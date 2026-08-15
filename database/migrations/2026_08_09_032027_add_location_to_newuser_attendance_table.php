<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('newuser_attendance', function (Blueprint $table) {
            if (! Schema::hasColumn('newuser_attendance', 'clock_in_latitude')) {
                $table->decimal('clock_in_latitude', 10, 7)->nullable()->after('clock_in_ip');
            }
            if (! Schema::hasColumn('newuser_attendance', 'clock_in_longitude')) {
                $table->decimal('clock_in_longitude', 10, 7)->nullable()->after('clock_in_latitude');
            }
            if (! Schema::hasColumn('newuser_attendance', 'clock_in_accuracy')) {
                $table->decimal('clock_in_accuracy', 8, 2)->nullable()->after('clock_in_longitude');
            }
            if (! Schema::hasColumn('newuser_attendance', 'clock_out_latitude')) {
                $table->decimal('clock_out_latitude', 10, 7)->nullable()->after('clock_out_ip');
            }
            if (! Schema::hasColumn('newuser_attendance', 'clock_out_longitude')) {
                $table->decimal('clock_out_longitude', 10, 7)->nullable()->after('clock_out_latitude');
            }
            if (! Schema::hasColumn('newuser_attendance', 'clock_out_accuracy')) {
                $table->decimal('clock_out_accuracy', 8, 2)->nullable()->after('clock_out_longitude');
            }
        });
    }

    public function down(): void
    {
        Schema::table('newuser_attendance', function (Blueprint $table) {
            $cols = [
                'clock_in_latitude',
                'clock_in_longitude',
                'clock_in_accuracy',
                'clock_out_latitude',
                'clock_out_longitude',
                'clock_out_accuracy',
            ];
            foreach ($cols as $col) {
                if (Schema::hasColumn('newuser_attendance', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
