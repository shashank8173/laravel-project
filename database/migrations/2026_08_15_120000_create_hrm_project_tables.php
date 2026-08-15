<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('hrm_projects')) {
            Schema::create('hrm_projects', function (Blueprint $table) {
                $table->id();
                $table->string('name', 255);
                $table->text('description')->nullable();
                $table->string('client_name', 255)->nullable();
                $table->unsignedBigInteger('project_manager_id');
                $table->date('start_date');
                $table->date('end_date');
                $table->string('priority', 20)->default('medium'); // low, medium, high, urgent
                $table->string('status', 30)->default('planning'); // planning, not_started, in_progress, on_hold, completed, cancelled
                $table->unsignedTinyInteger('progress')->default(0);
                $table->unsignedBigInteger('created_by');
                $table->timestamps();

                $table->index('project_manager_id');
                $table->index('created_by');
                $table->index('status');
                $table->index('priority');
                $table->index('end_date');
                $table->index(['status', 'end_date']);
            });
        }

        if (! Schema::hasTable('hrm_project_employee')) {
            Schema::create('hrm_project_employee', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('project_id');
                $table->unsignedBigInteger('employee_id');
                $table->unsignedBigInteger('assigned_by');
                $table->timestamp('assigned_at')->useCurrent();
                $table->string('status', 20)->default('active'); // active, replaced, removed
                $table->timestamps();

                $table->unique(['project_id', 'employee_id']);
                $table->index('employee_id');
                $table->index('assigned_by');
                $table->index('status');
            });
        }

        if (! Schema::hasTable('hrm_project_assignment_history')) {
            Schema::create('hrm_project_assignment_history', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('project_id');
                $table->unsignedBigInteger('employee_id');
                $table->unsignedBigInteger('previous_employee_id')->nullable();
                $table->unsignedBigInteger('assigned_by');
                $table->string('assignment_type', 40); // initial_assignment, reassignment, manager_assignment, admin_assignment
                $table->text('reason')->nullable();
                $table->string('status', 20)->default('active'); // active, replaced, removed
                $table->string('email_status', 20)->nullable(); // pending, sent, failed, skipped
                $table->text('email_error')->nullable();
                $table->timestamp('assigned_at')->useCurrent();
                $table->timestamps();

                $table->index('project_id');
                $table->index('employee_id');
                $table->index('previous_employee_id');
                $table->index('assigned_by');
                $table->index('assignment_type');
            });
        }

        if (! Schema::hasTable('hrm_project_activities')) {
            Schema::create('hrm_project_activities', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('project_id');
                $table->unsignedBigInteger('actor_id')->nullable();
                $table->string('action', 60);
                $table->json('meta')->nullable();
                $table->timestamp('created_at')->useCurrent();

                $table->index(['project_id', 'created_at']);
                $table->index('actor_id');
                $table->index('action');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('hrm_project_activities');
        Schema::dropIfExists('hrm_project_assignment_history');
        Schema::dropIfExists('hrm_project_employee');
        Schema::dropIfExists('hrm_projects');
    }
};
