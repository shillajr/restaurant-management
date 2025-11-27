<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class EmployeeLoan extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'employee_id',
        'amount',
        'purpose',
        'issue_date',
        'repayment_per_cycle',
        'total_repaid',
        'balance',
        'status',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'issue_date' => 'date',
        'repayment_per_cycle' => 'decimal:2',
        'total_repaid' => 'decimal:2',
        'balance' => 'decimal:2',
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
     * Get the employee that owns the loan.
     */
    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    /**
     * Get the user who created this loan.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Record a repayment for this loan.
     */
    public function recordRepayment($amount)
    {
        $this->total_repaid += $amount;
        $this->balance = $this->amount - $this->total_repaid;
        
        // Update status if fully repaid
        if ($this->balance <= 0) {
            $this->status = 'completed';
            $this->balance = 0; // Ensure no negative balance
        }
        
        $this->save();
    }

    /**
     * Get active loans for an employee.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active')->where('balance', '>', 0);
    }

    /**
     * Get total active loan balance for an employee.
     */
    public static function getTotalActiveBalance($employeeId)
    {
        return static::where('employee_id', $employeeId)
            ->active()
            ->sum('balance');
    }
}
