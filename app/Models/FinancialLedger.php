<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class FinancialLedger extends Model
{
    use SoftDeletes;

    public const TYPE_LIABILITY = 'liability';
    public const TYPE_RECEIVABLE = 'receivable';

    public const STATUS_OPEN = 'open';
    public const STATUS_CLOSED = 'closed';
    public const STATUS_ARCHIVED = 'archived';

    protected $fillable = [
        'ledger_code',
        'ledger_type',
        'status',
        'purchase_order_id',
        'credit_sale_id',
        'vendor_id',
        'vendor_name',
        'vendor_phone',
        'contact_first_name',
        'contact_last_name',
        'contact_phone',
        'contact_email',
        'currency',
        'principal_amount',
        'outstanding_amount',
        'paid_amount',
        'opened_at',
        'closed_at',
        'archived_at',
        'last_reminder_sent_at',
        'next_reminder_due_at',
        'notes',
    ];

    protected $casts = [
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
        'archived_at' => 'datetime',
        'last_reminder_sent_at' => 'datetime',
        'next_reminder_due_at' => 'datetime',
        'principal_amount' => 'decimal:2',
        'outstanding_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $ledger): void {
            if (! $ledger->ledger_code) {
                $ledger->ledger_code = self::generateCode();
            }

            if (! $ledger->opened_at) {
                $ledger->opened_at = Carbon::now();
            }

            if (! $ledger->status) {
                $ledger->status = self::STATUS_OPEN;
            }

            if (! $ledger->currency) {
                $ledger->currency = config('app.currency', 'TZS');
            }

            if (! $ledger->next_reminder_due_at) {
                $ledger->next_reminder_due_at = Carbon::now()->addDays(7);
            }
        });

        static::saved(function (self $ledger): void {
            $ledger->syncPurchaseOrderCreditStatus();
        });

        static::deleted(function (self $ledger): void {
            $ledger->syncPurchaseOrderCreditStatus();
        });
    }

    public function source(): MorphTo
    {
        return $this->morphTo();
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function creditSale(): BelongsTo
    {
        return $this->belongsTo(CreditSale::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(FinancialLedgerPayment::class);
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_OPEN);
    }

    public function scopeOutstanding(Builder $query): Builder
    {
        return $query->where('outstanding_amount', '>', 0)->whereNull('archived_at');
    }

    public function registerPayment(float $amount, ?Carbon $paidAt = null, array $payload = []): FinancialLedgerPayment
    {
        $payment = $this->payments()->create([
            'amount' => $amount,
            'paid_at' => $paidAt ?? Carbon::now(),
            'payment_method' => $payload['payment_method'] ?? null,
            'reference' => $payload['reference'] ?? null,
            'notes' => $payload['notes'] ?? null,
            'recorded_by' => $payload['recorded_by'] ?? null,
        ]);

        $this->refreshFinancials();

        return $payment;
    }

    public function refreshFinancials(): void
    {
        $paid = (float) $this->payments()->sum('amount');
        $this->paid_amount = $paid;
        $this->outstanding_amount = max(0, (float) $this->principal_amount - $paid);

        if ($this->outstanding_amount <= 0 && $this->status !== self::STATUS_CLOSED) {
            $this->markClosed();
        } else {
            $this->saveQuietly();
            $this->syncPurchaseOrderCreditStatus();
        }
    }

    public function markClosed(?Carbon $closedAt = null): void
    {
        $this->status = self::STATUS_CLOSED;
        $this->outstanding_amount = 0;
        $this->closed_at = $closedAt ?? Carbon::now();
        $this->next_reminder_due_at = null;
        $this->saveQuietly();

        $this->syncPurchaseOrderCreditStatus();
    }

    public function reopen(): void
    {
        $this->status = self::STATUS_OPEN;
        $this->closed_at = null;
        $this->next_reminder_due_at = Carbon::now()->addDays(7);
        $this->saveQuietly();

        $this->syncPurchaseOrderCreditStatus();
    }

    public function archive(): void
    {
        $this->archived_at = Carbon::now();
        $this->status = self::STATUS_ARCHIVED;
        $this->saveQuietly();
    }

    public function syncPurchaseOrderCreditStatus(): void
    {
        if (! $this->purchase_order_id) {
            return;
        }

        $purchaseOrder = $this->purchaseOrder;

        if (! $purchaseOrder) {
            return;
        }

        $hasCredit = $purchaseOrder->creditLedgers()->whereNull('archived_at')->exists();
        $outstanding = $purchaseOrder->creditLedgers()->whereNull('archived_at')->sum('outstanding_amount');

        $purchaseOrder->forceFill([
            'has_credit_items' => $hasCredit,
            'credit_outstanding_amount' => $outstanding,
            'credit_closed_at' => $outstanding <= 0 ? Carbon::now() : null,
        ])->saveQuietly();

        if ($outstanding <= 0 && $purchaseOrder->workflow_status !== PurchaseOrder::WORKFLOW_COMPLETED) {
            $purchaseOrder->forceFill([
                'workflow_status' => PurchaseOrder::WORKFLOW_COMPLETED,
            ])->saveQuietly();
        }
    }

    public static function generateCode(): string
    {
        $prefix = 'LED-' . now()->format('Ymd');
        $latest = self::withTrashed()
            ->where('ledger_code', 'like', $prefix . '%')
            ->orderByDesc('ledger_code')
            ->value('ledger_code');

        $nextSequence = '0001';

        if ($latest) {
            $sequence = (int) Str::afterLast($latest, '-') + 1;
            $nextSequence = str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
        }

        return $prefix . '-' . $nextSequence;
    }
}
