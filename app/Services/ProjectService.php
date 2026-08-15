<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\Project;
use App\Models\ProjectActivity;
use App\Models\ProjectAssignmentHistory;
use App\Models\ProjectEmployee;
use App\Models\ReportingManager;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProjectService
{
    public function __construct(private HrmMailer $mailer) {}

    public function logActivity(Project $project, ?Employee $actor, string $action, array $meta = []): ProjectActivity
    {
        return ProjectActivity::query()->create([
            'project_id' => $project->id,
            'actor_id' => $actor?->id,
            'action' => $action,
            'meta' => $meta ?: null,
            'created_at' => now(),
        ]);
    }

    /**
     * Primary reporting manager of an employee (existing HRM relationship).
     */
    public function primaryManagerOf(Employee $employee): ?Employee
    {
        $row = ReportingManager::query()
            ->where('employee_id', $employee->id)
            ->where('reporting_manager_type', 'Primary')
            ->with('manager')
            ->first();

        if ($row?->manager) {
            return $row->manager;
        }

        $fallback = ReportingManager::query()
            ->where('employee_id', $employee->id)
            ->with('manager')
            ->orderBy('id')
            ->first();

        return $fallback?->manager;
    }

    public function resolveAssignmentType(Employee $actor): string
    {
        if ($actor->isAdmin()) {
            return ProjectAssignmentHistory::TYPE_ADMIN;
        }

        if ($actor->isReportingManager()) {
            return ProjectAssignmentHistory::TYPE_MANAGER;
        }

        return ProjectAssignmentHistory::TYPE_INITIAL;
    }

    /**
     * @param  list<int>  $employeeIds
     * @return array{assigned:list<int>, skipped:list<int>}
     */
    public function assignEmployees(Project $project, array $employeeIds, Employee $actor, ?string $reason = null): array
    {
        $employeeIds = collect($employeeIds)
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        $assigned = [];
        $skipped = [];
        $historyIds = [];

        DB::transaction(function () use ($project, $employeeIds, $actor, $reason, &$assigned, &$skipped, &$historyIds) {
            foreach ($employeeIds as $employeeId) {
                $employee = Employee::query()->find($employeeId);
                if (! $employee || ! $employee->isActive()) {
                    $skipped[] = $employeeId;

                    continue;
                }

                $existing = ProjectEmployee::query()
                    ->where('project_id', $project->id)
                    ->where('employee_id', $employeeId)
                    ->first();

                if ($existing && $existing->status === 'active') {
                    $skipped[] = $employeeId;

                    continue;
                }

                if ($existing) {
                    $existing->update([
                        'assigned_by' => $actor->id,
                        'assigned_at' => now(),
                        'status' => 'active',
                    ]);
                } else {
                    ProjectEmployee::query()->create([
                        'project_id' => $project->id,
                        'employee_id' => $employeeId,
                        'assigned_by' => $actor->id,
                        'assigned_at' => now(),
                        'status' => 'active',
                    ]);
                }

                $type = $this->resolveAssignmentType($actor);
                $history = ProjectAssignmentHistory::query()->create([
                    'project_id' => $project->id,
                    'employee_id' => $employeeId,
                    'previous_employee_id' => null,
                    'assigned_by' => $actor->id,
                    'assignment_type' => $type,
                    'reason' => $reason,
                    'status' => 'active',
                    'email_status' => 'pending',
                    'assigned_at' => now(),
                ]);

                $this->logActivity($project, $actor, 'assigned', [
                    'employee_id' => $employeeId,
                    'employee_name' => $employee->full_name,
                    'assignment_type' => $type,
                    'reason' => $reason,
                ]);

                app(EmployeeNotificationService::class)->notify(
                    $employee,
                    'project_assigned',
                    'Project assigned · '.$project->name,
                    'Assigned by '.($actor->full_name ?: 'Admin'),
                    route('projects.show', $project),
                    $actor
                );

                $assigned[] = $employeeId;
                $historyIds[] = $history->id;
            }
        });

        foreach ($historyIds as $historyId) {
            $history = ProjectAssignmentHistory::query()->with(['employee', 'assigner', 'project.manager'])->find($historyId);
            if ($history) {
                $this->sendAssignmentEmail($history);
            }
        }

        return ['assigned' => $assigned, 'skipped' => $skipped];
    }

    /**
     * Reassign an active member's responsibility to another employee. History is never overwritten.
     */
    public function reassign(Project $project, Employee $from, Employee $to, Employee $actor, string $reason): ProjectAssignmentHistory
    {
        $history = DB::transaction(function () use ($project, $from, $to, $actor, $reason) {
            $membership = ProjectEmployee::query()
                ->where('project_id', $project->id)
                ->where('employee_id', $from->id)
                ->where('status', 'active')
                ->firstOrFail();

            $membership->update(['status' => 'replaced']);

            ProjectAssignmentHistory::query()
                ->where('project_id', $project->id)
                ->where('employee_id', $from->id)
                ->where('status', 'active')
                ->update(['status' => 'replaced']);

            $targetMembership = ProjectEmployee::query()
                ->where('project_id', $project->id)
                ->where('employee_id', $to->id)
                ->first();

            if ($targetMembership && $targetMembership->status === 'active') {
                throw new \RuntimeException('Target employee is already assigned to this project.');
            }

            if ($targetMembership) {
                $targetMembership->update([
                    'assigned_by' => $actor->id,
                    'assigned_at' => now(),
                    'status' => 'active',
                ]);
            } else {
                ProjectEmployee::query()->create([
                    'project_id' => $project->id,
                    'employee_id' => $to->id,
                    'assigned_by' => $actor->id,
                    'assigned_at' => now(),
                    'status' => 'active',
                ]);
            }

            $history = ProjectAssignmentHistory::query()->create([
                'project_id' => $project->id,
                'employee_id' => $to->id,
                'previous_employee_id' => $from->id,
                'assigned_by' => $actor->id,
                'assignment_type' => ProjectAssignmentHistory::TYPE_REASSIGNMENT,
                'reason' => $reason,
                'status' => 'active',
                'email_status' => 'pending',
                'assigned_at' => now(),
            ]);

            $this->logActivity($project, $actor, 'reassigned', [
                'from_employee_id' => $from->id,
                'from_employee_name' => $from->full_name,
                'to_employee_id' => $to->id,
                'to_employee_name' => $to->full_name,
                'reason' => $reason,
            ]);

            app(EmployeeNotificationService::class)->notify(
                $to,
                'project_reassigned',
                'Project reassigned · '.$project->name,
                'From '.$from->full_name.' · by '.($actor->full_name ?: 'User'),
                route('projects.show', $project),
                $actor
            );

            if ((int) $from->id !== (int) $actor->id) {
                app(EmployeeNotificationService::class)->notify(
                    $from,
                    'project_reassigned_out',
                    'You were reassigned off · '.$project->name,
                    'Moved to '.$to->full_name,
                    route('projects.show', $project),
                    $actor
                );
            }

            return $history;
        });

        $history->load(['employee', 'previousEmployee', 'assigner', 'project.manager']);
        $this->sendReassignmentEmail($history);

        return $history;
    }

    public function removeMember(Project $project, Employee $employee, Employee $actor, ?string $reason = null): void
    {
        DB::transaction(function () use ($project, $employee, $actor, $reason) {
            $membership = ProjectEmployee::query()
                ->where('project_id', $project->id)
                ->where('employee_id', $employee->id)
                ->where('status', 'active')
                ->firstOrFail();

            $membership->update(['status' => 'removed']);

            ProjectAssignmentHistory::query()
                ->where('project_id', $project->id)
                ->where('employee_id', $employee->id)
                ->where('status', 'active')
                ->update(['status' => 'removed']);

            $this->logActivity($project, $actor, 'member_removed', [
                'employee_id' => $employee->id,
                'employee_name' => $employee->full_name,
                'reason' => $reason,
            ]);
        });
    }

    public function updateStatus(Project $project, string $status, Employee $actor): void
    {
        $previous = $project->status;
        if ($previous === $status) {
            return;
        }

        DB::transaction(function () use ($project, $status, $actor, $previous) {
            $project->update(['status' => $status]);
            $this->logActivity($project, $actor, 'status_changed', [
                'from' => $previous,
                'to' => $status,
            ]);
        });
    }

    public function updateProgress(Project $project, int $progress, Employee $actor): void
    {
        $previous = (int) $project->progress;
        if ($previous === $progress) {
            return;
        }

        DB::transaction(function () use ($project, $progress, $actor, $previous) {
            $project->update(['progress' => $progress]);
            $this->logActivity($project, $actor, 'progress_changed', [
                'from' => $previous,
                'to' => $progress,
            ]);
        });
    }

    /**
     * @param  list<string>  $emails
     * @return list<string>
     */
    private function uniqueEmails(array $emails, ?string $excludeTo = null): array
    {
        $clean = [];
        foreach ($emails as $email) {
            $email = strtolower(trim((string) $email));
            if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                continue;
            }
            if ($excludeTo && strcasecmp($email, $excludeTo) === 0) {
                continue;
            }
            $clean[$email] = $email;
        }

        return array_values($clean);
    }

    public function sendAssignmentEmail(ProjectAssignmentHistory $history): void
    {
        $project = $history->project ?? Project::query()->find($history->project_id);
        $employee = $history->employee ?? Employee::query()->find($history->employee_id);
        $assigner = $history->assigner ?? Employee::query()->find($history->assigned_by);

        if (! $project || ! $employee) {
            return;
        }

        $to = $employee->officialEmail();
        if (! $to) {
            $history->update([
                'email_status' => 'skipped',
                'email_error' => 'Assignee has no official email',
            ]);
            $this->logActivity($project, $assigner, 'email_failed', [
                'employee_id' => $employee->id,
                'employee_name' => $employee->full_name,
                'error' => 'No official email',
                'context' => 'assignment',
            ]);

            return;
        }

        $manager = $this->primaryManagerOf($employee);
        $cc = $this->uniqueEmails([
            $manager?->officialEmail() ?? '',
            $project->manager?->officialEmail() ?? '',
            $assigner?->officialEmail() ?? '',
        ], $to);

        $subject = 'New Project Assigned: '.$project->name;
        $html = $this->buildAssignmentHtml($project, $employee, $assigner);

        try {
            $ok = $this->mailer->send($to, $subject, $html, $cc);
            $history->update([
                'email_status' => $ok ? 'sent' : 'failed',
                'email_error' => $ok ? null : 'Mailer returned false (check SMTP / logs)',
            ]);
            $this->logActivity($project, $assigner, $ok ? 'email_sent' : 'email_failed', [
                'employee_id' => $employee->id,
                'employee_name' => $employee->full_name,
                'to' => $to,
                'cc' => $cc,
                'context' => 'assignment',
                'error' => $ok ? null : 'Mailer returned false',
            ]);
        } catch (Throwable $e) {
            Log::error('Project assignment email failed', [
                'project_id' => $project->id,
                'employee_id' => $employee->id,
                'error' => $e->getMessage(),
            ]);
            $history->update([
                'email_status' => 'failed',
                'email_error' => $e->getMessage(),
            ]);
            $this->logActivity($project, $assigner, 'email_failed', [
                'employee_id' => $employee->id,
                'employee_name' => $employee->full_name,
                'to' => $to,
                'error' => $e->getMessage(),
                'context' => 'assignment',
            ]);
        }
    }

    public function sendReassignmentEmail(ProjectAssignmentHistory $history): void
    {
        $project = $history->project ?? Project::query()->with('manager')->find($history->project_id);
        $newEmployee = $history->employee ?? Employee::query()->find($history->employee_id);
        $previous = $history->previousEmployee ?? Employee::query()->find($history->previous_employee_id);
        $actor = $history->assigner ?? Employee::query()->find($history->assigned_by);

        if (! $project || ! $newEmployee) {
            return;
        }

        $to = $newEmployee->officialEmail();
        if (! $to) {
            $history->update([
                'email_status' => 'skipped',
                'email_error' => 'New assignee has no official email',
            ]);
            $this->logActivity($project, $actor, 'email_failed', [
                'employee_id' => $newEmployee->id,
                'employee_name' => $newEmployee->full_name,
                'error' => 'No official email',
                'context' => 'reassignment',
            ]);

            return;
        }

        $originalManager = $previous ? $this->primaryManagerOf($previous) : null;
        $cc = $this->uniqueEmails([
            $previous?->officialEmail() ?? '',
            $originalManager?->officialEmail() ?? '',
            $project->manager?->officialEmail() ?? '',
        ], $to);

        $subject = 'Project Reassigned: '.$project->name;
        $html = $this->buildReassignmentHtml($project, $previous, $newEmployee, $actor, (string) $history->reason);

        try {
            $ok = $this->mailer->send($to, $subject, $html, $cc);
            $history->update([
                'email_status' => $ok ? 'sent' : 'failed',
                'email_error' => $ok ? null : 'Mailer returned false (check SMTP / logs)',
            ]);
            $this->logActivity($project, $actor, $ok ? 'email_sent' : 'email_failed', [
                'employee_id' => $newEmployee->id,
                'employee_name' => $newEmployee->full_name,
                'to' => $to,
                'cc' => $cc,
                'context' => 'reassignment',
                'error' => $ok ? null : 'Mailer returned false',
            ]);
        } catch (Throwable $e) {
            Log::error('Project reassignment email failed', [
                'project_id' => $project->id,
                'employee_id' => $newEmployee->id,
                'error' => $e->getMessage(),
            ]);
            $history->update([
                'email_status' => 'failed',
                'email_error' => $e->getMessage(),
            ]);
            $this->logActivity($project, $actor, 'email_failed', [
                'employee_id' => $newEmployee->id,
                'employee_name' => $newEmployee->full_name,
                'to' => $to,
                'error' => $e->getMessage(),
                'context' => 'reassignment',
            ]);
        }
    }

    private function buildAssignmentHtml(Project $project, Employee $assignee, ?Employee $assigner): string
    {
        $url = route('projects.show', $project);
        $rows = [
            'Project Name' => e($project->name),
            'Client' => e($project->client_name ?: '—'),
            'Description' => $project->description
                ? strip_tags((string) $project->description, '<p><br><br/><b><strong><i><em><ul><ol><li><u><a>')
                : '—',
            'Project Manager' => e($project->manager?->full_name ?: '—'),
            'Start Date' => e(optional($project->start_date)->format('d M Y') ?: '—'),
            'Deadline' => e(optional($project->end_date)->format('d M Y') ?: '—'),
            'Priority' => e($project->priorityLabel()),
            'Assigned By' => e($assigner?->full_name ?: '—'),
            'Assigned To' => e($assignee->full_name),
        ];

        return $this->wrapEmail('New Project Assigned', $rows, $url, 'View Project');
    }

    private function buildReassignmentHtml(
        Project $project,
        ?Employee $previous,
        Employee $newEmployee,
        ?Employee $actor,
        string $reason
    ): string {
        $url = route('projects.show', $project);
        $rows = [
            'Project' => e($project->name),
            'Previously Assigned To' => e($previous?->full_name ?: '—'),
            'New Assignee' => e($newEmployee->full_name),
            'Reassigned By' => e($actor?->full_name ?: '—'),
            'Reason' => nl2br(e($reason ?: '—')),
            'Deadline' => e(optional($project->end_date)->format('d M Y') ?: '—'),
            'Priority' => e($project->priorityLabel()),
        ];

        return $this->wrapEmail('Project Reassigned', $rows, $url, 'View Project');
    }

    /**
     * @param  array<string, string>  $rows
     */
    private function wrapEmail(string $heading, array $rows, string $url, string $cta): string
    {
        $body = '';
        foreach ($rows as $label => $value) {
            $body .= '<tr><td style="padding:8px 12px;color:#6b7c93;font-size:13px;width:160px;vertical-align:top;">'
                .e($label).'</td><td style="padding:8px 12px;color:#0f2744;font-size:14px;font-weight:600;">'
                .$value.'</td></tr>';
        }

        return '<div style="font-family:Segoe UI,Arial,sans-serif;background:#f4f7fb;padding:24px;">'
            .'<div style="max-width:640px;margin:0 auto;background:#fff;border-radius:16px;overflow:hidden;border:1px solid #e8eef5;">'
            .'<div style="background:linear-gradient(135deg,#0f2744,#1f4a78);color:#fff;padding:20px 24px;">'
            .'<div style="font-size:12px;opacity:.8;letter-spacing:.04em;text-transform:uppercase;">Leadforgrow HRM</div>'
            .'<h1 style="margin:6px 0 0;font-size:22px;">'.e($heading).'</h1></div>'
            .'<div style="padding:8px 12px;"><table style="width:100%;border-collapse:collapse;">'.$body.'</table></div>'
            .'<div style="padding:8px 24px 24px;"><a href="'.e($url).'" style="display:inline-block;background:#ff9b44;color:#fff;'
            .'text-decoration:none;padding:12px 20px;border-radius:10px;font-weight:700;">'.e($cta).'</a></div>'
            .'</div></div>';
    }
}
