<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $isSqlite = Schema::getConnection()->getDriverName() === 'sqlite';

        Schema::table('entity_notification_settings', function (Blueprint $table) use ($isSqlite) {
            if (! Schema::hasColumn('entity_notification_settings', 'credit_ledger_vendor_sms_template')) {
                if ($isSqlite) {
                    $table->text('credit_ledger_vendor_sms_template')->nullable();
                } else {
                    $table->text('credit_ledger_vendor_sms_template')->nullable()->after('user_onboarding_sms_template');
                }
            }

            if (! Schema::hasColumn('entity_notification_settings', 'credit_ledger_vendor_email_template')) {
                if ($isSqlite) {
                    $table->longText('credit_ledger_vendor_email_template')->nullable();
                } else {
                    $table->longText('credit_ledger_vendor_email_template')->nullable()->after('credit_ledger_vendor_sms_template');
                }
            }

            if (! Schema::hasColumn('entity_notification_settings', 'credit_ledger_customer_sms_template')) {
                if ($isSqlite) {
                    $table->text('credit_ledger_customer_sms_template')->nullable();
                } else {
                    $table->text('credit_ledger_customer_sms_template')->nullable()->after('credit_ledger_vendor_email_template');
                }
            }

            if (! Schema::hasColumn('entity_notification_settings', 'credit_ledger_customer_email_template')) {
                if ($isSqlite) {
                    $table->longText('credit_ledger_customer_email_template')->nullable();
                } else {
                    $table->longText('credit_ledger_customer_email_template')->nullable()->after('credit_ledger_customer_sms_template');
                }
            }

            if (! Schema::hasColumn('entity_notification_settings', 'credit_ledger_internal_email_template')) {
                if ($isSqlite) {
                    $table->longText('credit_ledger_internal_email_template')->nullable();
                } else {
                    $table->longText('credit_ledger_internal_email_template')->nullable()->after('credit_ledger_customer_email_template');
                }
            }
        });

        $vendorSms = 'Reminder: Purchase order :po_number has :amount outstanding. Kindly arrange settlement with :business_name.';
        $vendorEmail = <<<HTML
<p>Hello,</p>
<p>This is a friendly reminder that purchase order <strong>:po_number</strong> has an outstanding balance of <strong>:amount</strong>.</p>
<p>Please contact our purchasing team at :business_phone if you have any questions.</p>
<p>Thank you,<br>:business_name</p>
HTML;
        $customerSms = 'Reminder: Your account has an outstanding balance of :amount with :business_name. Please arrange payment.';
        $customerEmail = <<<HTML
<p>Hello :customer_name,</p>
<p>This is a reminder that your account with <strong>:business_name</strong> has an outstanding balance of <strong>:amount</strong>.</p>
<p>Please complete payment at your earliest convenience or reach us at :business_phone for assistance.</p>
<p>Thank you,<br>:business_name</p>
HTML;
        $internalEmail = <<<HTML
<p>Heads up team,</p>
<p>The ledger <strong>:ledger_code</strong> still has an outstanding balance of <strong>:amount</strong>.</p>
<p>Please follow up with the counterparty before the next reminder cycle.</p>
HTML;

        DB::table('entity_notification_settings')->update([
            'credit_ledger_vendor_sms_template' => $vendorSms,
            'credit_ledger_vendor_email_template' => $vendorEmail,
            'credit_ledger_customer_sms_template' => $customerSms,
            'credit_ledger_customer_email_template' => $customerEmail,
            'credit_ledger_internal_email_template' => $internalEmail,
        ]);
    }

    public function down(): void
    {
        Schema::table('entity_notification_settings', function (Blueprint $table) {
            $columns = [
                'credit_ledger_internal_email_template',
                'credit_ledger_customer_email_template',
                'credit_ledger_customer_sms_template',
                'credit_ledger_vendor_email_template',
                'credit_ledger_vendor_sms_template',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('entity_notification_settings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
