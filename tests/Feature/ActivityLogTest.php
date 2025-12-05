<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ActivityLogTest extends TestCase
{
    use RefreshDatabase;

    private function createRoleWithPermission(string $roleName, string $permissionName): Role
    {
        $permission = Permission::firstOrCreate(['name' => $permissionName]);
        $role = Role::firstOrCreate(['name' => $roleName]);
        $role->givePermissionTo($permission);

        return $role;
    }

    #[Test]
    public function authorised_user_can_view_activity_log(): void
    {
        $role = $this->createRoleWithPermission('admin', 'view activity log');
        $user = User::factory()->create();
        $user->assignRole($role);

        activity()
            ->causedBy($user)
            ->event('test_event')
            ->log('Demo activity entry.');

        $response = $this->actingAs($user)->get(route('activity-log.index'));

        $response->assertOk();
        $response->assertSee('Activity Log');
        $response->assertSee('Demo activity entry.');
    }

    #[Test]
    public function unauthorised_user_is_blocked(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('activity-log.index'));

        $response->assertForbidden();
    }

    #[Test]
    public function activity_log_can_be_filtered_by_role(): void
    {
        $adminRole = $this->createRoleWithPermission('admin', 'view activity log');
        $managerRole = Role::firstOrCreate(['name' => 'manager']);

        $viewer = User::factory()->create();
        $viewer->assignRole($adminRole);

        $actorAdmin = User::factory()->create();
        $actorAdmin->assignRole($adminRole);

        $actorManager = User::factory()->create();
        $actorManager->assignRole($managerRole);

        activity()->causedBy($actorAdmin)->log('Admin activity entry.');
        activity()->causedBy($actorManager)->log('Manager activity entry.');

        $response = $this->actingAs($viewer)->get(route('activity-log.index', ['role' => 'manager']));

        $response->assertOk();
        $response->assertSee('Manager activity entry.');
        $response->assertDontSee('Admin activity entry.');
    }
}
