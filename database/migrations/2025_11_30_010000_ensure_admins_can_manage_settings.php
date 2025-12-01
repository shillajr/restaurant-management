<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $guard = config('auth.defaults.guard', 'web');

        $manageSettings = Permission::firstOrCreate([
            'name' => 'manage settings',
            'guard_name' => $guard,
        ]);

        if ($adminRole = Role::where('name', 'admin')->first()) {
            if (! $adminRole->hasPermissionTo($manageSettings)) {
                $adminRole->givePermissionTo($manageSettings);
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
