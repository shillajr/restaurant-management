<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class PurchaseOrder extends Model
{
    use HasFactory, LogsActivity;

    public const WORKFLOW_COMPLETED = 'completed';

    protected $fillable = [
        'po_number',
        'requisition_id',
        'created_by',
        'approved_by',
        'approved_at',
        'generated_by',
        'requested_delivery_date',
        'items',
        'total_quantity',
        'subtotal',
        'tax',
        'other_charges',
        'grand_total',
        'status',
        // New workflow-centric status distinct from legacy status column
        'workflow_status',
        'has_credit_items',
        'credit_outstanding_amount',
        'credit_closed_at',
        'notes',
        // Legacy fields (kept for compatibility)
        'assigned_to',
        'cancelled_by',
        'cancelled_at',
        'cancellation_reason',
        'supplier_id',
        'invoice_number',
        'total_amount',
        'purchased_at',
        'receipt_path',
    ];

    protected $casts = [
        'items' => 'array',
        'approved_at' => 'datetime',
        'requested_delivery_date' => 'date',
        'total_quantity' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'tax' => 'decimal:2',
        'other_charges' => 'decimal:2',
        'grand_total' => 'decimal:2',
        'purchased_at' => 'datetime',
        'total_amount' => 'decimal:2',
        'cancelled_at' => 'datetime',
        'credit_outstanding_amount' => 'decimal:2',
        'credit_closed_at' => 'datetime',
        'has_credit_items' => 'boolean',
    ];

    /**
     * Human-friendly workflow statuses mapping.
     */
    public const WORKFLOW_STATUSES = [
        'pending',        // PO drafted from approved requisition, internal review before sending
        'sent_to_vendor', // PO dispatched to vendor
        'returned',       // Vendor responded with changes / questions
        'approved',       // Final vendor confirmation / internal final approval
        'completed',      // Purchasing team marked as completed
        'rejected',       // Rejected internally or by vendor
        'cancelled',      // Cancelled after approval with appropriate permissions
    ];

    /**
     * Get color classes for workflow statuses.
     */
    public static function workflowStatusColor(string $status): string
    {
        return [
            'pending' => 'bg-yellow-100 text-yellow-800',
            'sent_to_vendor' => 'bg-indigo-100 text-indigo-800',
            'returned' => 'bg-purple-100 text-purple-800',
            'approved' => 'bg-green-100 text-green-800',
            'completed' => 'bg-emerald-200 text-emerald-900',
            'rejected' => 'bg-red-100 text-red-800',
            'cancelled' => 'bg-gray-200 text-gray-800',
        ][$status] ?? 'bg-gray-100 text-gray-800';
    }

    public function creditLedgers(): HasMany
    {
        return $this->hasMany(FinancialLedger::class);
    }

    public function markCreditCompleted(): void
    {
        $this->forceFill([
            'has_credit_items' => false,
            'credit_outstanding_amount' => 0,
            'credit_closed_at' => Carbon::now(),
            'workflow_status' => self::WORKFLOW_COMPLETED,
        ])->saveQuietly();
    }

    /**
     * Generate a unique PO number
     */
    public static function generatePONumber(): string
    {
        $year = date('Y');
        $month = date('m');
        $lastPO = self::whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->orderBy('id', 'desc')
            ->first();

        $sequence = $lastPO ? (intval(substr($lastPO->po_number, -4)) + 1) : 1;
        
        return sprintf('PO-%s%s-%04d', $year, $month, $sequence);
    }

    /**
     * Get the activity log options.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * Get the requisition that this purchase order is for.
     */
    public function requisition(): BelongsTo
    {
        return $this->belongsTo(ChefRequisition::class, 'requisition_id');
    }

    /**
     * Get the user who approved and created this PO
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function generator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    /**
     * Get the user who created this PO (from requisition)
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user assigned to this purchase order.
     */
    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    /**
     * Get the expense associated with this purchase order.
     */
    public function expense(): HasOne
    {
        return $this->hasOne(Expense::class);
    }

    /**
     * Scope for filtering by status
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Check if PO can be edited
     */
    public function canBeEdited(): bool
    {
        return in_array($this->status, ['open', 'ordered']);
    }

    /**
     * Check if PO is completed
     */
    public function isCompleted(): bool
    {
        return in_array($this->status, ['received', 'closed']);
    }

    /**
     * Get unique vendors from items
     */
    public function getVendorsAttribute(): array
    {
        if (!$this->items) {
            return [];
        }

        $vendors = collect($this->items)
            ->pluck('vendor')
            ->unique()
            ->filter()
            ->values()
            ->toArray();

        return $vendors;
    }

    /**
     * Get items grouped by vendor with detailed information
     */
    public function getItemsByVendor(): array
    {
        if (!$this->items) {
            return [];
        }

        return collect($this->items)
            ->groupBy('vendor')
            ->map(function ($items, $vendorName) {
                $vendorTotal = $items->sum(function ($item) {
                    return ($item['price'] ?? 0) * ($item['quantity'] ?? 0);
                });
                
                return [
                    'vendor_name' => $vendorName,
                    'items' => $items->values(),
                    'item_count' => $items->count(),
                    'total_quantity' => $items->sum('quantity'),
                    'vendor_subtotal' => $vendorTotal,
                ];
            })
            ->sortByDesc('vendor_subtotal')
            ->values()
            ->toArray();
    }

    /**
     * Get vendor statistics
     */
    public function getVendorStats(): array
    {
        $itemsByVendor = $this->getItemsByVendor();
        
        return [
            'total_vendors' => count($itemsByVendor),
            'vendors' => collect($itemsByVendor)->pluck('vendor_name')->toArray(),
            'largest_vendor' => $itemsByVendor[0]['vendor_name'] ?? null,
            'largest_vendor_amount' => $itemsByVendor[0]['vendor_subtotal'] ?? 0,
        ];
    }
}
