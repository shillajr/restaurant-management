<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('credit_sales', function (Blueprint $table) {
            $table->id();
            $table->string('sale_code')->unique();
            $table->date('sale_date')->nullable();
            $table->string('currency', 3)->default(config('app.currency', 'TZS'));
            $table->decimal('total_amount', 12, 2);
            $table->string('customer_first_name');
            $table->string('customer_last_name');
            $table->string('customer_phone')->nullable();
            $table->string('customer_email')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('financial_ledgers', function (Blueprint $table) {
            $table->id();
            $table->string('ledger_code')->unique();
            $table->string('ledger_type');
            $table->string('status')->default('open');
            $table->morphs('source');
            $table->foreignId('purchase_order_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('credit_sale_id')->nullable()->constrained('credit_sales')->nullOnDelete();
            $table->foreignId('vendor_id')->nullable()->constrained()->nullOnDelete();
            $table->string('vendor_name')->nullable();
            $table->string('vendor_phone')->nullable();
            $table->string('contact_first_name')->nullable();
            $table->string('contact_last_name')->nullable();
            $table->string('contact_phone')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('currency', 3)->default(config('app.currency', 'TZS'));
            $table->decimal('principal_amount', 12, 2);
            $table->decimal('outstanding_amount', 12, 2);
            $table->decimal('paid_amount', 12, 2)->default(0);
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->timestamp('last_reminder_sent_at')->nullable();
            $table->timestamp('next_reminder_due_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('financial_ledger_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('financial_ledger_id')->constrained('financial_ledgers')->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->date('paid_at');
            $table->string('payment_method')->nullable();
            $table->string('reference')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        $isSqlite = Schema::getConnection()->getDriverName() === 'sqlite';

        Schema::table('purchase_orders', function (Blueprint $table) use ($isSqlite) {
            if (! Schema::hasColumn('purchase_orders', 'has_credit_items')) {
                $column = $table->boolean('has_credit_items')->default(false);

                if (! $isSqlite) {
                    $column->after('workflow_status');
                }
            }

            if (! Schema::hasColumn('purchase_orders', 'credit_outstanding_amount')) {
                $column = $table->decimal('credit_outstanding_amount', 12, 2)->default(0);

                if (! $isSqlite) {
                    $column->after('has_credit_items');
                }
            }

            if (! Schema::hasColumn('purchase_orders', 'credit_closed_at')) {
                $column = $table->timestamp('credit_closed_at')->nullable();

                if (! $isSqlite) {
                    $column->after('credit_outstanding_amount');
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            if (Schema::hasColumn('purchase_orders', 'credit_closed_at')) {
                $table->dropColumn('credit_closed_at');
            }

            if (Schema::hasColumn('purchase_orders', 'credit_outstanding_amount')) {
                $table->dropColumn('credit_outstanding_amount');
            }

            if (Schema::hasColumn('purchase_orders', 'has_credit_items')) {
                $table->dropColumn('has_credit_items');
            }
        });

        Schema::dropIfExists('financial_ledger_payments');
        Schema::dropIfExists('financial_ledgers');
        Schema::dropIfExists('credit_sales');
    }
};
