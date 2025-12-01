<?php

namespace App\Models;

// use Illuminate.Contracts.Auth.MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Permission\Traits\HasRoles;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'entity_id',
        'name',
        'email',
        'phone',
        'password',
        'monthly_salary',
        'daily_rate',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'monthly_salary' => 'decimal:2',
            'daily_rate' => 'decimal:2',
        ];
    }

    public function entity(): BelongsTo
    {
        return $this->belongsTo(Entity::class);
    }

    /**
     * Boot method to automatically calculate daily rate.
     */
    protected static function booted()
    {
        static::saving(function ($user) {
            if ($user->isDirty('monthly_salary')) {
                $user->daily_rate = $user->monthly_salary / 30;
            }
        });
    }

    /**
     * Get all payrolls for this employee.
     */
    public function payrolls()
    {
        return $this->hasMany(Payroll::class, 'employee_id');
    }

    /**
     * Get all payroll payments through payrolls.
     */
    public function payrollPayments()
    {
        return $this->hasManyThrough(PayrollPayment::class, Payroll::class, 'employee_id', 'payroll_id');
    }

    /**
     * Get the latest payroll for this employee.
     */
    public function latestPayroll()
    {
        return $this->hasOne(Payroll::class, 'employee_id')->latestOfMany('month');
    }

    /**
     * Get all loans for this employee.
     */
    public function loans()
    {
        return $this->hasMany(EmployeeLoan::class, 'employee_id');
    }

    /**
     * Get active loans for this employee.
     */
    public function activeLoans()
    {
        return $this->hasMany(EmployeeLoan::class, 'employee_id')->where('status', 'active')->where('balance', '>', 0);
    }

    /**
     * Get total outstanding balance across all payrolls.
     */
    public function getTotalOutstandingBalanceAttribute()
    {
        return $this->payrolls()->sum('outstanding_balance');
    }

    /**
     * Get total active loan balance.
     */
    public function getTotalActiveLoanBalanceAttribute()
    {
        return $this->activeLoans()->sum('balance');
    }
}
