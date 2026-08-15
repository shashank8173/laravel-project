<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hrm_call_logs', function (Blueprint $table) {
            if (! Schema::hasColumn('hrm_call_logs', 'scope')) {
                $table->string('scope', 16)->default('dm')->after('call_uuid');
            }
            if (! Schema::hasColumn('hrm_call_logs', 'group_id')) {
                $table->unsignedBigInteger('group_id')->nullable()->after('receiver_id');
            }
        });

        // Allow null receiver for group room calls (MySQL)
        try {
            DB::statement('ALTER TABLE hrm_call_logs MODIFY receiver_id BIGINT UNSIGNED NULL');
        } catch (\Throwable) {
            // Ignore if already nullable / unsupported
        }

        Schema::create('hrm_call_participants', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('call_id');
            $table->unsignedBigInteger('user_id');
            $table->string('role', 16)->default('member'); // host|member
            $table->string('status', 16)->default('invited'); // invited|ringing|joined|left|rejected|missed
            $table->timestamp('joined_at')->nullable();
            $table->timestamp('left_at')->nullable();
            $table->timestamps();

            $table->unique(['call_id', 'user_id']);
            $table->index(['user_id', 'status']);
            $table->index(['call_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hrm_call_participants');

        Schema::table('hrm_call_logs', function (Blueprint $table) {
            if (Schema::hasColumn('hrm_call_logs', 'group_id')) {
                $table->dropColumn('group_id');
            }
            if (Schema::hasColumn('hrm_call_logs', 'scope')) {
                $table->dropColumn('scope');
            }
        });
    }
};
