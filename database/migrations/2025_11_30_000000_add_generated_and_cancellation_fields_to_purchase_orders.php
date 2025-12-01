<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        $isSqlite = Schema::getConnection()->getDriverName() === 'sqlite';

        Schema::table('purchase_orders', function (Blueprint $table) use ($isSqlite) {
            if (! Schema::hasColumn('purchase_orders', 'generated_by')) {
                if ($isSqlite) {
                    $table->unsignedBigInteger('generated_by')->nullable();
                } else {
                    $table->foreignId('generated_by')->nullable()->after('approved_at')->constrained('users')->nullOnDelete();
                }
            }

            if (! Schema::hasColumn('purchase_orders', 'cancelled_by')) {
                if ($isSqlite) {
                    $table->unsignedBigInteger('cancelled_by')->nullable();
                } else {
                    $table->foreignId('cancelled_by')->nullable()->after('assigned_to')->constrained('users')->nullOnDelete();
                }
            }

            if (! Schema::hasColumn('purchase_orders', 'cancelled_at')) {
                $column = $table->timestamp('cancelled_at')->nullable();

                if (! $isSqlite) {
                    $column->after('cancelled_by');
                }
            }

            if (! Schema::hasColumn('purchase_orders', 'cancellation_reason')) {
                $column = $table->string('cancellation_reason')->nullable();

                if (! $isSqlite) {
                    $column->after('cancelled_at');
                }
            }
        });

        // Ensure new permission exists and is assigned to managers and admins
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $guard = config('auth.defaults.guard', 'web');

        $cancelPermission = Permission::firstOrCreate([
            'name' => 'cancel purchase orders',
            'guard_name' => $guard,
        ]);

        foreach (['manager', 'admin'] as $roleName) {
            if ($role = Role::where('name', $roleName)->first()) {
                $role->givePermissionTo($cancelPermission);
            }
        }

        $viewLedgersPermission = Permission::firstOrCreate([
            'name' => 'view financial ledgers',
            'guard_name' => $guard,
        ]);

        foreach (['admin', 'manager', 'purchasing'] as $roleName) {
            if ($role = Role::where('name', $roleName)->first()) {
                $role->givePermissionTo($viewLedgersPermission);
            }
        }

        // Backfill generated_by for legacy records
        \App\Models\PurchaseOrder::query()
            ->whereNull('generated_by')
            ->chunkById(200, function ($orders): void {
                foreach ($orders as $order) {
                    $order->forceFill([
                        'generated_by' => $order->approved_by ?? $order->created_by,
                    ])->save();
                }
            });

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        $isSqlite = Schema::getConnection()->getDriverName() === 'sqlite';

        Schema::table('purchase_orders', function (Blueprint $table) use ($isSqlite) {
            if (Schema::hasColumn('purchase_orders', 'cancellation_reason')) {
                $table->dropColumn('cancellation_reason');
            }

            if (Schema::hasColumn('purchase_orders', 'cancelled_at')) {
                $table->dropColumn('cancelled_at');
            }

            if (Schema::hasColumn('purchase_orders', 'cancelled_by')) {
                if (! $isSqlite) {
                    $table->dropForeign(['cancelled_by']);
                }
                $table->dropColumn('cancelled_by');
            }

            if (Schema::hasColumn('purchase_orders', 'generated_by')) {
                if (! $isSqlite) {
                    $table->dropForeign(['generated_by']);
                }
                $table->dropColumn('generated_by');
            }
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
