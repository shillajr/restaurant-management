<?php

namespace App\Policies;

use App\Models\PurchaseOrder;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PurchaseOrderPolicy
{
    use HandlesAuthorization;

    /**
     * Grant all abilities to admins before evaluating specific permissions.
     */
    public function before(User $user, string $ability): bool|null
    {
        return $user->hasRole('admin') ? true : null;
    }

    public function view(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $user->can('view purchase orders');
    }

    public function update(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $user->can('edit purchase orders');
    }

    public function approve(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $user->can('approve purchase orders')
            || $user->can('approve requisitions');
    }

    public function send(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $user->can('send purchase orders');
    }

    public function markPurchased(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $user->can('mark purchased');
    }
}
