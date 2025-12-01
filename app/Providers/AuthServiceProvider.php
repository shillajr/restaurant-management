<?php

namespace App\Providers;

use App\Models\PurchaseOrder;
use App\Policies\PurchaseOrderPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        PurchaseOrder::class => PurchaseOrderPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        Gate::define('approve-purchase-order', function ($user, PurchaseOrder $purchaseOrder) {
            return $user->can('approve purchase orders')
                || $user->can('approve requisitions');
        });
    }
}
