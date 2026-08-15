<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hrm_greeting_settings', function (Blueprint $table) {
            $table->id();
            $table->boolean('birthday_enabled')->default(true);
            $table->string('birthday_subject')->default('Happy Birthday!');
            $table->string('birthday_heading')->default('Happy Birthday, {name}!');
            $table->text('birthday_message')->nullable();
            $table->string('birthday_footer')->default('Best wishes from the HR Team!');
            $table->string('birthday_alert_subject')->default('Birthday Alert!');
            $table->string('birthday_alert_heading')->default('Birthday Alert!');
            $table->text('birthday_alert_message')->nullable();

            $table->boolean('anniversary_enabled')->default(true);
            $table->string('anniversary_subject')->default('Happy Work Anniversary!');
            $table->string('anniversary_heading')->default('Congratulations, {name}!');
            $table->text('anniversary_message')->nullable();
            $table->string('anniversary_footer')->default('Best regards, HR Team');
            $table->string('anniversary_alert_subject')->default('Work Anniversary Alert!');
            $table->string('anniversary_alert_heading')->default('Work Anniversary Alert!');
            $table->text('anniversary_alert_message')->nullable();

            $table->timestamps();
        });

        Schema::create('hrm_greeting_images', function (Blueprint $table) {
            $table->id();
            $table->string('type', 30); // birthday | anniversary
            $table->string('title')->nullable();
            $table->string('image_path');
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['type', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hrm_greeting_images');
        Schema::dropIfExists('hrm_greeting_settings');
    }
};
