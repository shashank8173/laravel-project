<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('posh_settings', function (Blueprint $table) {
            $table->id();
            $table->string('guidelines_title')->default('POSH Policy');
            $table->text('guidelines_intro')->nullable();
            $table->string('committee_title')->default('Internal Complaints Committee (ICC)');
            $table->text('committee_intro')->nullable();
            $table->text('committee_footer')->nullable();
            $table->string('contact_email')->nullable();
            $table->timestamps();
        });

        Schema::create('posh_guideline_sections', function (Blueprint $table) {
            $table->id();
            $table->string('heading');
            $table->longText('body');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('posh_committee_members', function (Blueprint $table) {
            $table->id();
            $table->string('role_title');
            $table->string('name');
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('notes')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('posh_committee_members');
        Schema::dropIfExists('posh_guideline_sections');
        Schema::dropIfExists('posh_settings');
    }
};
