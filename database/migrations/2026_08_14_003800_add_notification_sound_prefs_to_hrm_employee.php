<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hrm_employee', function (Blueprint $table) {
            if (! Schema::hasColumn('hrm_employee', 'notify_chat_sound')) {
                $table->boolean('notify_chat_sound')->default(true)->after('ui_theme');
            }
            if (! Schema::hasColumn('hrm_employee', 'notify_call_ringtone')) {
                $table->boolean('notify_call_ringtone')->default(true)->after('notify_chat_sound');
            }
        });
    }

    public function down(): void
    {
        Schema::table('hrm_employee', function (Blueprint $table) {
            if (Schema::hasColumn('hrm_employee', 'notify_call_ringtone')) {
                $table->dropColumn('notify_call_ringtone');
            }
            if (Schema::hasColumn('hrm_employee', 'notify_chat_sound')) {
                $table->dropColumn('notify_chat_sound');
            }
        });
    }
};
