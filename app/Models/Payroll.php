<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Payroll extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'employee_id',
        'month',
        'monthly_salary',
        'total_absent_days',
        'absent_days_deduction',
        'base_salary_payable',
        'loan_deductions',
        'previous_debt',
        'total_due',
        'total_paid',
        'outstanding_balance',
        'status',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'month' => 'date',
        'monthly_salary' => 'decimal:2',
        'absent_days_deduction' => 'decimal:2',
        'base_salary_payable' => 'decimal:2',
        'loan_deductions' => 'decimal:2',
        'previous_debt' => 'decimal:2',
        'total_due' => 'decimal:2',
        'total_paid' => 'decimal:2',
        'outstanding_balance' => 'decimal:2',
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
     * Get the employee that owns the payroll.
     */
    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    /**
     * Get the user who created this payroll.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get all payments for this payroll.
     */
    public function payments()
    {
        return $this->hasMany(PayrollPayment::class);
    }

    /**
     * Update payment totals based on payments.
     */
    public function updatePaymentTotals()
    {
        $this->total_paid = $this->payments()->sum('amount');
        $this->outstanding_balance = $this->total_due - $this->total_paid;
        
        // Update status
        if ($this->outstanding_balance <= 0) {
            $this->status = 'paid';
        } elseif ($this->total_paid > 0) {
            $this->status = 'partial';
        } else {
            $this->status = 'pending';
        }
        
        $this->save();
    }

    /**
     * Calculate the daily rate for this payroll.
     */
    public function calculateDailyRate()
    {
        return $this->monthly_salary / 30;
    }

    /**
     * Scope to filter by month.
     */
    public function scopeForMonth($query, $month)
    {
        return $query->whereYear('month', '=', date('Y', strtotime($month)))
                     ->whereMonth('month', '=', date('m', strtotime($month)));
    }

    /**
     * Scope to filter by status.
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }
}
