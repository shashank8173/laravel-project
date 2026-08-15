<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hrm_call_logs', function (Blueprint $table) {
            $table->id();
            $table->string('call_uuid', 64)->unique();
            $table->unsignedBigInteger('caller_id');
            $table->unsignedBigInteger('receiver_id');
            $table->string('call_type', 16); // audio|video
            $table->string('status', 24)->default('calling');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('answered_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->unsignedInteger('duration_seconds')->default(0);
            $table->timestamps();

            $table->index(['caller_id', 'status']);
            $table->index(['receiver_id', 'status']);
        });

        Schema::create('hrm_call_signals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('call_id');
            $table->unsignedBigInteger('sender_id');
            $table->unsignedBigInteger('receiver_id');
            $table->string('type', 32); // offer|answer|ice|hangup|reject|busy|ringing
            $table->mediumText('payload')->nullable();
            $table->timestamp('consumed_at')->nullable();
            $table->timestamps();

            $table->index(['receiver_id', 'consumed_at']);
            $table->index(['call_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hrm_call_signals');
        Schema::dropIfExists('hrm_call_logs');
    }
};
