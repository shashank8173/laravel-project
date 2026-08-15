<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('hrm_employee_notifications')) {
            Schema::create('hrm_employee_notifications', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('employee_id'); // recipient
                $table->unsignedBigInteger('actor_id')->nullable(); // who triggered
                $table->string('type', 60);
                $table->string('title', 255);
                $table->string('body', 500)->nullable();
                $table->string('link', 500)->nullable();
                $table->timestamp('read_at')->nullable();
                $table->timestamp('created_at')->useCurrent();

                $table->index(['employee_id', 'read_at']);
                $table->index(['employee_id', 'created_at']);
                $table->index('type');
            });
        }

        Schema::table('hrm_employee', function (Blueprint $table) {
            if (! Schema::hasColumn('hrm_employee', 'notify_app_sound')) {
                $table->boolean('notify_app_sound')->default(true)->after('notify_call_ringtone');
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hrm_employee_notifications');

        Schema::table('hrm_employee', function (Blueprint $table) {
            if (Schema::hasColumn('hrm_employee', 'notify_app_sound')) {
                $table->dropColumn('notify_app_sound');
            }
        });
    }
};
