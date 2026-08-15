<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('hrm_project_daily_notes')) {
            return;
        }

        Schema::create('hrm_project_daily_notes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('project_id');
            $table->unsignedBigInteger('employee_id');
            $table->date('note_date');
            $table->text('note');
            $table->string('work_status', 30)->default('working'); // pending, working, in_progress, completed
            $table->unsignedTinyInteger('progress_percent')->nullable();
            $table->timestamps();

            $table->index(['project_id', 'note_date']);
            $table->index(['project_id', 'employee_id']);
            $table->index('work_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hrm_project_daily_notes');
    }
};
