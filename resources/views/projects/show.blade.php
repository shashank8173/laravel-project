@extends('layouts.app')

@section('title', $project->name)
@section('heading', 'Project details')

@section('page_actions')
<a href="{{ route('projects.index') }}" class="btn btn-outline-secondary me-2">
    <i class="la la-arrow-left"></i> Projects
</a>
@if($canUpdate)
    <a href="{{ route('projects.edit', $project) }}" class="btn add-btn">
        <i class="fa-solid fa-pen"></i> Edit
    </a>
@endif
@endsection

@push('styles')
<style>
    .pj-wrap { --ink:#0f2744; --muted:#6b7c93; --line:#e8eef5; --soft:#f4f7fb; --accent:#ff9b44; }
    .pj-wrap .pj-panel { background:#fff; border:1px solid var(--line); border-radius:18px; overflow:hidden; margin-bottom:1rem; }
    .pj-wrap .pj-panel-head {
        display:flex; justify-content:space-between; align-items:center; gap:1rem; flex-wrap:wrap;
        padding:1rem 1.15rem; border-bottom:1px solid var(--line);
        background:linear-gradient(180deg,#fff,#fafbfd);
    }
    .pj-wrap .pj-panel-head h5 { margin:0; font-weight:750; color:var(--ink); }
    .pj-wrap .pj-panel-body { padding:1.15rem; }
    .pj-wrap .hero {
        background:linear-gradient(135deg,#fff8f2,#fff); border:1px solid #ffe1c4;
        border-radius:18px; padding:1.25rem; margin-bottom:1rem;
    }
    .pj-wrap .meta-row { display:flex; flex-wrap:wrap; gap:1rem 1.5rem; margin-top:.85rem; }
    .pj-wrap .meta-row .k { display:block; font-size:.7rem; text-transform:uppercase; letter-spacing:.04em; color:var(--muted); font-weight:700; }
    .pj-wrap .meta-row .v { font-weight:700; color:var(--ink); }
    .pj-wrap .pill {
        display:inline-flex; padding:.28rem .65rem; border-radius:999px; font-size:.72rem; font-weight:700; text-transform:capitalize;
    }
    .pj-wrap .pr-low { background:#eef2ff; color:#4338ca; }
    .pj-wrap .pr-medium { background:#e0f2fe; color:#0369a1; }
    .pj-wrap .pr-high { background:#ffedd5; color:#c2410c; }
    .pj-wrap .pr-urgent { background:#fee2e2; color:#b91c1c; }
    .pj-wrap .st-planning { background:#dbeafe; color:#1d4ed8; }
    .pj-wrap .st-not_started { background:#e2e8f0; color:#475569; }
    .pj-wrap .st-in_progress { background:#dcfce7; color:#15803d; }
    .pj-wrap .st-on_hold { background:#fef3c7; color:#b45309; }
    .pj-wrap .st-completed { background:#ccfbf1; color:#0f766e; }
    .pj-wrap .st-cancelled { background:#fee2e2; color:#b91c1c; }
    .pj-wrap .prog-lg { height:14px; background:#eef2f7; border-radius:999px; overflow:hidden; }
    .pj-wrap .prog-lg > span { display:block; height:100%; background:linear-gradient(90deg,#ff9b44,#ff7a18); }
    .pj-wrap .task-flow {
        display:flex; align-items:flex-start; gap:0; flex-wrap:nowrap; overflow-x:auto;
        padding:.35rem 0 .15rem; margin:.75rem 0 .25rem;
    }
    .pj-wrap .task-flow-step {
        flex:1 1 0; min-width:88px; text-align:center; position:relative; padding:0 .25rem;
    }
    .pj-wrap .task-flow-step:not(:last-child)::after {
        content:""; position:absolute; top:14px; left:calc(50% + 14px); right:calc(-50% + 14px);
        height:3px; background:#e5e7eb; z-index:0;
    }
    .pj-wrap .task-flow-step.is-done:not(:last-child)::after,
    .pj-wrap .task-flow-step.is-current:not(:last-child)::after { background:#86efac; }
    .pj-wrap .task-flow-dot {
        width:28px; height:28px; border-radius:50%; margin:0 auto .35rem;
        display:flex; align-items:center; justify-content:center;
        background:#eef2f7; color:#64748b; font-size:.72rem; font-weight:800;
        border:2px solid #fff; box-shadow:0 0 0 2px #e5e7eb; position:relative; z-index:1;
    }
    .pj-wrap .task-flow-step.is-done .task-flow-dot {
        background:#16a34a; color:#fff; box-shadow:0 0 0 2px #86efac;
    }
    .pj-wrap .task-flow-step.is-current .task-flow-dot {
        background:#ff9b44; color:#fff; box-shadow:0 0 0 2px #ffd0a8;
    }
    .pj-wrap .task-flow-label {
        font-size:.68rem; font-weight:700; color:#6b7c93; text-transform:uppercase; letter-spacing:.03em;
    }
    .pj-wrap .task-flow-step.is-done .task-flow-label,
    .pj-wrap .task-flow-step.is-current .task-flow-label { color:#0f2744; }
    .pj-wrap .task-locked {
        background:#ecfdf5; border:1px solid #a7f3d0; color:#047857;
        border-radius:12px; padding:.7rem .9rem; font-size:.85rem; font-weight:600;
    }
    /* Zigzag vertical history (left / right columns) */
    .pj-wrap .pj-zigzag {
        position: relative;
        padding: .5rem 0 0;
        max-width: 920px;
        margin: 0 auto;
    }
    .pj-wrap .pj-zigzag::before {
        content: "";
        position: absolute;
        left: 50%;
        top: 8px;
        bottom: 8px;
        width: 3px;
        background: linear-gradient(180deg, #ffd0a8, #e8eef5 40%, #e8eef5);
        transform: translateX(-50%);
        border-radius: 3px;
    }
    .pj-wrap .pj-zigzag-row {
        display: grid;
        grid-template-columns: 1fr 28px 1fr;
        gap: .75rem;
        align-items: start;
        margin-bottom: 1.1rem;
        position: relative;
    }
    .pj-wrap .pj-zigzag-row:last-child { margin-bottom: 0; }
    .pj-wrap .pj-zigzag-point {
        width: 16px; height: 16px; border-radius: 50%;
        background: #ff9b44; border: 3px solid #fff;
        box-shadow: 0 0 0 2px #ffd7b0;
        margin: .35rem auto 0;
        position: relative; z-index: 1;
    }
    .pj-wrap .pj-zigzag-card {
        background: #fff;
        border: 1px solid #e8eef5;
        border-radius: 14px;
        padding: .85rem 1rem;
        box-shadow: 0 4px 14px rgba(15,39,68,.04);
    }
    .pj-wrap .pj-zigzag-row.is-left .pj-zigzag-card { text-align: right; }
    .pj-wrap .pj-zigzag-row.is-right .pj-zigzag-card { text-align: left; }
    .pj-wrap .pj-zigzag-row.is-left .pj-zigzag-spacer { grid-column: 3; }
    .pj-wrap .pj-zigzag-row.is-left .pj-zigzag-card { grid-column: 1; grid-row: 1; }
    .pj-wrap .pj-zigzag-row.is-left .pj-zigzag-point { grid-column: 2; grid-row: 1; }
    .pj-wrap .pj-zigzag-row.is-right .pj-zigzag-spacer { grid-column: 1; }
    .pj-wrap .pj-zigzag-row.is-right .pj-zigzag-point { grid-column: 2; grid-row: 1; }
    .pj-wrap .pj-zigzag-row.is-right .pj-zigzag-card { grid-column: 3; grid-row: 1; }
    .pj-wrap .pj-zigzag .h-title { font-weight: 750; color: var(--ink); font-size: .9rem; margin: 0 0 .25rem; }
    .pj-wrap .pj-zigzag .h-meta { font-size: .78rem; color: var(--muted); margin-top: .15rem; }
    .pj-wrap .pj-zigzag .h-body { font-size: .86rem; color: #344054; margin-top: .45rem; }
    @media (max-width: 767px) {
        .pj-wrap .pj-zigzag::before { left: 12px; transform: none; }
        .pj-wrap .pj-zigzag-row {
            grid-template-columns: 28px 1fr;
            gap: .65rem;
        }
        .pj-wrap .pj-zigzag-row.is-left .pj-zigzag-card,
        .pj-wrap .pj-zigzag-row.is-right .pj-zigzag-card {
            grid-column: 2; grid-row: 1; text-align: left;
        }
        .pj-wrap .pj-zigzag-row.is-left .pj-zigzag-point,
        .pj-wrap .pj-zigzag-row.is-right .pj-zigzag-point {
            grid-column: 1; grid-row: 1;
        }
        .pj-wrap .pj-zigzag-spacer { display: none !important; }
    }
    .pj-wrap .timeline { list-style:none; margin:0; padding:0; }
    .pj-wrap .timeline li {
        position:relative; padding:0 0 1.15rem 1.35rem; border-left:2px solid #e8eef5;
    }
    .pj-wrap .timeline li:last-child { border-left-color:transparent; padding-bottom:0; }
    .pj-wrap .timeline li::before {
        content:""; position:absolute; left:-7px; top:4px; width:12px; height:12px;
        border-radius:50%; background:var(--accent); border:2px solid #fff; box-shadow:0 0 0 2px #ffd7b0;
    }
    .pj-wrap .timeline .t-title { font-weight:750; color:var(--ink); margin:0; }
    .pj-wrap .timeline .t-meta { font-size:.8rem; color:var(--muted); margin-top:.15rem; }
    .pj-wrap .table > :not(caption) > * > * { vertical-align:middle; }
    .pj-wrap .form-label { font-size:.8rem; font-weight:600; color:var(--muted); }
    .pj-wrap .pj-scroll-box {
        max-height: 36rem; /* ~10 latest items; rest scroll inside */
        overflow-y: auto;
        overflow-x: hidden;
        padding: .15rem .35rem .35rem 0;
        scrollbar-width: thin;
        scrollbar-color: #ffd0a8 transparent;
        overscroll-behavior: contain;
    }
    .pj-wrap .pj-scroll-box::-webkit-scrollbar { width: 6px; }
    .pj-wrap .pj-scroll-box::-webkit-scrollbar-thumb {
        background: #ffd0a8; border-radius: 8px;
    }
    /* In side-by-side columns, zigzag becomes a clean left-rail timeline */
    .pj-wrap .pj-split-row .pj-zigzag::before {
        left: 12px;
        transform: none;
    }
    .pj-wrap .pj-split-row .pj-zigzag-row {
        grid-template-columns: 28px 1fr;
        gap: .65rem;
    }
    .pj-wrap .pj-split-row .pj-zigzag-row.is-left .pj-zigzag-card,
    .pj-wrap .pj-split-row .pj-zigzag-row.is-right .pj-zigzag-card {
        grid-column: 2;
        grid-row: 1;
        text-align: left !important;
    }
    .pj-wrap .pj-split-row .pj-zigzag-row.is-left .pj-zigzag-point,
    .pj-wrap .pj-split-row .pj-zigzag-row.is-right .pj-zigzag-point {
        grid-column: 1;
        grid-row: 1;
    }
    .pj-wrap .pj-split-row .pj-zigzag-spacer { display: none !important; }
    .pj-wrap .pj-split-row .pj-zigzag .d-flex.justify-content-end {
        justify-content: flex-start !important;
    }
    .pj-wrap .pj-split-row .pj-zigzag .text-end { text-align: left !important; }
    .pj-wrap .pj-split-row > [class*="col-"] > .pj-panel { height: 100%; margin-bottom: 0; }
</style>
@endpush

@section('content')
@php
    $user = auth()->user();
    $activeIds = $project->activeMemberships->pluck('employee_id')->map(fn ($id) => (int) $id)->all();
    $canReassignSelf = $canReassign && in_array((int) $user->id, $activeIds, true);
@endphp
<div class="pj-wrap">
    <div class="hero">
        <div class="d-flex flex-wrap gap-2 mb-2">
            <span class="pill pr-{{ $project->priority }}">{{ $project->priorityLabel() }}</span>
            <span class="pill st-{{ $project->status }}">{{ $project->statusLabel() }}</span>
            @if($project->isOverdue())
                <span class="pill st-cancelled">Overdue</span>
            @endif
        </div>
        <h3 class="fw-bold mb-1" style="color:var(--ink);">{{ $project->name }}</h3>
        <div class="text-muted">{{ $project->client_name ?: 'No client' }}</div>
        <div class="meta-row">
            <div>
                <span class="k">Project Manager</span>
                <span class="v">{{ $project->manager?->full_name ?: '—' }}</span>
            </div>
            <div>
                <span class="k">Start</span>
                <span class="v">{{ optional($project->start_date)->format('d M Y') }}</span>
            </div>
            <div>
                <span class="k">Deadline</span>
                <span class="v">{{ optional($project->end_date)->format('d M Y') }}</span>
            </div>
            <div>
                <span class="k">Created By</span>
                <span class="v">{{ $project->creator?->full_name ?: '—' }}</span>
            </div>
            <div>
                <span class="k">Created At</span>
                <span class="v">{{ optional($project->created_at)->format('d M Y, h:i A') }}</span>
            </div>
        </div>
        <div class="mt-3">
            <div class="d-flex justify-content-between mb-1">
                <span class="k">Progress</span>
                <strong style="color:var(--ink);">{{ (int) $project->progress }}%</strong>
            </div>
            <div class="prog-lg"><span style="width:{{ (int) $project->progress }}%"></span></div>
        </div>
        @if($project->description)
            <div class="mt-3 hrm-rich-content">{!! $project->description !!}</div>
        @endif
    </div>

    <div class="row g-3">
        @if($canUpdate)
            <div class="col-md-6">
                <div class="pj-panel">
                    <div class="pj-panel-head"><h5>Update Status</h5></div>
                    <div class="pj-panel-body">
                        <form method="POST" action="{{ route('projects.status', $project) }}" class="row g-2 align-items-end">
                            @csrf
                            <div class="col-8">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select">
                                    @foreach($statuses as $st)
                                        <option value="{{ $st }}" @selected($project->status === $st)>{{ ucwords(str_replace('_',' ',$st)) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-4">
                                <button class="btn add-btn w-100" type="submit">Update</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="pj-panel">
                    <div class="pj-panel-head"><h5>Update Progress</h5></div>
                    <div class="pj-panel-body">
                        <form method="POST" action="{{ route('projects.progress', $project) }}" class="row g-2 align-items-end">
                            @csrf
                            <div class="col-8">
                                <label class="form-label">Progress %</label>
                                <input type="number" name="progress" min="0" max="100" class="form-control" value="{{ (int) $project->progress }}">
                            </div>
                            <div class="col-4">
                                <button class="btn add-btn w-100" type="submit">Update</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <div class="pj-panel" id="assign-team">
        <div class="pj-panel-head">
            <h5>Project Team</h5>
            @if($canAssign)
                <button type="button" class="btn btn-sm add-btn" data-bs-toggle="modal" data-bs-target="#assignModal">
                    <i class="fa-solid fa-user-plus"></i> Assign Team
                </button>
            @endif
        </div>
        <div class="pj-panel-body table-responsive">
            <table class="table mb-0">
                <thead>
                <tr>
                    <th>Employee</th>
                    <th>Designation</th>
                    <th>Department</th>
                    <th>Assigned Date</th>
                    <th>Assigned By</th>
                    <th>Work Status</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @forelse($project->activeMemberships as $membership)
                    @php $latest = $latestByEmployee[(int) $membership->employee_id] ?? null; @endphp
                    <tr>
                        <td class="fw-semibold">{{ $membership->employee?->full_name }}</td>
                        <td>{{ $membership->employee?->designation?->name ?: '—' }}</td>
                        <td>{{ $membership->employee?->department?->name ?: '—' }}</td>
                        <td>{{ optional($membership->assigned_at)->format('d M Y, h:i A') }}</td>
                        <td>{{ $membership->assigner?->full_name ?: '—' }}</td>
                        <td>
                            @if($latest)
                                <span class="pill {{ $latest->statusClass() }}">{{ $latest->statusLabel() }}</span>
                                <div class="small text-muted mt-1">
                                    {{ $latest->timeRangeLabel() }}
                                    @if($latest->duration_minutes !== null) · {{ $latest->durationLabel() }}@endif
                                </div>
                                @php $totalMins = (int) ($timeTotals[(int) $membership->employee_id] ?? 0); @endphp
                                @if($totalMins > 0)
                                    @php
                                        $th = intdiv($totalMins, 60);
                                        $tm = $totalMins % 60;
                                        $totalLabel = $th > 0 ? ($th.'h'.($tm ? ' '.$tm.'m' : '')) : ($tm.'m');
                                    @endphp
                                    <div class="small fw-semibold mt-1" style="color:var(--ink);">Total: {{ $totalLabel }}</div>
                                @endif
                            @else
                                <span class="pill st-not_started">No update</span>
                            @endif
                        </td>
                        <td class="text-end">
                            @if($canReassign && (
                                $user->isAdmin()
                                || (int) $project->project_manager_id === (int) $user->id
                                || (int) $membership->employee_id === (int) $user->id
                            ))
                                <button type="button" class="btn btn-sm btn-outline-primary"
                                        data-bs-toggle="modal"
                                        data-bs-target="#reassignModal"
                                        data-from-id="{{ $membership->employee_id }}"
                                        data-from-name="{{ $membership->employee?->full_name }}">
                                    Reassign
                                </button>
                            @endif
                            @if($canAssign)
                                <form method="POST" action="{{ route('projects.members.remove', [$project, $membership->employee_id]) }}"
                                      class="d-inline"
                                      onsubmit="return confirm('Remove this employee from the project?');">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger" type="submit">Remove</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-3">No team members assigned yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Project Tasks (left) + Daily Work Notes (right) --}}
    <div class="row g-3 pj-split-row mb-3">
    <div class="col-lg-6">
    <div class="pj-panel" id="project-tasks">
        <div class="pj-panel-head">
            <h5>Project Tasks</h5>
            <span class="small text-muted">
                @if($canManageTasks) Assign · @endif
                Latest 10 visible · scroll for more
            </span>
        </div>
        <div class="pj-panel-body">
            @if($canManageTasks)
                <form method="POST" action="{{ route('projects.tasks.store', $project) }}" class="row g-2 mb-3 p-3" style="background:#fafbfd;border:1px solid #e8eef5;border-radius:14px;">
                    @csrf
                    <div class="col-12">
                        <label class="form-label">Task title <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control form-control-sm" value="{{ old('title') }}" required placeholder="e.g. Build login API">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Assign to <span class="text-danger">*</span></label>
                        <x-employee-select
                            name="assigned_to"
                            :employees="$employees"
                            :selected="old('assigned_to')"
                            :required="true"
                            class="form-select form-select-sm"
                            placeholder="Search employee…"
                        />
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Priority</label>
                        <select name="priority" class="form-select form-select-sm">
                            @foreach($taskPriorities as $pr)
                                <option value="{{ $pr }}" @selected(old('priority', 'medium') === $pr)>{{ ucfirst($pr) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Due date</label>
                        <input type="date" name="due_date" class="form-control form-control-sm" value="{{ old('due_date') }}">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Description</label>
                        <textarea name="description" rows="2" class="form-control js-rich-editor" placeholder="Click to write task details…">{{ old('description') }}</textarea>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-sm add-btn"><i class="fa-solid fa-plus me-1"></i> Assign task</button>
                    </div>
                </form>
            @endif

            <div class="pj-scroll-box">
            @forelse($tasks as $task)
                @php
                    $isAssignee = (int) $task->assigned_to === (int) auth()->id();
                    $employeeLocked = $isAssignee && ! $canManageTasks && $task->isLockedForEmployee();
                    $canTouch = $canManageTasks || ($isAssignee && ! $employeeLocked);
                    $flow = $task->flowSteps();
                    $historyActs = ($taskActivityMap[(int) $task->id] ?? collect())->sortByDesc('created_at');
                @endphp
                <div class="border rounded-3 mb-3 p-3" style="border-color:#e8eef5!important;">
                    <div class="d-flex flex-wrap justify-content-between gap-2 align-items-start">
                        <div class="flex-grow-1">
                            <div class="d-flex flex-wrap gap-2 align-items-center mb-1">
                                <span class="fw-bold" style="color:var(--ink);font-size:1rem;">{{ $task->title }}</span>
                                <span class="pill {{ $task->priorityClass() }}">{{ $task->priorityLabel() }}</span>
                                <span class="pill {{ $task->statusClass() }}">{{ $task->statusLabel() }}</span>
                                @if($task->isOverdue())
                                    <span class="pill st-cancelled">Overdue</span>
                                @endif
                                @if($task->status === 'completed')
                                    <span class="pill st-completed"><i class="fa-solid fa-lock me-1"></i>Locked</span>
                                @endif
                            </div>
                            <div class="small text-muted">
                                Assigned to <strong>{{ $task->assignee?->full_name }}</strong>
                                · by {{ $task->assigner?->full_name ?: '—' }}
                                @if($task->due_date) · Due {{ $task->due_date->format('d M Y') }} @endif
                                · Progress {{ (int) $task->progress }}%
                            </div>
                            @if($task->description)
                                <div class="hrm-rich-content mt-2" style="font-size:.88rem;">{!! $task->description !!}</div>
                            @endif

                            {{-- Easy status flow --}}
                            <div class="task-flow" aria-label="Task progress flow">
                                @foreach($flow as $step)
                                    <div class="task-flow-step {{ $step['done'] ? 'is-done' : '' }} {{ $step['current'] ? 'is-current' : '' }}">
                                        <div class="task-flow-dot">
                                            @if($step['done'] && ! $step['current'])
                                                <i class="fa-solid fa-check"></i>
                                            @elseif($step['current'])
                                                <i class="fa-solid fa-circle" style="font-size:.45rem;"></i>
                                            @else
                                                {{ $loop->iteration }}
                                            @endif
                                        </div>
                                        <div class="task-flow-label">{{ $step['label'] }}</div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        @if($canManageTasks)
                            <form method="POST" action="{{ route('projects.tasks.destroy', [$project, $task]) }}"
                                  onsubmit="return confirm('Delete this task?');">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger" type="submit">Delete</button>
                            </form>
                        @endif
                    </div>

                    @if($employeeLocked)
                        <div class="task-locked mt-3">
                            <i class="fa-solid fa-circle-check me-1"></i>
                            Task completed — locked on your side. Status and notes cannot be changed.
                            @if($canManageTasks)
                                {{-- never shown together --}}
                            @endif
                            Ask your manager if it needs to be reopened.
                        </div>
                    @endif

                    @if($canTouch)
                        <div class="row g-2 mt-3 align-items-end">
                            <div class="col-md-8">
                                <form method="POST" action="{{ route('projects.tasks.status', [$project, $task]) }}" class="row g-2 align-items-end"
                                      onsubmit="if(this.status.value==='completed' && !{{ $canManageTasks ? 'true' : 'false' }}){ return confirm('Mark as Completed? You will not be able to edit this task after this.'); }">
                                    @csrf
                                    <div class="col-sm-5">
                                        <label class="form-label">Update status</label>
                                        <select name="status" class="form-select form-select-sm">
                                            @foreach($taskStatuses as $st)
                                                @if($st === 'cancelled' && ! $canManageTasks)
                                                    @continue
                                                @endif
                                                <option value="{{ $st }}" @selected($task->status === $st)>
                                                    {{ match($st) {
                                                        'pending' => 'Pending',
                                                        'working' => 'Working',
                                                        'in_progress' => 'In Progress',
                                                        'completed' => 'Completed',
                                                        'cancelled' => 'Cancelled',
                                                        default => $st
                                                    } }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-sm-4">
                                        <label class="form-label">Progress %</label>
                                        <input type="number" name="progress" min="0" max="100" class="form-control form-control-sm" value="{{ (int) $task->progress }}">
                                    </div>
                                    <div class="col-sm-3">
                                        <button class="btn btn-sm add-btn w-100" type="submit">Save</button>
                                    </div>
                                </form>
                                @if($canManageTasks && $task->isLockedForEmployee())
                                    <div class="form-text text-success">Manager view: you can reopen this completed task.</div>
                                @endif
                            </div>
                        </div>

                        <details class="mt-3">
                            <summary class="fw-semibold" style="cursor:pointer;color:var(--ink);">Add note / time log</summary>
                            <form method="POST" action="{{ route('projects.tasks.notes.store', [$project, $task]) }}" class="row g-2 mt-2">
                                @csrf
                                <div class="col-md-2">
                                    <label class="form-label">Date</label>
                                    <input type="date" name="note_date" class="form-control form-control-sm" value="{{ now()->toDateString() }}" required>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Start</label>
                                    <input type="time" name="start_time" class="form-control form-control-sm">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">End</label>
                                    <input type="time" name="end_time" class="form-control form-control-sm">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Note status</label>
                                    <select name="work_status" class="form-select form-select-sm">
                                        <option value="">—</option>
                                        @foreach($workStatuses as $ws)
                                            <option value="{{ $ws }}">{{ match($ws){ 'pending'=>'Pending','working'=>'Working','in_progress'=>'In Progress','completed'=>'Completed', default=>$ws} }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3 d-flex align-items-end">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="update_task_status" value="1" id="syncStatus{{ $task->id }}">
                                        <label class="form-check-label small" for="syncStatus{{ $task->id }}">Also update task status</label>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <textarea name="note" rows="2" class="form-control js-rich-editor" placeholder="Click to write what you did on this task…" required></textarea>
                                </div>
                                <div class="col-12">
                                    <button type="submit" class="btn btn-sm add-btn">Add note</button>
                                </div>
                            </form>
                        </details>
                    @endif

                    {{-- Friendly history flow --}}
                    <details class="mt-3" @if($historyActs->isNotEmpty() || $task->notes->isNotEmpty()) open @endif>
                        <summary class="fw-semibold" style="cursor:pointer;color:var(--ink);">
                            <i class="fa-solid fa-clock-rotate-left me-1"></i> Task history
                        </summary>
                        @php
                            $events = collect();
                            foreach ($historyActs as $act) {
                                $meta = $act->meta ?? [];
                                $label = match ($act->action) {
                                    'task_created' => 'Task assigned to '.($meta['assignee_name'] ?? 'employee'),
                                    'task_status' => 'Status: '.ucwords(str_replace('_',' ', (string)($meta['from'] ?? '?'))).' → '.ucwords(str_replace('_',' ', (string)($meta['to'] ?? '?'))),
                                    'task_note' => 'Note added'.(!empty($meta['snippet']) ? ': '.$meta['snippet'] : ''),
                                    'task_deleted' => 'Task deleted',
                                    default => $act->title(),
                                };
                                $events->push([
                                    'at' => $act->created_at,
                                    'who' => $act->actor?->full_name ?: 'System',
                                    'label' => $label,
                                    'extra' => !empty($meta['duration_minutes'])
                                        ? (intdiv((int)$meta['duration_minutes'],60).'h '.(((int)$meta['duration_minutes'])%60).'m')
                                        : null,
                                ]);
                            }
                            foreach ($task->notes as $tn) {
                                $events->push([
                                    'at' => $tn->created_at,
                                    'who' => $tn->employee?->full_name ?: '—',
                                    'label' => 'Work note'.($tn->work_status ? ' · '.$tn->statusLabel() : ''),
                                    'extra' => trim(($tn->timeRangeLabel() !== '—' ? $tn->timeRangeLabel().' ('.$tn->durationLabel().')' : '').' '.\Illuminate\Support\Str::limit(strip_tags($tn->note), 100)),
                                ]);
                            }
                            $events = $events->sortByDesc(fn ($e) => optional($e['at'])->timestamp ?? 0)->values();
                        @endphp
                        <div class="pj-zigzag mt-3">
                            @forelse($events as $i => $ev)
                                <div class="pj-zigzag-row {{ $i % 2 === 0 ? 'is-left' : 'is-right' }}">
                                    <div class="pj-zigzag-spacer"></div>
                                    <div class="pj-zigzag-point" title="{{ optional($ev['at'])->format('d M Y, h:i A') }}"></div>
                                    <div class="pj-zigzag-card">
                                        <p class="h-title">{{ $ev['label'] }}</p>
                                        <div class="h-meta">
                                            {{ $ev['who'] }} · {{ optional($ev['at'])->format('d M Y, h:i A') }}
                                        </div>
                                        @if(!empty($ev['extra']))
                                            <div class="h-body">{{ $ev['extra'] }}</div>
                                        @endif
                                    </div>
                                </div>
                            @empty
                                <div class="text-center text-muted py-3 small">No history yet. Updates will appear here.</div>
                            @endforelse
                        </div>
                    </details>
                </div>
            @empty
                <div class="text-center text-muted py-3">No tasks assigned yet.</div>
            @endforelse
            </div>{{-- /.pj-scroll-box tasks --}}
        </div>
    </div>
    </div>{{-- /.col-lg-6 tasks --}}

    <div class="col-lg-6">
    <div class="pj-panel" id="daily-notes">
        <div class="pj-panel-head">
            <h5>Daily Work Notes</h5>
            <span class="small text-muted">Latest 10 visible · scroll for more</span>
        </div>
        <div class="pj-panel-body">
            @if($canAddNote)
                <form method="POST" action="{{ route('projects.notes.store', $project) }}" class="row g-2 mb-3 p-3" style="background:#fafbfd;border:1px solid #e8eef5;border-radius:14px;">
                    @csrf
                    <div class="col-6">
                        <label class="form-label">Date</label>
                        <input type="date" name="note_date" class="form-control form-control-sm" value="{{ old('note_date', now()->toDateString()) }}" required>
                    </div>
                    <div class="col-6">
                        <label class="form-label">Work status</label>
                        <select name="work_status" class="form-select form-select-sm" required>
                            @foreach($workStatuses as $ws)
                                <option value="{{ $ws }}" @selected(old('work_status', 'working') === $ws)>
                                    {{ match($ws) { 'pending' => 'Pending', 'working' => 'Working', 'in_progress' => 'In Progress', 'completed' => 'Completed', default => $ws } }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-4">
                        <label class="form-label">Start</label>
                        <input type="time" name="start_time" class="form-control form-control-sm @error('start_time') is-invalid @enderror"
                               value="{{ old('start_time') }}" required>
                        @error('start_time')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-4">
                        <label class="form-label">End</label>
                        <input type="time" name="end_time" class="form-control form-control-sm @error('end_time') is-invalid @enderror"
                               value="{{ old('end_time') }}" required>
                        @error('end_time')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-4">
                        <label class="form-label">Progress %</label>
                        <input type="number" name="progress_percent" min="0" max="100" class="form-control form-control-sm"
                               value="{{ old('progress_percent') }}" placeholder="0–100">
                    </div>
                    <div class="col-12">
                        <label class="form-label">What did you do?</label>
                        <textarea name="note" rows="2" class="form-control js-rich-editor @error('note') is-invalid @enderror"
                                  placeholder="Click to write today’s work update…" required>{{ old('note') }}</textarea>
                        @error('note')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-sm add-btn">
                            <i class="fa-solid fa-plus me-1"></i> Add note
                        </button>
                    </div>
                </form>
            @endif

            <form method="GET" action="{{ route('projects.show', $project) }}" class="row g-2 align-items-end mb-3">
                <input type="hidden" name="_hash" value="daily-notes">
                <div class="col-5">
                    <label class="form-label">Employee</label>
                    <select name="note_employee" class="form-select form-select-sm">
                        <option value="">All team</option>
                        @foreach($project->activeMemberships as $m)
                            <option value="{{ $m->employee_id }}" @selected($noteFilterEmp === (int) $m->employee_id)>
                                {{ $m->employee?->full_name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-5">
                    <label class="form-label">Status</label>
                    <select name="note_status" class="form-select form-select-sm">
                        <option value="">All statuses</option>
                        @foreach($workStatuses as $ws)
                            <option value="{{ $ws }}" @selected($noteFilterStatus === $ws)>
                                {{ match($ws) { 'pending' => 'Pending', 'working' => 'Working', 'in_progress' => 'In Progress', 'completed' => 'Completed', default => $ws } }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-2">
                    <button class="btn btn-sm btn-outline-secondary w-100" type="submit">Go</button>
                </div>
            </form>

            <div class="pj-scroll-box">
            <div class="pj-zigzag mt-1">
                @forelse($dailyNotes as $i => $note)
                    <div class="pj-zigzag-row {{ $i % 2 === 0 ? 'is-left' : 'is-right' }}">
                        <div class="pj-zigzag-spacer"></div>
                        <div class="pj-zigzag-point" title="{{ optional($note->note_date)->format('d M Y') }}"></div>
                        <div class="pj-zigzag-card">
                            <div class="d-flex flex-wrap gap-2 align-items-center justify-content-start mb-1">
                                <span class="h-title mb-0">{{ $note->employee?->full_name }}</span>
                                <span class="pill {{ $note->statusClass() }}">{{ $note->statusLabel() }}</span>
                                @if($note->progress_percent !== null)
                                    <span class="small text-muted fw-semibold">{{ (int) $note->progress_percent }}%</span>
                                @endif
                            </div>
                            <div class="h-meta">
                                {{ optional($note->note_date)->format('d M Y') }}
                                · {{ $note->timeRangeLabel() }}
                                · <strong>{{ $note->durationLabel() }}</strong>
                            </div>
                            <div class="h-body hrm-rich-content">{!! $note->note !!}</div>
                            <div class="h-meta mt-1">Posted {{ optional($note->created_at)->format('d M, h:i A') }}</div>
                            @if(auth()->user()->isAdmin() || (int) $project->project_manager_id === (int) auth()->id() || (int) $note->employee_id === (int) auth()->id())
                                <form method="POST" action="{{ route('projects.notes.destroy', [$project, $note]) }}"
                                      class="mt-2 text-start"
                                      onsubmit="return confirm('Delete this daily note?');">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger" type="submit">Delete</button>
                                </form>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="text-center text-muted py-4">No daily notes yet. Team members can add what they worked on.</div>
                @endforelse
            </div>
            </div>{{-- /.pj-scroll-box notes --}}
        </div>
    </div>
    </div>{{-- /.col-lg-6 notes --}}
    </div>{{-- /.pj-split-row --}}

    <div class="row g-3">
        <div class="col-lg-6">
            <div class="pj-panel" id="activity">
                <div class="pj-panel-head"><h5>Activity Timeline</h5></div>
                <div class="pj-panel-body">
                    <ul class="timeline">
                        @forelse($project->activities as $activity)
                            @php $meta = $activity->meta ?? []; @endphp
                            <li>
                                <p class="t-title">{{ $activity->title() }}</p>
                                <div class="t-meta">
                                    By: {{ $activity->actor?->full_name ?: 'System' }}
                                    · {{ optional($activity->created_at)->format('d M Y, h:i A') }}
                                </div>
                                @if(($meta['reason'] ?? null))
                                    <div class="t-meta">Reason: {{ $meta['reason'] }}</div>
                                @endif
                                @if(($meta['from'] ?? null) || ($meta['to'] ?? null))
                                    <div class="t-meta">
                                        @if(isset($meta['from_employee_name'], $meta['to_employee_name']))
                                            {{ $meta['from_employee_name'] }} → {{ $meta['to_employee_name'] }}
                                        @elseif(isset($meta['employee_name']))
                                            {{ $meta['employee_name'] }}
                                            @if(($meta['assignment_type'] ?? null)) ({{ str_replace('_',' ', $meta['assignment_type']) }}) @endif
                                        @else
                                            {{ is_string($meta['from'] ?? null) ? ucwords(str_replace('_',' ', $meta['from'])) : ($meta['from'] ?? '') }}
                                            →
                                            {{ is_string($meta['to'] ?? null) ? ucwords(str_replace('_',' ', (string)$meta['to'])) : ($meta['to'] ?? '') }}
                                            @if(is_numeric($meta['from'] ?? null) || is_numeric($meta['to'] ?? null))% @endif
                                        @endif
                                    </div>
                                @elseif(($meta['employee_name'] ?? null))
                                    <div class="t-meta">{{ $meta['employee_name'] }}</div>
                                @endif
                                @if(($meta['title'] ?? null) && in_array($activity->action, ['task_created', 'task_status', 'task_note', 'task_deleted'], true))
                                    <div class="t-meta">Task: {{ $meta['title'] }}
                                        @if(($meta['assignee_name'] ?? null)) · {{ $meta['assignee_name'] }}@endif
                                    </div>
                                @endif
                                @if(($meta['snippet'] ?? null))
                                    <div class="t-meta">{{ $meta['snippet'] }}</div>
                                @endif
                                @if(($meta['work_status'] ?? null))
                                    <div class="t-meta">Status: {{ ucwords(str_replace('_',' ', $meta['work_status'])) }}
                                        @if(isset($meta['progress_percent'])) · {{ $meta['progress_percent'] }}%@endif
                                    </div>
                                @endif
                                @if(($meta['start_time'] ?? null) || ($meta['end_time'] ?? null))
                                    <div class="t-meta">
                                        Time: {{ $meta['start_time'] ?? '?' }} – {{ $meta['end_time'] ?? '?' }}
                                        @if(!empty($meta['duration_minutes']))
                                            · {{ intdiv((int)$meta['duration_minutes'], 60) }}h {{ ((int)$meta['duration_minutes']) % 60 }}m
                                        @endif
                                    </div>
                                @endif
                                @if(($meta['to'] ?? null) && ($meta['context'] ?? null))
                                    <div class="t-meta">To: {{ $meta['to'] }}</div>
                                @endif
                                @if(($meta['error'] ?? null))
                                    <div class="t-meta text-danger">{{ $meta['error'] }}</div>
                                @endif
                            </li>
                        @empty
                            <li><p class="t-title">No activity yet</p></li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="pj-panel">
                <div class="pj-panel-head"><h5>Assignment History</h5></div>
                <div class="pj-panel-body table-responsive">
                    <table class="table table-sm mb-0">
                        <thead>
                        <tr>
                            <th>When</th>
                            <th>Type</th>
                            <th>Employee</th>
                            <th>Previous</th>
                            <th>By</th>
                            <th>Email</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($project->assignmentHistory as $hist)
                            <tr>
                                <td>{{ optional($hist->assigned_at)->format('d M Y, h:i A') }}</td>
                                <td>{{ $hist->typeLabel() }}</td>
                                <td>
                                    {{ $hist->employee?->full_name }}
                                    <div class="small text-muted">{{ ucfirst($hist->status) }}</div>
                                    @if($hist->reason)
                                        <div class="small text-muted">{{ $hist->reason }}</div>
                                    @endif
                                </td>
                                <td>{{ $hist->previousEmployee?->full_name ?: '—' }}</td>
                                <td>{{ $hist->assigner?->full_name }}</td>
                                <td>
                                    <span class="pill {{ $hist->email_status === 'sent' ? 'st-completed' : ($hist->email_status === 'failed' ? 'st-cancelled' : 'st-not_started') }}">
                                        {{ $hist->email_status ?: '—' }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-3">No assignment history.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    @if($canDelete)
        <div class="pj-panel">
            <div class="pj-panel-body d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <strong style="color:var(--ink);">Cancel project</strong>
                    <div class="text-muted small">Marks the project as Cancelled, removes active team members, and keeps full assignment/activity history.</div>
                </div>
                <form method="POST" action="{{ route('projects.destroy', $project) }}"
                      onsubmit="return confirm('Cancel this project? History will be retained.');">
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-outline-danger" type="submit">Cancel Project</button>
                </form>
            </div>
        </div>
    @endif
</div>

@if($canAssign)
<div class="modal fade" id="assignModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" action="{{ route('projects.assign', $project) }}" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">Assign Team</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <label class="form-label">Employees</label>
                <x-employee-select
                    name="employee_ids[]"
                    :employees="$employees"
                    :exclude="$activeIds"
                    :multiple="true"
                    :required="true"
                    placeholder="Search & select employees to assign…"
                />
                <div class="form-text">Search by name, department or designation. Already assigned employees are hidden.</div>
                <label class="form-label mt-3">Note (optional)</label>
                <textarea name="reason" class="form-control" rows="2"></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn add-btn">Assign</button>
            </div>
        </form>
    </div>
</div>
@endif

@if($canReassign)
<div class="modal fade" id="reassignModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" action="{{ route('projects.reassign', $project) }}" class="modal-content">
            @csrf
            <input type="hidden" name="from_employee_id" id="reassign_from_id" value="{{ $canReassignSelf ? $user->id : '' }}">
            <div class="modal-header">
                <h5 class="modal-title">Reassign Project</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Current Assignee</label>
                    <input type="text" class="form-control" id="reassign_from_name" value="" readonly>
                </div>
                <div class="mb-3">
                    <label class="form-label">Assign To <span class="text-danger">*</span></label>
                    <x-employee-select
                        name="to_employee_id"
                        :employees="$employees"
                        :required="true"
                        placeholder="Search employee to reassign…"
                    />
                </div>
                <div class="mb-0">
                    <label class="form-label">Reason <span class="text-danger">*</span></label>
                    <textarea name="reason" class="form-control" rows="3" required minlength="5"
                              placeholder="Why are you reassigning this responsibility?"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn add-btn"
                        onclick="return confirm('Confirm reassignment? History will be preserved.');">Reassign</button>
            </div>
        </form>
    </div>
</div>
<script>
document.getElementById('reassignModal')?.addEventListener('show.bs.modal', function (event) {
    const btn = event.relatedTarget;
    if (!btn) return;
    document.getElementById('reassign_from_id').value = btn.getAttribute('data-from-id') || '';
    document.getElementById('reassign_from_name').value = btn.getAttribute('data-from-name') || '';
});
</script>
@endif
@endsection
