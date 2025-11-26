<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

/**
 * ChefRequisition Model
 * 
 * Relationships:
 * - belongsTo User (chef)
 * - belongsTo User (checker)
 * - hasOne PurchaseOrder
 * - morphMany Activity (audit trail)
 */
class ChefRequisition extends Model
{
    use HasFactory, LogsActivity;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'chef_id',
        'requested_for_date',
        'items',
        'note',
        'status',
        'checker_id',
        'checked_at',
        'rejection_reason',
        'change_request',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'items' => 'array',
        'requested_for_date' => 'date',
        'checked_at' => 'datetime',
    ];

    /**
     * Get the chef who created the requisition.
     */
    public function chef(): BelongsTo
    {
        return $this->belongsTo(User::class, 'chef_id');
    }

    /**
     * Get the user who checked/approved the requisition.
     */
    public function checker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checker_id');
    }

    /**
     * Get the purchase order associated with the requisition.
     */
    public function purchaseOrder(): HasOne
    {
        return $this->hasOne(PurchaseOrder::class);
    }

    /**
     * Get all of the requisition's activity logs.
     */
    public function activities(): MorphMany
    {
        return $this->morphMany(\Spatie\Activitylog\Models\Activity::class, 'subject');
    }

    /**
     * Activity log options.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['chef_id', 'requested_for_date', 'items', 'note', 'status', 'checker_id', 'checked_at'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
