<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\CatalogCategory;
use App\Models\User;
use App\Services\ActivityRecorder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityLogTest extends TestCase
{
    use RefreshDatabase;

    private function staff(bool $superadmin = false): User
    {
        return User::factory()->create(['is_admin' => true, 'is_superadmin' => $superadmin]);
    }

    public function test_guest_and_member_cannot_read_logs(): void
    {
        $this->get('/admin/activity-logs')->assertRedirect('/login');
        $this->actingAs(User::factory()->create(['password_setup_at' => now()]))->get('/admin/activity-logs')->assertForbidden();
    }

    public function test_staff_can_read_logs_without_logging_the_log_page_itself(): void
    {
        foreach ([false, true] as $superadmin) {
            $this->actingAs($this->staff($superadmin))->get('/admin/activity-logs')->assertOk()->assertSee('Log aktivitas');
        }
        $this->assertDatabaseCount('activity_logs', 0);
        $this->post('/admin/activity-logs')->assertMethodNotAllowed();
        $this->delete('/admin/activity-logs/1')->assertNotFound();
    }

    public function test_successful_mutation_records_actor_and_target_without_request_secrets(): void
    {
        $staff = $this->staff();
        $originalName = $staff->name;
        $originalEmail = $staff->email;
        $this->actingAs($staff)->put('/admin/settings/profile', [
            'name' => 'New name', 'email' => 'new@example.com', 'password' => 'never-record-this',
            'client_secret' => 'never-record-this', '_token' => 'never-record-this',
        ])->assertRedirect('/admin/settings');
        $entry = ActivityLog::sole();
        $this->assertSame($staff->id, $entry->actor_id);
        $this->assertSame($originalName, $entry->actor_name);
        $this->assertSame($originalEmail, $entry->actor_email);
        $this->assertSame('profile', $entry->action);
        $this->assertSame(['user' => (string) $staff->id], $entry->targets);
        $this->assertStringNotContainsString('never-record-this', $entry->toJson());
        $this->assertSame('New name', $staff->fresh()->name);
    }

    public function test_validation_failure_and_forbidden_action_are_not_reported_as_success(): void
    {
        $staff = $this->staff();
        $this->actingAs($staff)->put('/admin/settings/profile', ['name' => '', 'email' => 'invalid'])->assertSessionHasErrors();
        $this->assertDatabaseCount('activity_logs', 0);
        $this->get('/admin/settings')->assertOk();
        $this->put('/admin/settings/profile', ['name' => 'Fixed', 'email' => 'fixed@example.com'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('activity_logs', ['action' => 'profile', 'actor_id' => $staff->id]);
        $count = ActivityLog::count();
        $this->put('/admin/settings', ['brand_name' => 'Forbidden'])->assertForbidden();
        $this->assertDatabaseCount('activity_logs', $count);
    }

    public function test_page_visits_are_not_logged_and_mutations_record_the_route_bound_target(): void
    {
        $staff = $this->staff();
        $this->actingAs($staff)->get('/admin/catalogs')->assertOk();
        $this->assertDatabaseCount('activity_logs', 0);
        $category = CatalogCategory::create(['name' => 'Test category', 'slug' => 'test-category']);
        $this->delete('/admin/catalog-categories/'.$category->id)->assertRedirect();
        $entry = ActivityLog::where('action', 'deactivate')->sole();
        $this->assertSame(['category' => (string) $category->id], $entry->targets);
        $this->assertFalse($category->fresh()->is_active);
    }

    public function test_admin_cannot_see_restricted_areas_even_with_filters(): void
    {
        $actor = $this->staff(true);
        app(ActivityRecorder::class)->record($actor, 'points', 'store', 'admin.points.store');
        app(ActivityRecorder::class)->record($actor, 'brand', 'update', 'admin.settings.update');
        app(ActivityRecorder::class)->record($actor, 'tracking', 'update', 'admin.tracking.update');
        $this->actingAs($this->staff())->get('/admin/activity-logs')->assertOk()
            ->assertViewHas('logs', fn ($logs) => $logs->total() === 1 && $logs->first()->area === 'tracking')
            ->assertDontSee('admin.points.store')->assertDontSee('admin.settings.update');
        $this->get('/admin/activity-logs?area=points')->assertSessionHasErrors('area');
        $this->actingAs($actor)->get('/admin/activity-logs')->assertOk()->assertViewHas('logs', fn ($logs) => $logs->total() === 3);
    }

    public function test_filters_search_actor_action_area_and_date_together(): void
    {
        $this->freezeTime();
        $actor = $this->staff(true);
        $actor->forceFill(['name' => 'Specific actor'])->save();
        app(ActivityRecorder::class)->record($actor, 'tracking', 'update', 'admin.tracking.update');
        app(ActivityRecorder::class)->record($actor, 'catalogs', 'update', 'admin.catalogs.update');
        $query = http_build_query(['q' => 'Specific', 'actor' => $actor->email, 'action' => 'update', 'area' => 'tracking', 'from' => now()->toDateString(), 'to' => now()->toDateString()]);
        $this->actingAs($actor)->get('/admin/activity-logs?'.$query)->assertOk()->assertViewHas('logs', fn ($logs) => $logs->total() === 1);
        $this->get('/admin/activity-logs?to='.now()->toDateString())->assertOk()->assertViewHas('logs', fn ($logs) => $logs->total() === 2);
        $this->get('/admin/activity-logs?q=absent')->assertOk()->assertViewHas('logs', fn ($logs) => $logs->total() === 0);
        $this->get('/admin/activity-logs?from=2026-10-20&to=2026-10-19')->assertSessionHasErrors('to');
        $this->get('/admin/activity-logs?from=invalid')->assertSessionHasErrors('from');
    }

    public function test_login_logout_and_legacy_page_visits_are_excluded(): void
    {
        $staff = $this->staff(true);
        $this->post('/login', ['email' => $staff->email, 'password' => 'password'])->assertRedirect('/admin');
        $this->post('/logout')->assertRedirect('/login');
        $this->assertDatabaseCount('activity_logs', 0);
        app(ActivityRecorder::class)->record($staff, 'catalogs', 'view', 'admin.catalogs.index');
        app(ActivityRecorder::class)->record($staff, 'auth', 'login', 'auth.login');
        $this->actingAs($staff)->get('/admin/activity-logs')->assertOk()->assertViewHas('logs', fn ($logs) => $logs->total() === 0);
    }

    public function test_actor_snapshot_remains_after_account_is_deleted_and_output_is_escaped(): void
    {
        $actor = $this->staff();
        $actor->forceFill(['name' => '<script>alert(1)</script>'])->save();
        app(ActivityRecorder::class)->record($actor, 'tracking', 'update', 'admin.tracking.update');
        $actor->delete();
        $this->assertNull(ActivityLog::sole()->actor_id);
        $this->actingAs($this->staff(true))->get('/admin/activity-logs')->assertOk()
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)->assertDontSee('<script>alert(1)</script>', false);
    }

    public function test_log_list_is_paginated(): void
    {
        $actor = $this->staff();
        for ($i = 0; $i < 23; $i++) {
            app(ActivityRecorder::class)->record($actor, 'tracking', 'update', 'admin.tracking.update');
        }
        $this->actingAs($actor)->get('/admin/activity-logs?page=2')->assertOk()
            ->assertViewHas('logs', fn ($logs) => $logs->total() === 23 && $logs->count() === 3);
    }
}
