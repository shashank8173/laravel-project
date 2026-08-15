<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('hrm_employee', 'remember_token')) {
            return;
        }

        $modes = DB::select('SELECT @@SESSION.sql_mode AS m');
        $previousMode = $modes[0]->m ?? '';
        DB::statement("SET SESSION sql_mode=''");
        try {
            Schema::table('hrm_employee', function (Blueprint $table) {
                $table->rememberToken()->nullable()->after('password');
            });
        } finally {
            DB::statement('SET SESSION sql_mode=?', [$previousMode]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('hrm_employee', 'remember_token')) {
            return;
        }

        $modes = DB::select('SELECT @@SESSION.sql_mode AS m');
        $previousMode = $modes[0]->m ?? '';
        DB::statement("SET SESSION sql_mode=''");
        try {
            Schema::table('hrm_employee', function (Blueprint $table) {
                $table->dropColumn('remember_token');
            });
        } finally {
            DB::statement('SET SESSION sql_mode=?', [$previousMode]);
        }
    }
};
