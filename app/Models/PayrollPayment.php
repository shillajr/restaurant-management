<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class PayrollPayment extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'payroll_id',
        'amount',
        'payment_date',
        'payment_method',
        'payment_reference',
        'notes',
        'notification_sent',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'date',
        'notification_sent' => 'boolean',
    ];

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
     * Get the payroll that owns this payment.
     */
    public function payroll()
    {
        return $this->belongsTo(Payroll::class);
    }

    /**
     * Get the user who created this payment.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Boot method to update payroll totals when payment is saved.
     */
    protected static function booted()
    {
        static::created(function ($payment) {
            $payment->payroll->updatePaymentTotals();
        });

        static::updated(function ($payment) {
            $payment->payroll->updatePaymentTotals();
        });

        static::deleted(function ($payment) {
            $payment->payroll->updatePaymentTotals();
        });
    }
}
