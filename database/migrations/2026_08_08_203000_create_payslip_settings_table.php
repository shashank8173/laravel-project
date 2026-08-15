<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payslip_settings', function (Blueprint $table) {
            $table->id();
            $table->string('company_name')->default('Expetize Private Limited');
            $table->text('company_address')->nullable();
            $table->string('cin', 64)->nullable();
            $table->string('location', 120)->nullable()->default('Delhi');
            $table->string('logo_path')->nullable();
            $table->string('signature_path')->nullable();
            $table->string('signatory_name', 120)->nullable()->default('Authorized Signatory');
            $table->text('footer_note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payslip_settings');
    }
};
