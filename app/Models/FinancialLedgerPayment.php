<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class FinancialLedgerPayment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'financial_ledger_id',
        'amount',
        'paid_at',
        'payment_method',
        'reference',
        'notes',
        'recorded_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'date',
    ];

    protected static function booted(): void
    {
        static::saved(function (self $payment): void {
            $payment->ledger?->refreshFinancials();
        });

        static::deleted(function (self $payment): void {
            $payment->ledger?->refreshFinancials();
        });
    }

    public function ledger(): BelongsTo
    {
        return $this->belongsTo(FinancialLedger::class, 'financial_ledger_id');
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
