<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('hrm_project_daily_notes')) {
            return;
        }

        Schema::table('hrm_project_daily_notes', function (Blueprint $table) {
            if (! Schema::hasColumn('hrm_project_daily_notes', 'start_time')) {
                $table->time('start_time')->nullable()->after('note_date');
            }
            if (! Schema::hasColumn('hrm_project_daily_notes', 'end_time')) {
                $table->time('end_time')->nullable()->after('start_time');
            }
            if (! Schema::hasColumn('hrm_project_daily_notes', 'duration_minutes')) {
                $table->unsignedInteger('duration_minutes')->nullable()->after('end_time');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('hrm_project_daily_notes')) {
            return;
        }

        Schema::table('hrm_project_daily_notes', function (Blueprint $table) {
            foreach (['duration_minutes', 'end_time', 'start_time'] as $col) {
                if (Schema::hasColumn('hrm_project_daily_notes', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
