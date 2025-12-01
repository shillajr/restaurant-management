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
            if (! Schema::hasColumn('entity_notification_settings', 'user_onboarding_sms_enabled')) {
                if ($isSqlite) {
                    $table->boolean('user_onboarding_sms_enabled')->default(false);
                } else {
                    $table->boolean('user_onboarding_sms_enabled')->default(false)->after('requisition_approved_templates');
                }
            }

            if (! Schema::hasColumn('entity_notification_settings', 'user_onboarding_sms_template')) {
                if ($isSqlite) {
                    $table->text('user_onboarding_sms_template')->nullable();
                } else {
                    $table->text('user_onboarding_sms_template')->nullable()->after('user_onboarding_sms_enabled');
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('entity_notification_settings', function (Blueprint $table) {
            if (Schema::hasColumn('entity_notification_settings', 'user_onboarding_sms_template')) {
                $table->dropColumn('user_onboarding_sms_template');
            }

            if (Schema::hasColumn('entity_notification_settings', 'user_onboarding_sms_enabled')) {
                $table->dropColumn('user_onboarding_sms_enabled');
            }
        });
    }
};
