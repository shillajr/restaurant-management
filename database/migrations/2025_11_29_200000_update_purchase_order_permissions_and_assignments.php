<?php

use App\Models\PurchaseOrder;
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

        $viewPurchaseOrders = Permission::firstOrCreate([
            'name' => 'view purchase orders',
            'guard_name' => $guard,
        ]);

        $sendPurchaseOrders = Permission::firstOrCreate([
            'name' => 'send purchase orders',
            'guard_name' => $guard,
        ]);

        $markPurchased = Permission::firstOrCreate([
            'name' => 'mark purchased',
            'guard_name' => $guard,
        ]);

        if ($chefRole = Role::where('name', 'chef')->first()) {
            $chefRole->givePermissionTo($viewPurchaseOrders);
        }

        if ($purchaserRole = Role::where('name', 'purchaser')->first()) {
            if ($purchaserRole->hasPermissionTo($markPurchased)) {
                $purchaserRole->revokePermissionTo($markPurchased);
            }

            $purchaserRole->givePermissionTo([$sendPurchaseOrders, $viewPurchaseOrders]);
        }

        foreach (['manager', 'admin'] as $roleName) {
            if ($role = Role::where('name', $roleName)->first()) {
                $role->givePermissionTo([$markPurchased, $viewPurchaseOrders]);
            }
        }

        PurchaseOrder::query()
            ->whereNull('assigned_to')
            ->chunkById(200, function ($orders): void {
                foreach ($orders as $order) {
                    $assigneeId = $order->approved_by ?? $order->created_by;

                    if (! $assigneeId) {
                        continue;
                    }

                    $order->forceFill(['assigned_to' => $assigneeId])->save();
                }
            });

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
