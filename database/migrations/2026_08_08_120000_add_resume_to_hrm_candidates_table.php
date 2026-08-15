<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('hrm_candidates')) {
            return;
        }

        Schema::table('hrm_candidates', function (Blueprint $table) {
            if (! Schema::hasColumn('hrm_candidates', 'resume_path')) {
                $table->string('resume_path', 500)->nullable()->after('notes');
            }
            if (! Schema::hasColumn('hrm_candidates', 'resume_name')) {
                $table->string('resume_name', 255)->nullable()->after('resume_path');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('hrm_candidates')) {
            return;
        }

        Schema::table('hrm_candidates', function (Blueprint $table) {
            if (Schema::hasColumn('hrm_candidates', 'resume_name')) {
                $table->dropColumn('resume_name');
            }
            if (Schema::hasColumn('hrm_candidates', 'resume_path')) {
                $table->dropColumn('resume_path');
            }
        });
    }
};
