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
        Schema::create('employee_loans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('users')->onDelete('cascade');
            $table->decimal('amount', 10, 2); // Original loan amount
            $table->string('purpose')->nullable(); // Reason for loan/advance
            $table->date('issue_date');
            $table->decimal('repayment_per_cycle', 10, 2)->default(0); // Fixed amount to deduct per payroll
            $table->decimal('total_repaid', 10, 2)->default(0); // Total amount repaid so far
            $table->decimal('balance', 10, 2); // Remaining balance (amount - total_repaid)
            $table->enum('status', ['active', 'completed', 'cancelled'])->default('active');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_loans');
    }
};
