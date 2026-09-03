<?php

namespace Tests\Feature\Attendance;

use App\Models\Company;
use App\Models\Employee;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceTest extends TestCase
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

    public function test_owner_can_create_an_employee(): void
    {
        $company = Company::factory()->create();
        $owner = $this->makeUserWithRole($company, 'owner', ['employee.create']);

        $response = $this->actingAs($owner)->postJson('/api/v1/employees', [
            'name' => 'Budi Santoso',
            'position' => 'Petugas Lapangan',
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('employees', ['name' => 'Budi Santoso', 'company_id' => $company->id]);
    }

    public function test_worker_can_self_check_in_with_gps(): void
    {
        $company = Company::factory()->create();
        $worker = $this->makeUserWithRole($company, 'worker', ['attendance.view_own']);
        Employee::factory()->create(['company_id' => $company->id, 'user_id' => $worker->id, 'name' => $worker->name]);

        $response = $this->actingAs($worker)->postJson('/api/v1/attendances/check-in', [
            'status' => 'hadir',
            'check_in_lat' => -7.9797,
            'check_in_lng' => 112.6304,
        ]);

        $response->assertCreated();
        $this->assertEquals('hadir', $response->json('data.status'));
        $this->assertNotNull($response->json('data.check_in_time'));
    }

    public function test_self_check_in_requires_gps_when_status_is_hadir(): void
    {
        $company = Company::factory()->create();
        $worker = $this->makeUserWithRole($company, 'worker', []);
        Employee::factory()->create(['company_id' => $company->id, 'user_id' => $worker->id]);

        $response = $this->actingAs($worker)->postJson('/api/v1/attendances/check-in', [
            'status' => 'hadir',
            // no GPS
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('check_in_lat');
    }

    public function test_self_check_in_does_not_require_gps_for_izin(): void
    {
        $company = Company::factory()->create();
        $worker = $this->makeUserWithRole($company, 'worker', []);
        Employee::factory()->create(['company_id' => $company->id, 'user_id' => $worker->id]);

        $response = $this->actingAs($worker)->postJson('/api/v1/attendances/check-in', [
            'status' => 'izin',
            'notes' => 'Ada acara keluarga',
        ]);

        $response->assertCreated();
    }

    /**
     * The core "easy to fix mistakes" behavior for self check-in:
     * checking in twice the same day corrects the SAME record instead
     * of erroring out or creating a duplicate.
     */
    public function test_self_check_in_twice_same_day_upserts_instead_of_erroring(): void
    {
        $company = Company::factory()->create();
        $worker = $this->makeUserWithRole($company, 'worker', ['attendance.view_own']);
        Employee::factory()->create(['company_id' => $company->id, 'user_id' => $worker->id]);

        $first = $this->actingAs($worker)->postJson('/api/v1/attendances/check-in', [
            'status' => 'hadir', 'check_in_lat' => -7.9797, 'check_in_lng' => 112.6304,
        ]);
        $first->assertCreated();

        // Accidentally checked in as "hadir" but actually taking izin today — self-corrects.
        $second = $this->actingAs($worker)->postJson('/api/v1/attendances/check-in', [
            'status' => 'izin', 'notes' => 'Salah tadi, sebenarnya izin',
        ]);
        // Second call is semantically an UPDATE (correcting the same
        // day's record), not a new creation — 200 OK, not 201 Created.
        $second->assertOk();

        $this->assertEquals($first->json('data.id'), $second->json('data.id')); // same record, not a new one
        $this->assertDatabaseCount('attendances', 1);
        $this->assertEquals('izin', $second->json('data.status'));
    }

    public function test_check_in_fails_gracefully_without_a_linked_employee(): void
    {
        $company = Company::factory()->create();
        $userWithoutEmployee = $this->makeUserWithRole($company, 'worker', []);
        // No Employee record linked.

        $response = $this->actingAs($userWithoutEmployee)->postJson('/api/v1/attendances/check-in', [
            'status' => 'hadir', 'check_in_lat' => -7.9797, 'check_in_lng' => 112.6304,
        ]);

        $response->assertStatus(422);
    }

    public function test_supervisor_can_mark_attendance_for_employee_without_an_account(): void
    {
        $company = Company::factory()->create();
        $supervisor = $this->makeUserWithRole($company, 'supervisor', ['attendance.create']);
        $employee = Employee::factory()->create(['company_id' => $company->id]); // no user_id — no account

        $response = $this->actingAs($supervisor)->postJson('/api/v1/attendances', [
            'employee_id' => $employee->id,
            'date' => now()->toDateString(),
            'status' => 'hadir',
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('attendances', ['employee_id' => $employee->id, 'recorded_by' => $supervisor->id]);
    }

    /**
     * Marking someone else twice for the same day is DIFFERENT from
     * self check-in — must NOT silently overwrite. Instead gives a
     * clear, actionable error pointing to the correction path.
     */
    public function test_marking_duplicate_attendance_points_to_existing_record_for_correction(): void
    {
        $company = Company::factory()->create();
        $supervisor = $this->makeUserWithRole($company, 'supervisor', ['attendance.create']);
        $employee = Employee::factory()->create(['company_id' => $company->id]);

        $first = $this->actingAs($supervisor)->postJson('/api/v1/attendances', [
            'employee_id' => $employee->id, 'date' => now()->toDateString(), 'status' => 'hadir',
        ]);
        $first->assertCreated();

        $duplicate = $this->actingAs($supervisor)->postJson('/api/v1/attendances', [
            'employee_id' => $employee->id, 'date' => now()->toDateString(), 'status' => 'alpa',
        ]);

        $duplicate->assertStatus(422);
        $errorMessage = $duplicate->json('errors.date.0');
        $this->assertStringContainsString((string) $first->json('data.id'), $errorMessage);
    }

    /**
     * THE correction path itself — fixing a mistake via update instead
     * of delete+recreate.
     */
    public function test_correcting_a_wrong_status_via_update(): void
    {
        $company = Company::factory()->create();
        $supervisor = $this->makeUserWithRole($company, 'supervisor', ['attendance.create', 'attendance.update']);
        $employee = Employee::factory()->create(['company_id' => $company->id]);

        $created = $this->actingAs($supervisor)->postJson('/api/v1/attendances', [
            'employee_id' => $employee->id, 'date' => now()->toDateString(), 'status' => 'alpa',
        ]);
        $attendanceId = $created->json('data.id');

        // Turns out the employee actually called in sick — correct it.
        $corrected = $this->actingAs($supervisor)->putJson("/api/v1/attendances/{$attendanceId}", [
            'status' => 'sakit',
            'notes' => 'Ternyata sakit, ada kabar dari keluarganya',
        ]);

        $corrected->assertOk();
        $this->assertEquals('sakit', $corrected->json('data.status'));
        $this->assertDatabaseCount('attendances', 1); // still just one record, corrected in place
    }

    public function test_worker_with_view_own_sees_only_their_own_attendance(): void
    {
        $company = Company::factory()->create();
        $workerA = $this->makeUserWithRole($company, 'worker_a', ['attendance.view_own']);
        $employeeA = Employee::factory()->create(['company_id' => $company->id, 'user_id' => $workerA->id]);
        $employeeB = Employee::factory()->create(['company_id' => $company->id]); // someone else

        \App\Models\Attendance::create([
            'company_id' => $company->id, 'employee_id' => $employeeA->id,
            'date' => now()->toDateString(), 'status' => 'hadir',
        ]);
        \App\Models\Attendance::create([
            'company_id' => $company->id, 'employee_id' => $employeeB->id,
            'date' => now()->toDateString(), 'status' => 'hadir',
        ]);

        $list = $this->actingAs($workerA)->getJson('/api/v1/attendances');
        $list->assertOk();
        $this->assertCount(1, $list->json('data'));
        $this->assertEquals($employeeA->id, $list->json('data.0.employee_id'));
    }

    public function test_supervisor_with_full_view_sees_everyones_attendance(): void
    {
        $company = Company::factory()->create();
        $supervisor = $this->makeUserWithRole($company, 'supervisor', ['attendance.view']);
        $employeeA = Employee::factory()->create(['company_id' => $company->id]);
        $employeeB = Employee::factory()->create(['company_id' => $company->id]);

        \App\Models\Attendance::create([
            'company_id' => $company->id, 'employee_id' => $employeeA->id,
            'date' => now()->toDateString(), 'status' => 'hadir',
        ]);
        \App\Models\Attendance::create([
            'company_id' => $company->id, 'employee_id' => $employeeB->id,
            'date' => now()->toDateString(), 'status' => 'alpa',
        ]);

        $list = $this->actingAs($supervisor)->getJson('/api/v1/attendances');
        $list->assertOk();
        $this->assertCount(2, $list->json('data'));
    }

    public function test_deleting_an_attendance_record_soft_deletes_it(): void
    {
        $company = Company::factory()->create();
        $supervisor = $this->makeUserWithRole($company, 'supervisor', ['attendance.create', 'attendance.delete']);
        $employee = Employee::factory()->create(['company_id' => $company->id]);

        $created = $this->actingAs($supervisor)->postJson('/api/v1/attendances', [
            'employee_id' => $employee->id, 'date' => now()->toDateString(), 'status' => 'hadir',
        ]);
        $id = $created->json('data.id');

        $this->actingAs($supervisor)->deleteJson("/api/v1/attendances/{$id}")->assertOk();

        $this->assertSoftDeleted('attendances', ['id' => $id]);
    }
}
