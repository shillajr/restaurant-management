<?php

namespace App\Traits;

use App\Models\User;
use Illuminate\Support\Facades\DB;

trait Approvable
{
    /**
     * Approve the model instance.
     *
     * @param User $approver
     * @param string|null $notes
     * @return $this
     */
    public function approve(User $approver, ?string $notes = null)
    {
        DB::transaction(function () use ($approver, $notes) {
            $this->update([
                'status' => 'approved',
                'checker_id' => $approver->id,
                'checked_at' => now(),
                'approval_notes' => $notes
            ]);

            activity()
                ->performedOn($this)
                ->causedBy($approver)
                ->withProperties(['before' => $this->getOriginal(), 'after' => $this->getAttributes()])
                ->log('approved');
        });

        return $this;
    }

    /**
     * Reject the model instance.
     *
     * @param User $rejecter
     * @param string $reason
     * @return $this
     */
    public function reject(User $rejecter, string $reason)
    {
        DB::transaction(function () use ($rejecter, $reason) {
            $this->update([
                'status' => 'rejected',
                'checker_id' => $rejecter->id,
                'checked_at' => now(),
                'rejection_reason' => $reason
            ]);

            activity()
                ->performedOn($this)
                ->causedBy($rejecter)
                ->withProperties(['before' => $this->getOriginal(), 'after' => $this->getAttributes()])
                ->log('rejected');
        });

        return $this;
    }

    /**
     * Check if the model is pending approval.
     *
     * @return bool
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if the model is approved.
     *
     * @return bool
     */
    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    /**
     * Check if the model is rejected.
     *
     * @return bool
     */
    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    /**
     * Scope a query to only include pending records.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope a query to only include approved records.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope a query to only include rejected records.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }
}
