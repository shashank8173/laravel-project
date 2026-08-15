<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hrm_api_tokens', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('token_prefix', 16);
            $table->string('token_hash', 64)->unique();
            $table->json('abilities')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();

            $table->index('token_prefix');
            $table->index('created_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hrm_api_tokens');
    }
};
