<?php

namespace Tests\Feature\Task;

use App\Models\Company;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskTest extends TestCase
{
    use RefreshDatabase;

    private function makeUserWithRole(Company $company, string $slug, array $permissionNames = []): User
    {
        $role = Role::create(['company_id' => $company->id, 'slug' => $slug, 'name' => ucfirst($slug)]);

        if ($permissionNames) {
            $ids = collect($permissionNames)->map(
                fn ($name) => Permission::firstOrCreate(['name' => $name], ['module' => explode('.', $name)[0]])->id
            );
            $role->permissions()->sync($ids);
        }

        $user = User::factory()->create(['company_id' => $company->id]);
        $user->roles()->attach($role->id);

        return $user;
    }

    public function test_supervisor_can_assign_a_task_with_steps(): void
    {
        $company = Company::factory()->create();
        $supervisor = $this->makeUserWithRole($company, 'supervisor', ['task.create']);
        $worker = $this->makeUserWithRole($company, 'worker', []);

        $response = $this->actingAs($supervisor)->postJson('/api/v1/tasks', [
            'title' => 'Benerin pompa air',
            'assigned_to' => $worker->id,
            'steps' => ['Matikan aliran listrik', 'Bongkar pompa', 'Ganti seal yang bocor'],
        ]);

        $response->assertCreated();
        $this->assertCount(3, $response->json('data.steps'));
    }

    public function test_assigning_a_task_creates_an_in_app_notification(): void
    {
        $company = Company::factory()->create();
        $supervisor = $this->makeUserWithRole($company, 'supervisor', ['task.create']);
        $worker = $this->makeUserWithRole($company, 'worker', []);

        $this->actingAs($supervisor)->postJson('/api/v1/tasks', [
            'title' => 'Benerin pompa air', 'assigned_to' => $worker->id,
        ])->assertCreated();

        $this->assertDatabaseHas('notifications', [
            'user_id' => $worker->id,
            'type' => 'task_assigned',
        ]);
    }

    /**
     * The lighter-bar design: the assignee can check off a step WITHOUT
     * needing 'task.update' — they only need 'task.view_own' (they're
     * involved in the task at all).
     */
    public function test_assignee_can_toggle_a_step_with_only_view_own_permission(): void
    {
        $company = Company::factory()->create();
        $supervisor = $this->makeUserWithRole($company, 'supervisor', ['task.create']);
        $worker = $this->makeUserWithRole($company, 'worker', ['task.view_own']);

        $created = $this->actingAs($supervisor)->postJson('/api/v1/tasks', [
            'title' => 'Benerin pompa air', 'assigned_to' => $worker->id, 'steps' => ['Langkah 1'],
        ]);
        $taskId = $created->json('data.id');
        $stepId = $created->json('data.steps.0.id');

        $response = $this->actingAs($worker)->patchJson("/api/v1/tasks/{$taskId}/steps/{$stepId}/toggle");
        $response->assertOk();
        $this->assertTrue($response->json('data.steps.0.is_done'));
    }

    /**
     * Editing the task's TITLE is a stricter bar than checking a step
     * — an assignee with only view_own should NOT be able to rewrite
     * what the task even says.
     */
    public function test_assignee_cannot_edit_task_title(): void
    {
        $company = Company::factory()->create();
        $supervisor = $this->makeUserWithRole($company, 'supervisor', ['task.create']);
        $worker = $this->makeUserWithRole($company, 'worker', ['task.view_own']);

        $created = $this->actingAs($supervisor)->postJson('/api/v1/tasks', [
            'title' => 'Judul Asli', 'assigned_to' => $worker->id,
        ]);
        $taskId = $created->json('data.id');

        $response = $this->actingAs($worker)->putJson("/api/v1/tasks/{$taskId}", ['title' => 'Diubah Paksa']);
        $response->assertForbidden();
    }

    public function test_assigner_can_edit_the_task_they_gave(): void
    {
        $company = Company::factory()->create();
        $supervisor = $this->makeUserWithRole($company, 'supervisor', ['task.create']);
        $worker = $this->makeUserWithRole($company, 'worker', []);

        $created = $this->actingAs($supervisor)->postJson('/api/v1/tasks', [
            'title' => 'Judul Asli', 'assigned_to' => $worker->id,
        ]);
        $taskId = $created->json('data.id');

        $response = $this->actingAs($supervisor)->putJson("/api/v1/tasks/{$taskId}", ['title' => 'Judul Diperbaiki']);
        $response->assertOk();
        $this->assertEquals('Judul Diperbaiki', $response->json('data.title'));
    }

    public function test_worker_sees_only_tasks_assigned_to_or_by_them(): void
    {
        $company = Company::factory()->create();
        $supervisor = $this->makeUserWithRole($company, 'supervisor', ['task.create']);
        $worker = $this->makeUserWithRole($company, 'worker', ['task.view_own']);
        $otherWorker = $this->makeUserWithRole($company, 'worker2', []);

        $this->actingAs($supervisor)->postJson('/api/v1/tasks', [
            'title' => 'Tugas untuk saya', 'assigned_to' => $worker->id,
        ])->assertCreated();
        $this->actingAs($supervisor)->postJson('/api/v1/tasks', [
            'title' => 'Tugas untuk orang lain', 'assigned_to' => $otherWorker->id,
        ])->assertCreated();

        $list = $this->actingAs($worker)->getJson('/api/v1/tasks');
        $list->assertOk();
        $this->assertCount(1, $list->json('data'));
        $this->assertEquals('Tugas untuk saya', $list->json('data.0.title'));
    }

    /**
     * "Tugas susulan" — confirmed to be a NEW separate Task marked
     * related, not steps appended to the original.
     */
    public function test_follow_up_creates_a_separate_related_task(): void
    {
        $company = Company::factory()->create();
        $supervisor = $this->makeUserWithRole($company, 'supervisor', ['task.create']);
        $worker = $this->makeUserWithRole($company, 'worker', []);

        $original = $this->actingAs($supervisor)->postJson('/api/v1/tasks', [
            'title' => 'Bersihkan gudang', 'assigned_to' => $worker->id,
        ]);
        $originalId = $original->json('data.id');

        $followUp = $this->actingAs($supervisor)->postJson("/api/v1/tasks/{$originalId}/follow-up", [
            'title' => 'Rapikan rak yang ketinggalan', 'assigned_to' => $worker->id,
        ]);

        $followUp->assertCreated();
        $this->assertEquals($originalId, $followUp->json('data.related_task_id'));
        $this->assertNotEquals($originalId, $followUp->json('data.id')); // separate row, not appended

        $this->assertDatabaseCount('tasks', 2);
    }

    public function test_registering_a_device_token(): void
    {
        $company = Company::factory()->create();
        $worker = $this->makeUserWithRole($company, 'worker', []);

        $response = $this->actingAs($worker)->postJson('/api/v1/device-tokens', [
            'token' => 'sample-fcm-token-abc123', 'platform' => 'android',
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('device_tokens', ['user_id' => $worker->id, 'token' => 'sample-fcm-token-abc123']);
    }

    public function test_marking_a_notification_as_read(): void
    {
        $company = Company::factory()->create();
        $supervisor = $this->makeUserWithRole($company, 'supervisor', ['task.create']);
        $worker = $this->makeUserWithRole($company, 'worker', []);

        $this->actingAs($supervisor)->postJson('/api/v1/tasks', [
            'title' => 'Tugas', 'assigned_to' => $worker->id,
        ])->assertCreated();

        $list = $this->actingAs($worker)->getJson('/api/v1/notifications');
        $notificationId = $list->json('data.0.id');
        $this->assertNull($list->json('data.0.read_at'));

        $markRead = $this->actingAs($worker)->patchJson("/api/v1/notifications/{$notificationId}/read");
        $markRead->assertOk();
        $this->assertNotNull($markRead->json('data.read_at'));
    }
}
