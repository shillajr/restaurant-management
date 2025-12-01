<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class CreditSale extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'sale_code',
        'sale_date',
        'currency',
        'total_amount',
        'customer_first_name',
        'customer_last_name',
        'customer_phone',
        'customer_email',
        'notes',
        'recorded_by',
    ];

    protected $casts = [
        'sale_date' => 'date',
        'total_amount' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $sale): void {
            if (! $sale->sale_code) {
                $sale->sale_code = self::generateCode();
            }

            if (! $sale->currency) {
                $sale->currency = config('app.currency', 'TZS');
            }
        });
    }

    public function ledger(): HasOne
    {
        return $this->hasOne(FinancialLedger::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function getCustomerFullNameAttribute(): string
    {
        return trim($this->customer_first_name . ' ' . $this->customer_last_name);
    }

    public static function generateCode(): string
    {
        $prefix = 'CR-' . now()->format('Ymd');
        $latest = self::withTrashed()
            ->where('sale_code', 'like', $prefix . '%')
            ->orderByDesc('sale_code')
            ->value('sale_code');

        $nextSequence = '0001';

        if ($latest) {
            $sequence = (int) Str::afterLast($latest, '-') + 1;
            $nextSequence = str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
        }

        return $prefix . '-' . $nextSequence;
    }
}
