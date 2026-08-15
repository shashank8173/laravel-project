<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hrm_connectors', function (Blueprint $table) {
            $table->id();
            $table->string('type', 40)->unique(); // zoho|workday|salesforce|successfactors
            $table->string('name', 120);
            $table->boolean('enabled')->default(false);
            $table->string('webhook_secret', 80)->nullable();
            $table->text('config')->nullable(); // encrypted JSON
            $table->json('field_map')->nullable();
            $table->timestamp('last_sync_at')->nullable();
            $table->string('last_sync_status', 40)->nullable();
            $table->text('last_sync_message')->nullable();
            $table->timestamps();
        });

        Schema::create('hrm_connector_sync_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('connector_id');
            $table->string('direction', 20)->default('inbound'); // inbound|pull
            $table->string('status', 20); // success|error|partial
            $table->unsignedInteger('created_count')->default(0);
            $table->unsignedInteger('updated_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);
            $table->json('details')->nullable();
            $table->timestamps();

            $table->index('connector_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hrm_connector_sync_logs');
        Schema::dropIfExists('hrm_connectors');
    }
};
