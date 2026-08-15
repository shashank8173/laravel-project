<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hrm_employee', function (Blueprint $table) {
            if (! Schema::hasColumn('hrm_employee', 'ui_theme')) {
                $table->string('ui_theme', 20)->default('light')->after('role');
            }
        });
    }

    public function down(): void
    {
        Schema::table('hrm_employee', function (Blueprint $table) {
            if (Schema::hasColumn('hrm_employee', 'ui_theme')) {
                $table->dropColumn('ui_theme');
            }
        });
    }
};
