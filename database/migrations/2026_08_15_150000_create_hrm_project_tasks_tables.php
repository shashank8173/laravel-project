<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('hrm_project_tasks')) {
            Schema::create('hrm_project_tasks', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('project_id');
                $table->string('title', 255);
                $table->text('description')->nullable();
                $table->unsignedBigInteger('assigned_to');
                $table->unsignedBigInteger('assigned_by');
                $table->date('due_date')->nullable();
                $table->string('priority', 20)->default('medium'); // low, medium, high, urgent
                $table->string('status', 30)->default('pending'); // pending, working, in_progress, completed, cancelled
                $table->unsignedTinyInteger('progress')->default(0);
                $table->timestamps();

                $table->index('project_id');
                $table->index('assigned_to');
                $table->index('assigned_by');
                $table->index('status');
                $table->index('due_date');
            });
        }

        if (! Schema::hasTable('hrm_project_task_notes')) {
            Schema::create('hrm_project_task_notes', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('task_id');
                $table->unsignedBigInteger('project_id');
                $table->unsignedBigInteger('employee_id');
                $table->date('note_date');
                $table->time('start_time')->nullable();
                $table->time('end_time')->nullable();
                $table->unsignedInteger('duration_minutes')->nullable();
                $table->text('note');
                $table->string('work_status', 30)->nullable(); // pending, working, in_progress, completed
                $table->timestamps();

                $table->index(['task_id', 'note_date']);
                $table->index('project_id');
                $table->index('employee_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('hrm_project_task_notes');
        Schema::dropIfExists('hrm_project_tasks');
    }
};
