<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('payrolls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('users')->onDelete('cascade');
            $table->date('month'); // First day of the month
            $table->decimal('monthly_salary', 10, 2); // Snapshot of employee's salary
            $table->integer('total_absent_days')->default(0);
            $table->decimal('absent_days_deduction', 10, 2)->default(0);
            $table->decimal('base_salary_payable', 10, 2); // monthly_salary - absent_days_deduction
            $table->decimal('loan_deductions', 10, 2)->default(0); // Loan repayments deducted
            $table->decimal('previous_debt', 10, 2)->default(0); // Outstanding from previous cycles
            $table->decimal('total_due', 10, 2); // base_salary_payable + previous_debt - loan_deductions
            $table->decimal('total_paid', 10, 2)->default(0); // Sum of all payments
            $table->decimal('outstanding_balance', 10, 2); // total_due - total_paid
            $table->enum('status', ['pending', 'partial', 'paid'])->default('pending');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            // Ensure one payroll per employee per month
            $table->unique(['employee_id', 'month']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payrolls');
    }
};
