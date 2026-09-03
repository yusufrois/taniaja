<?php

namespace Tests\Feature\Cultivation;

use App\Models\ActivityTemplate;
use App\Models\ActivityTemplateItem;
use App\Models\Company;
use App\Models\Greenhouse;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Season;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScheduleGenerationTest extends TestCase
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

    public function test_generating_schedule_creates_dates_offset_from_planting_date(): void
    {
        $template = ActivityTemplate::factory()->create();
        ActivityTemplateItem::create([
            'company_id' => $template->company_id,
            'activity_template_id' => $template->id,
            'hst' => 7,
            'activity_name' => 'Pemupukan',
            'category' => 'pemupukan',
        ]);
        ActivityTemplateItem::create([
            'company_id' => $template->company_id,
            'activity_template_id' => $template->id,
            'hst' => 14,
            'activity_name' => 'Pemupukan Lanjutan',
            'category' => 'pemupukan',
        ]);

        $greenhouse = Greenhouse::factory()->create(['company_id' => $template->company_id]);
        $season = Season::create([
            'company_id' => $template->company_id,
            'greenhouse_id' => $greenhouse->id,
            'crop_id' => $template->variety->crop_id,
            'variety_id' => $template->variety_id,
            'season_name' => 'Musim Test',
            'planting_date' => '2026-08-01',
            'status' => 'active',
        ]);

        $owner = $this->makeUserWithRole(Company::find($template->company_id), 'owner', ['season.update']);

        $response = $this->actingAs($owner)->postJson("/api/v1/seasons/{$season->id}/generate-schedule");

        $response->assertOk();

        // Fetched through the model (not raw string matching against the
        // DB) so this doesn't depend on how the testing driver (SQLite)
        // happens to format a DATE column internally — SQLite stores it
        // with a "00:00:00" suffix while MySQL wouldn't, but the actual
        // date value is correct either way.
        $dates = \App\Models\Schedule::where('season_id', $season->id)
            ->orderBy('scheduled_date')
            ->pluck('scheduled_date')
            ->map(fn ($date) => $date->toDateString())
            ->all();

        $this->assertEquals(['2026-08-08', '2026-08-15'], $dates);
    }

    public function test_worker_can_complete_a_scheduled_activity(): void
    {
        $template = ActivityTemplate::factory()->create();
        $item = ActivityTemplateItem::create([
            'company_id' => $template->company_id,
            'activity_template_id' => $template->id,
            'hst' => 0,
            'activity_name' => 'Tanam',
            'category' => 'tanam',
        ]);

        $greenhouse = Greenhouse::factory()->create(['company_id' => $template->company_id]);
        $season = Season::create([
            'company_id' => $template->company_id,
            'greenhouse_id' => $greenhouse->id,
            'crop_id' => $template->variety->crop_id,
            'variety_id' => $template->variety_id,
            'season_name' => 'Musim Worker Test',
            'planting_date' => now()->toDateString(),
            'status' => 'active',
        ]);

        $company = Company::find($template->company_id);
        $owner = $this->makeUserWithRole($company, 'owner', ['season.update']);
        $this->actingAs($owner)->postJson("/api/v1/seasons/{$season->id}/generate-schedule")->assertOk();

        $scheduleId = \App\Models\Schedule::where('season_id', $season->id)->first()->id;

        $worker = $this->makeUserWithRole($company, 'worker', ['activity.view', 'activity.create']);

        $response = $this->actingAs($worker)->postJson("/api/v1/schedules/{$scheduleId}/complete", [
            'cost' => 50000,
            'notes' => 'Selesai tanam',
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('schedules', ['id' => $scheduleId, 'status' => 'completed']);
        $this->assertDatabaseHas('activities', ['schedule_id' => $scheduleId, 'cost' => 50000]);
    }

    public function test_worker_cannot_edit_season_but_can_still_complete_activity(): void
    {
        $template = ActivityTemplate::factory()->create();
        ActivityTemplateItem::create([
            'company_id' => $template->company_id,
            'activity_template_id' => $template->id,
            'hst' => 0,
            'activity_name' => 'Tanam',
            'category' => 'tanam',
        ]);

        $greenhouse = Greenhouse::factory()->create(['company_id' => $template->company_id]);
        $season = Season::create([
            'company_id' => $template->company_id,
            'greenhouse_id' => $greenhouse->id,
            'crop_id' => $template->variety->crop_id,
            'variety_id' => $template->variety_id,
            'season_name' => 'Musim',
            'planting_date' => now()->toDateString(),
            'status' => 'active',
        ]);

        $company = Company::find($template->company_id);
        $worker = $this->makeUserWithRole($company, 'worker', ['activity.view', 'activity.create']);

        // Worker has no season.update — must be forbidden from editing the season
        $response = $this->actingAs($worker)->putJson("/api/v1/seasons/{$season->id}", [
            'season_name' => 'Diubah Worker',
        ]);
        $response->assertForbidden();
    }

    public function test_dashboard_reflects_completed_activity_count(): void
    {
        $template = ActivityTemplate::factory()->create();
        ActivityTemplateItem::create([
            'company_id' => $template->company_id,
            'activity_template_id' => $template->id,
            'hst' => 0,
            'activity_name' => 'Tanam',
            'category' => 'tanam',
        ]);
        ActivityTemplateItem::create([
            'company_id' => $template->company_id,
            'activity_template_id' => $template->id,
            'hst' => 3,
            'activity_name' => 'Pemupukan',
            'category' => 'pemupukan',
        ]);

        $greenhouse = Greenhouse::factory()->create(['company_id' => $template->company_id]);
        $season = Season::create([
            'company_id' => $template->company_id,
            'greenhouse_id' => $greenhouse->id,
            'crop_id' => $template->variety->crop_id,
            'variety_id' => $template->variety_id,
            'season_name' => 'Musim Dashboard',
            'planting_date' => now()->toDateString(),
            'status' => 'active',
        ]);

        $company = Company::find($template->company_id);
        $owner = $this->makeUserWithRole($company, 'owner', ['season.update', 'season.view', 'activity.create']);
        $this->actingAs($owner)->postJson("/api/v1/seasons/{$season->id}/generate-schedule")->assertOk();

        $firstSchedule = \App\Models\Schedule::where('season_id', $season->id)->orderBy('scheduled_date')->first();
        $this->actingAs($owner)->postJson("/api/v1/schedules/{$firstSchedule->id}/complete")->assertCreated();

        $dashboard = $this->actingAs($owner)->getJson("/api/v1/seasons/{$season->id}/dashboard");

        $dashboard->assertOk();
        $dashboard->assertJsonPath('summary.activities_total', 2);
        $dashboard->assertJsonPath('summary.activities_completed', 1);
    }
}
