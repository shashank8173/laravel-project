<?php

namespace App\Http\Controllers;

use App\Http\Requests\Project\AssignProjectTeamRequest;
use App\Http\Requests\Project\ReassignProjectRequest;
use App\Http\Requests\Project\StoreProjectDailyNoteRequest;
use App\Http\Requests\Project\StoreProjectRequest;
use App\Http\Requests\Project\StoreProjectTaskNoteRequest;
use App\Http\Requests\Project\StoreProjectTaskRequest;
use App\Http\Requests\Project\UpdateProjectRequest;
use App\Http\Requests\Project\UpdateProjectTaskStatusRequest;
use App\Models\Employee;
use App\Models\Project;
use App\Models\ProjectDailyNote;
use App\Models\ProjectEmployee;
use App\Models\ProjectTask;
use App\Models\ProjectTaskNote;
use App\Services\EmployeeNotificationService;
use App\Services\ProjectService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ProjectController extends Controller
{
    public function __construct(private ProjectService $projects) {}

    public function index(Request $request)
    {
        $user = $request->user();
        $this->authorize('viewAny', Project::class);

        $q = trim((string) $request->get('q', ''));
        $status = (string) $request->get('status', '');
        $priority = (string) $request->get('priority', '');
        $managerId = (int) $request->get('manager_id', 0);
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');
        $metric = (string) $request->get('metric', '');

        $base = Project::query()->visibleTo($user);

        $countsQuery = clone $base;
        $counts = [
            'total' => (clone $countsQuery)->count(),
            'planning' => (clone $countsQuery)->where('status', 'planning')->count(),
            'active' => (clone $countsQuery)->whereIn('status', ['not_started', 'in_progress'])->count(),
            'completed' => (clone $countsQuery)->where('status', 'completed')->count(),
            'on_hold' => (clone $countsQuery)->where('status', 'on_hold')->count(),
            'overdue' => (clone $countsQuery)
                ->whereNotIn('status', ['completed', 'cancelled'])
                ->whereDate('end_date', '<', now()->toDateString())
                ->count(),
        ];

        $query = Project::query()
            ->visibleTo($user)
            ->with([
                'manager:id,fname,lname',
                'creator:id,fname,lname',
                'activeMemberships.employee:id,fname,lname',
            ]);

        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->where('name', 'like', "%{$q}%")
                    ->orWhere('client_name', 'like', "%{$q}%")
                    ->orWhere('description', 'like', "%{$q}%");
            });
        }

        if ($status !== '' && in_array($status, Project::STATUSES, true)) {
            $query->where('status', $status);
        }

        if ($priority !== '' && in_array($priority, Project::PRIORITIES, true)) {
            $query->where('priority', $priority);
        }

        if ($managerId > 0) {
            $query->where('project_manager_id', $managerId);
        }

        if ($dateFrom) {
            $query->whereDate('start_date', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->whereDate('end_date', '<=', $dateTo);
        }

        if ($metric === 'planning') {
            $query->where('status', 'planning');
        } elseif ($metric === 'active') {
            $query->whereIn('status', ['not_started', 'in_progress']);
        } elseif ($metric === 'completed') {
            $query->where('status', 'completed');
        } elseif ($metric === 'on_hold') {
            $query->where('status', 'on_hold');
        } elseif ($metric === 'overdue') {
            $query->whereNotIn('status', ['completed', 'cancelled'])
                ->whereDate('end_date', '<', now()->toDateString());
        }

        $projects = $query->orderByDesc('id')->paginate(15)->withQueryString();

        $managers = Employee::query()
            ->where('status', 1)
            ->where('archive_status', 0)
            ->orderBy('fname')
            ->orderBy('lname')
            ->get(['id', 'fname', 'lname']);

        return view('projects.index', compact(
            'projects',
            'counts',
            'q',
            'status',
            'priority',
            'managerId',
            'dateFrom',
            'dateTo',
            'metric',
            'managers'
        ));
    }

    public function create(Request $request)
    {
        $this->authorize('create', Project::class);

        return view('projects.create', [
            'employees' => $this->activeEmployees(),
            'priorities' => Project::PRIORITIES,
            'statuses' => Project::STATUSES,
        ]);
    }

    public function store(StoreProjectRequest $request)
    {
        $actor = $request->user();
        $data = $request->validated();
        $teamIds = $data['team_ids'] ?? [];
        unset($data['team_ids']);
        $data['progress'] = (int) ($data['progress'] ?? 0);
        $data['created_by'] = $actor->id;

        $project = DB::transaction(function () use ($data, $actor) {
            $project = Project::query()->create($data);
            $this->projects->logActivity($project, $actor, 'created', [
                'name' => $project->name,
            ]);

            return $project;
        });

        if (! empty($teamIds)) {
            $this->projects->assignEmployees($project, $teamIds, $actor);
        }

        return redirect()
            ->route('projects.show', $project)
            ->with('success', 'Project created successfully.');
    }

    public function show(Request $request, Project $project)
    {
        $this->authorize('view', $project);

        $project->load([
            'manager.department',
            'manager.designation',
            'creator',
            'activeMemberships.employee.department',
            'activeMemberships.employee.designation',
            'activeMemberships.assigner',
            'assignmentHistory.employee',
            'assignmentHistory.previousEmployee',
            'assignmentHistory.assigner',
            'activities.actor',
        ]);

        $noteFilterStatus = (string) $request->get('note_status', '');
        $noteFilterEmp = (int) $request->get('note_employee', 0);

        $notesQuery = $project->dailyNotes()->with('employee:id,fname,lname');
        if ($noteFilterStatus !== '' && in_array($noteFilterStatus, ProjectDailyNote::WORK_STATUSES, true)) {
            $notesQuery->where('work_status', $noteFilterStatus);
        }
        if ($noteFilterEmp > 0) {
            $notesQuery->where('employee_id', $noteFilterEmp);
        }
        $dailyNotes = $notesQuery->limit(100)->get();

        // Latest work status per active member (for team tracker)
        $latestNoteIds = ProjectDailyNote::query()
            ->selectRaw('MAX(id) as id')
            ->where('project_id', $project->id)
            ->groupBy('employee_id')
            ->pluck('id');
        $latestByEmployee = ProjectDailyNote::query()
            ->whereIn('id', $latestNoteIds)
            ->get()
            ->keyBy('employee_id');

        $timeTotals = ProjectDailyNote::query()
            ->where('project_id', $project->id)
            ->selectRaw('employee_id, COALESCE(SUM(duration_minutes), 0) as total_minutes')
            ->groupBy('employee_id')
            ->pluck('total_minutes', 'employee_id');

        $canAddNote = $request->user()->can('addDailyNote', $project);
        $canManageTasks = $request->user()->can('manageTasks', $project);

        $tasks = $project->tasks()
            ->with([
                'assignee:id,fname,lname',
                'assigner:id,fname,lname',
                'notes' => fn ($q) => $q->with('employee:id,fname,lname')->orderByDesc('note_date')->orderByDesc('id')->limit(30),
            ])
            ->get();

        $taskActivityMap = $project->activities()
            ->with('actor:id,fname,lname')
            ->whereIn('action', ['task_created', 'task_status', 'task_note', 'task_deleted'])
            ->orderBy('created_at')
            ->get()
            ->groupBy(fn ($a) => (int) (($a->meta['task_id'] ?? 0)));

        return view('projects.show', [
            'project' => $project,
            'employees' => $this->activeEmployees(),
            'priorities' => Project::PRIORITIES,
            'statuses' => Project::STATUSES,
            'workStatuses' => ProjectDailyNote::WORK_STATUSES,
            'taskStatuses' => ProjectTask::STATUSES,
            'taskPriorities' => ProjectTask::PRIORITIES,
            'tasks' => $tasks,
            'taskActivityMap' => $taskActivityMap,
            'dailyNotes' => $dailyNotes,
            'latestByEmployee' => $latestByEmployee,
            'timeTotals' => $timeTotals,
            'noteFilterStatus' => $noteFilterStatus,
            'noteFilterEmp' => $noteFilterEmp,
            'canAddNote' => $canAddNote,
            'canManageTasks' => $canManageTasks,
            'canUpdate' => $request->user()->can('update', $project),
            'canAssign' => $request->user()->can('assign', $project),
            'canReassign' => $request->user()->can('reassign', $project),
            'canDelete' => $request->user()->can('delete', $project),
        ]);
    }

    public function storeDailyNote(StoreProjectDailyNoteRequest $request, Project $project)
    {
        $actor = $request->user();
        $data = $request->validated();

        $note = DB::transaction(function () use ($project, $actor, $data) {
            $duration = ProjectDailyNote::minutesBetween($data['start_time'], $data['end_time']);

            $note = ProjectDailyNote::query()->create([
                'project_id' => $project->id,
                'employee_id' => $actor->id,
                'note_date' => $data['note_date'],
                'start_time' => $data['start_time'],
                'end_time' => $data['end_time'],
                'duration_minutes' => $duration,
                'note' => $data['note'],
                'work_status' => $data['work_status'],
                'progress_percent' => $data['progress_percent'] ?? null,
            ]);

            $this->projects->logActivity($project, $actor, 'daily_note', [
                'note_id' => $note->id,
                'employee_name' => $actor->full_name,
                'work_status' => $note->work_status,
                'progress_percent' => $note->progress_percent,
                'note_date' => optional($note->note_date)->format('Y-m-d'),
                'start_time' => $data['start_time'],
                'end_time' => $data['end_time'],
                'duration_minutes' => $duration,
                'snippet' => \Illuminate\Support\Str::limit(strip_tags($note->note), 120),
            ]);

            return $note;
        });

        // Notify PM + other active members (except author)
        $recipients = $project->activeMemberships()->pluck('employee_id')->map(fn ($id) => (int) $id)->all();
        $recipients[] = (int) $project->project_manager_id;
        $recipients = array_values(array_unique(array_filter($recipients, fn ($id) => $id && $id !== (int) $actor->id)));

        app(EmployeeNotificationService::class)->notifyMany(
            $recipients,
            'project_daily_note',
            ($actor->full_name ?: 'Teammate').' · '.$note->statusLabel().' on '.$project->name,
            \Illuminate\Support\Str::limit(strip_tags($note->note), 100),
            route('projects.show', $project).'#daily-notes',
            $actor
        );

        return redirect()
            ->route('projects.show', $project)
            ->withFragment('daily-notes')
            ->with('success', 'Daily note saved.');
    }

    public function destroyDailyNote(Request $request, Project $project, ProjectDailyNote $note)
    {
        $this->authorize('view', $project);
        abort_unless((int) $note->project_id === (int) $project->id, 404);

        $user = $request->user();
        $canDelete = $user->isAdmin()
            || (int) $project->project_manager_id === (int) $user->id
            || (int) $note->employee_id === (int) $user->id;
        abort_unless($canDelete, 403);

        $note->delete();

        return back()->with('success', 'Daily note removed.');
    }

    public function storeTask(StoreProjectTaskRequest $request, Project $project)
    {
        $actor = $request->user();
        $data = $request->validated();

        $assigneeId = (int) $data['assigned_to'];
        $needsTeamAdd = ! $project->hasActiveMember($assigneeId);

        $task = DB::transaction(function () use ($project, $actor, $data, $assigneeId) {
            $task = ProjectTask::query()->create([
                'project_id' => $project->id,
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'assigned_to' => $assigneeId,
                'assigned_by' => $actor->id,
                'due_date' => $data['due_date'] ?? null,
                'priority' => $data['priority'],
                'status' => $data['status'] ?? 'pending',
                'progress' => (int) ($data['progress'] ?? 0),
            ]);

            $assigneeName = Employee::query()->find($assigneeId)?->full_name;

            $this->projects->logActivity($project, $actor, 'task_created', [
                'task_id' => $task->id,
                'title' => $task->title,
                'assigned_to' => $assigneeId,
                'assignee_name' => $assigneeName,
                'due_date' => optional($task->due_date)->format('Y-m-d'),
                'priority' => $task->priority,
            ]);

            return $task;
        });

        if ($needsTeamAdd) {
            $this->projects->assignEmployees($project, [$assigneeId], $actor, 'Added via task assignment');
        }

        app(EmployeeNotificationService::class)->notify(
            $assigneeId,
            'project_task_assigned',
            'New task · '.$task->title,
            'On project '.$project->name.' · by '.($actor->full_name ?: 'Manager'),
            route('projects.show', $project).'#project-tasks',
            $actor
        );

        return redirect()
            ->route('projects.show', $project)
            ->withFragment('project-tasks')
            ->with('success', 'Task assigned successfully.');
    }

    public function updateTaskStatus(UpdateProjectTaskStatusRequest $request, Project $project, ProjectTask $task)
    {
        abort_unless((int) $task->project_id === (int) $project->id, 404);
        $actor = $request->user();

        // Extra guard: employee cannot edit once completed
        if ($task->isLockedForEmployee() && ! $actor->can('manageTasks', $project)) {
            return back()->withErrors(['status' => 'This task is completed and locked. Contact your manager to reopen.']);
        }

        $data = $request->validated();
        $prev = $task->status;
        $prevProgress = (int) $task->progress;

        // Employees may not cancel tasks — only managers
        if (($data['status'] ?? '') === 'cancelled' && ! $actor->can('manageTasks', $project)) {
            return back()->withErrors(['status' => 'Only managers can cancel a task.']);
        }

        // Employee marking completed → force 100%
        $progress = (int) ($data['progress'] ?? $task->progress);
        if (($data['status'] ?? '') === 'completed') {
            $progress = max($progress, 100);
        }

        DB::transaction(function () use ($task, $actor, $data, $project, $prev, $prevProgress, $progress) {
            $task->update([
                'status' => $data['status'],
                'progress' => $progress,
            ]);

            $this->projects->logActivity($project, $actor, 'task_status', [
                'task_id' => $task->id,
                'title' => $task->title,
                'from' => $prev,
                'to' => $data['status'],
                'progress_from' => $prevProgress,
                'progress_to' => $progress,
                'locked' => $data['status'] === 'completed',
            ]);
        });

        // Notify assigner / PM when employee updates
        if ((int) $actor->id === (int) $task->assigned_to) {
            $notifyIds = array_filter([
                (int) $task->assigned_by,
                (int) $project->project_manager_id,
            ], fn ($id) => $id && $id !== (int) $actor->id);

            $title = ($data['status'] ?? '') === 'completed'
                ? ($actor->full_name ?: 'Employee').' completed task'
                : ($actor->full_name ?: 'Employee').' updated task · '.$task->fresh()->statusLabel();

            app(EmployeeNotificationService::class)->notifyMany(
                $notifyIds,
                'project_task_status',
                $title,
                $task->title.' on '.$project->name,
                route('projects.show', $project).'#project-tasks',
                $actor
            );
        }

        $msg = ($data['status'] ?? '') === 'completed' && ! $actor->can('manageTasks', $project)
            ? 'Task marked completed. It is now locked on your side.'
            : 'Task status updated.';

        return back()->with('success', $msg);
    }

    public function storeTaskNote(StoreProjectTaskNoteRequest $request, Project $project, ProjectTask $task)
    {
        abort_unless((int) $task->project_id === (int) $project->id, 404);
        $actor = $request->user();

        if ($task->isLockedForEmployee() && ! $actor->can('manageTasks', $project)) {
            return back()->withErrors(['note' => 'This task is completed and locked. You cannot add more notes.']);
        }

        $data = $request->validated();
        $duration = null;
        if (! empty($data['start_time']) && ! empty($data['end_time'])) {
            $duration = ProjectDailyNote::minutesBetween($data['start_time'], $data['end_time']);
        }

        DB::transaction(function () use ($project, $task, $actor, $data, $duration) {
            ProjectTaskNote::query()->create([
                'task_id' => $task->id,
                'project_id' => $project->id,
                'employee_id' => $actor->id,
                'note_date' => $data['note_date'],
                'start_time' => $data['start_time'] ?? null,
                'end_time' => $data['end_time'] ?? null,
                'duration_minutes' => $duration,
                'note' => $data['note'],
                'work_status' => $data['work_status'] ?? null,
            ]);

            if (! empty($data['update_task_status']) && ! empty($data['work_status'])) {
                $map = [
                    'pending' => 'pending',
                    'working' => 'working',
                    'in_progress' => 'in_progress',
                    'completed' => 'completed',
                ];
                if (isset($map[$data['work_status']])) {
                    $payload = ['status' => $map[$data['work_status']]];
                    if ($map[$data['work_status']] === 'completed') {
                        $payload['progress'] = 100;
                    }
                    $task->update($payload);
                }
            }

            $this->projects->logActivity($project, $actor, 'task_note', [
                'task_id' => $task->id,
                'title' => $task->title,
                'snippet' => \Illuminate\Support\Str::limit(strip_tags($data['note']), 120),
                'work_status' => $data['work_status'] ?? null,
                'start_time' => $data['start_time'] ?? null,
                'end_time' => $data['end_time'] ?? null,
                'duration_minutes' => $duration,
            ]);
        });

        return back()->with('success', 'Task note added.');
    }

    public function destroyTask(Request $request, Project $project, ProjectTask $task)
    {
        abort_unless((int) $task->project_id === (int) $project->id, 404);
        $this->authorize('delete', $task);
        $actor = $request->user();

        DB::transaction(function () use ($project, $task, $actor) {
            $this->projects->logActivity($project, $actor, 'task_deleted', [
                'task_id' => $task->id,
                'title' => $task->title,
                'assignee_name' => $task->assignee?->full_name,
            ]);
            ProjectTaskNote::query()->where('task_id', $task->id)->delete();
            $task->delete();
        });

        return back()->with('success', 'Task deleted.');
    }

    public function edit(Request $request, Project $project)
    {
        $this->authorize('update', $project);

        return view('projects.edit', [
            'project' => $project,
            'employees' => $this->activeEmployees(),
            'priorities' => Project::PRIORITIES,
            'statuses' => Project::STATUSES,
        ]);
    }

    public function update(UpdateProjectRequest $request, Project $project)
    {
        $actor = $request->user();
        $data = $request->validated();
        $data['progress'] = (int) ($data['progress'] ?? $project->progress);

        $statusChanged = $project->status !== $data['status'];
        $progressChanged = (int) $project->progress !== (int) $data['progress'];
        $prevStatus = $project->status;
        $prevProgress = (int) $project->progress;

        DB::transaction(function () use ($project, $data, $actor, $statusChanged, $progressChanged, $prevStatus, $prevProgress) {
            $project->update($data);
            $this->projects->logActivity($project, $actor, 'updated', [
                'name' => $project->name,
            ]);
            if ($statusChanged) {
                $this->projects->logActivity($project, $actor, 'status_changed', [
                    'from' => $prevStatus,
                    'to' => $data['status'],
                ]);
            }
            if ($progressChanged) {
                $this->projects->logActivity($project, $actor, 'progress_changed', [
                    'from' => $prevProgress,
                    'to' => (int) $data['progress'],
                ]);
            }
        });

        return redirect()
            ->route('projects.show', $project)
            ->with('success', 'Project updated successfully.');
    }

    public function destroy(Request $request, Project $project)
    {
        $this->authorize('delete', $project);
        $actor = $request->user();

        DB::transaction(function () use ($project, $actor) {
            $previous = $project->status;
            ProjectEmployee::query()
                ->where('project_id', $project->id)
                ->where('status', 'active')
                ->update(['status' => 'removed']);

            \App\Models\ProjectAssignmentHistory::query()
                ->where('project_id', $project->id)
                ->where('status', 'active')
                ->update(['status' => 'removed']);

            $project->update(['status' => 'cancelled']);

            $this->projects->logActivity($project, $actor, 'status_changed', [
                'from' => $previous,
                'to' => 'cancelled',
                'via' => 'delete_action',
            ]);
            $this->projects->logActivity($project, $actor, 'deleted', [
                'name' => $project->name,
                'note' => 'Project cancelled; history retained',
            ]);
        });

        return redirect()
            ->route('projects.index')
            ->with('success', 'Project cancelled. Assignment and activity history have been retained.');
    }

    public function assign(AssignProjectTeamRequest $request, Project $project)
    {
        $result = $this->projects->assignEmployees(
            $project,
            $request->validated('employee_ids'),
            $request->user(),
            $request->validated('reason')
        );

        $msg = count($result['assigned']).' employee(s) assigned.';
        if (count($result['skipped']) > 0) {
            $msg .= ' '.count($result['skipped']).' skipped (inactive or already assigned).';
        }

        return back()->with('success', $msg);
    }

    public function reassign(ReassignProjectRequest $request, Project $project)
    {
        $data = $request->validated();
        $fromId = (int) ($data['from_employee_id'] ?? $request->user()->id);
        $toId = (int) $data['to_employee_id'];

        $from = Employee::query()->findOrFail($fromId);
        $to = Employee::query()->findOrFail($toId);

        if (! $to->isActive()) {
            return back()->withErrors(['to_employee_id' => 'Target employee must be active.']);
        }

        if (! $project->hasActiveMember($fromId)) {
            return back()->withErrors(['from_employee_id' => 'Current employee is not an active project member.']);
        }

        try {
            $this->projects->reassign($project, $from, $to, $request->user(), $data['reason']);
        } catch (\RuntimeException $e) {
            return back()->withErrors(['to_employee_id' => $e->getMessage()]);
        }

        return back()->with('success', 'Project responsibility reassigned successfully.');
    }

    public function removeMember(Request $request, Project $project, Employee $employee)
    {
        $this->authorize('assign', $project);

        if (! $project->hasActiveMember((int) $employee->id)) {
            return back()->withErrors(['member' => 'Employee is not an active project member.']);
        }

        $this->projects->removeMember($project, $employee, $request->user(), $request->input('reason'));

        return back()->with('success', 'Employee removed from project.');
    }

    public function updateStatus(Request $request, Project $project)
    {
        $this->authorize('updateStatus', $project);

        $data = $request->validate([
            'status' => ['required', Rule::in(Project::STATUSES)],
        ]);

        $this->projects->updateStatus($project, $data['status'], $request->user());

        return back()->with('success', 'Project status updated.');
    }

    public function updateProgress(Request $request, Project $project)
    {
        $this->authorize('updateProgress', $project);

        $data = $request->validate([
            'progress' => ['required', 'integer', 'min:0', 'max:100'],
        ]);

        $this->projects->updateProgress($project, (int) $data['progress'], $request->user());

        return back()->with('success', 'Project progress updated.');
    }

    /**
     * @return \Illuminate\Support\Collection<int, Employee>
     */
    private function activeEmployees()
    {
        return Employee::query()
            ->with(['department:id,name', 'designation:id,name'])
            ->where('status', 1)
            ->where('archive_status', 0)
            ->orderBy('fname')
            ->orderBy('lname')
            ->get(['id', 'fname', 'lname', 'department_id', 'designation_id', 'office_email']);
    }
}
