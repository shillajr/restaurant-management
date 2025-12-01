<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $isSqlite = Schema::getConnection()->getDriverName() === 'sqlite';

        Schema::table('entity_notification_settings', function (Blueprint $table) use ($isSqlite) {
            if (! Schema::hasColumn('entity_notification_settings', 'requisition_submitted_notification_phones')) {
                if ($isSqlite) {
                    $table->json('requisition_submitted_notification_phones')->nullable();
                } else {
                    $table->json('requisition_submitted_notification_phones')->nullable()->after('purchase_order_notification_phones');
                }
            }

            if (! Schema::hasColumn('entity_notification_settings', 'requisition_submitted_templates')) {
                if ($isSqlite) {
                    $table->json('requisition_submitted_templates')->nullable();
                } else {
                    $table->json('requisition_submitted_templates')->nullable()->after('requisition_submitted_notification_phones');
                }
            }

            if (! Schema::hasColumn('entity_notification_settings', 'requisition_approved_notification_phones')) {
                if ($isSqlite) {
                    $table->json('requisition_approved_notification_phones')->nullable();
                } else {
                    $table->json('requisition_approved_notification_phones')->nullable()->after('requisition_submitted_templates');
                }
            }

            if (! Schema::hasColumn('entity_notification_settings', 'requisition_approved_templates')) {
                if ($isSqlite) {
                    $table->json('requisition_approved_templates')->nullable();
                } else {
                    $table->json('requisition_approved_templates')->nullable()->after('requisition_approved_notification_phones');
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('entity_notification_settings', function (Blueprint $table) {
            if (Schema::hasColumn('entity_notification_settings', 'requisition_approved_templates')) {
                $table->dropColumn('requisition_approved_templates');
            }

            if (Schema::hasColumn('entity_notification_settings', 'requisition_approved_notification_phones')) {
                $table->dropColumn('requisition_approved_notification_phones');
            }

            if (Schema::hasColumn('entity_notification_settings', 'requisition_submitted_templates')) {
                $table->dropColumn('requisition_submitted_templates');
            }

            if (Schema::hasColumn('entity_notification_settings', 'requisition_submitted_notification_phones')) {
                $table->dropColumn('requisition_submitted_notification_phones');
            }
        });
    }
};
