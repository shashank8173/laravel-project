<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hrm_greeting_settings', function (Blueprint $table) {
            $table->boolean('holiday_enabled')->default(true)->after('anniversary_alert_message');
            $table->string('holiday_subject')->default('Happy {holiday}!')->after('holiday_enabled');
            $table->string('holiday_heading')->default('Happy {holiday}!')->after('holiday_subject');
            $table->text('holiday_message')->nullable()->after('holiday_heading');
            $table->string('holiday_footer')->default('Best wishes from the HR Team!')->after('holiday_message');
        });
    }

    public function down(): void
    {
        Schema::table('hrm_greeting_settings', function (Blueprint $table) {
            $table->dropColumn([
                'holiday_enabled',
                'holiday_subject',
                'holiday_heading',
                'holiday_message',
                'holiday_footer',
            ]);
        });
    }
};
