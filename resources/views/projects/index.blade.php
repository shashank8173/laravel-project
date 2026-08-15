@extends('layouts.app')

@section('title', 'Projects')
@section('heading', 'Projects')

@section('page_actions')
@can('create', App\Models\Project::class)
<a href="{{ route('projects.create') }}" class="btn add-btn">
    <i class="fa-solid fa-plus"></i> Create Project
</a>
@endcan
@endsection

@push('styles')
<style>
    .pj-wrap { --ink:#0f2744; --muted:#6b7c93; --line:#e8eef5; --soft:#f4f7fb; --accent:#ff9b44; }
    .pj-wrap .pj-metric {
        border:1px solid var(--line); border-radius:14px; background:#fff; padding:1rem 1.05rem; height:100%;
        position:relative; overflow:hidden; text-decoration:none; display:block; color:inherit;
        transition: border-color .15s ease, transform .15s ease;
    }
    .pj-wrap .pj-metric:hover { border-color:#ffd0a8; transform:translateY(-1px); }
    .pj-wrap .pj-metric.is-active { border-color:var(--accent); box-shadow:0 0 0 2px rgba(255,155,68,.15); }
    .pj-wrap .pj-metric::before { content:""; position:absolute; left:0; top:0; bottom:0; width:4px; background:var(--accent); }
    .pj-wrap .pj-metric.is-planning::before { background:#2563eb; }
    .pj-wrap .pj-metric.is-active-m::before { background:#16a34a; }
    .pj-wrap .pj-metric.is-completed::before { background:#0f766e; }
    .pj-wrap .pj-metric.is-hold::before { background:#f59e0b; }
    .pj-wrap .pj-metric.is-overdue::before { background:#ef4444; }
    .pj-wrap .pj-metric .k { font-size:.68rem; text-transform:uppercase; letter-spacing:.04em; color:var(--muted); font-weight:700; }
    .pj-wrap .pj-metric .v { font-size:1.35rem; font-weight:800; color:var(--ink); margin:.15rem 0 0; line-height:1.1; }
    .pj-wrap .pj-panel { background:#fff; border:1px solid var(--line); border-radius:18px; overflow:hidden; }
    .pj-wrap .pj-panel-head {
        display:flex; justify-content:space-between; align-items:center; gap:1rem; flex-wrap:wrap;
        padding:1rem 1.15rem; border-bottom:1px solid var(--line);
        background:linear-gradient(180deg,#fff,#fafbfd);
    }
    .pj-wrap .pj-panel-head h5 { margin:0; font-weight:750; color:var(--ink); }
    .pj-wrap .pj-panel-body { padding:1.15rem; }
    .pj-wrap .name { font-weight:750; color:var(--ink); margin:0; }
    .pj-wrap .sub { font-size:.78rem; color:var(--muted); }
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
    .pj-wrap .prog {
        height:8px; background:#eef2f7; border-radius:999px; overflow:hidden; min-width:90px;
    }
    .pj-wrap .prog > span { display:block; height:100%; background:linear-gradient(90deg,#ff9b44,#ff7a18); }
    .pj-wrap .table > :not(caption) > * > * { vertical-align:middle; }
    .pj-wrap .team-chip {
        display:inline-flex; padding:.15rem .45rem; margin:.1rem; border-radius:8px;
        background:var(--soft); color:var(--ink); font-size:.72rem; font-weight:600;
    }
</style>
@endpush

@section('content')
@php
    $filterBase = array_filter([
        'q' => $q ?: null,
        'status' => $status ?: null,
        'priority' => $priority ?: null,
        'manager_id' => $managerId ?: null,
        'date_from' => $dateFrom ?: null,
        'date_to' => $dateTo ?: null,
    ], fn ($v) => $v !== null && $v !== '');
@endphp
<div class="pj-wrap">
    <div class="row g-3 mb-3">
        <div class="col-6 col-md-4 col-xl-2">
            <a href="{{ route('projects.index', $filterBase) }}" class="pj-metric {{ $metric === '' ? 'is-active' : '' }}">
                <div class="k">Total</div>
                <p class="v">{{ $counts['total'] }}</p>
            </a>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <a href="{{ route('projects.index', array_merge($filterBase, ['metric' => 'planning'])) }}"
               class="pj-metric is-planning {{ $metric === 'planning' ? 'is-active' : '' }}">
                <div class="k">Planning</div>
                <p class="v">{{ $counts['planning'] }}</p>
            </a>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <a href="{{ route('projects.index', array_merge($filterBase, ['metric' => 'active'])) }}"
               class="pj-metric is-active-m {{ $metric === 'active' ? 'is-active' : '' }}">
                <div class="k">Active</div>
                <p class="v">{{ $counts['active'] }}</p>
            </a>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <a href="{{ route('projects.index', array_merge($filterBase, ['metric' => 'completed'])) }}"
               class="pj-metric is-completed {{ $metric === 'completed' ? 'is-active' : '' }}">
                <div class="k">Completed</div>
                <p class="v">{{ $counts['completed'] }}</p>
            </a>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <a href="{{ route('projects.index', array_merge($filterBase, ['metric' => 'on_hold'])) }}"
               class="pj-metric is-hold {{ $metric === 'on_hold' ? 'is-active' : '' }}">
                <div class="k">On Hold</div>
                <p class="v">{{ $counts['on_hold'] }}</p>
            </a>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <a href="{{ route('projects.index', array_merge($filterBase, ['metric' => 'overdue'])) }}"
               class="pj-metric is-overdue {{ $metric === 'overdue' ? 'is-active' : '' }}">
                <div class="k">Overdue</div>
                <p class="v">{{ $counts['overdue'] }}</p>
            </a>
        </div>
    </div>

    <div class="pj-panel mb-3">
        <div class="pj-panel-head">
            <h5>Filters</h5>
        </div>
        <div class="pj-panel-body">
            <form method="GET" action="{{ route('projects.index') }}" class="row g-2 align-items-end">
                @if($metric)<input type="hidden" name="metric" value="{{ $metric }}">@endif
                <div class="col-md-3">
                    <label class="form-label">Search</label>
                    <input type="text" name="q" value="{{ $q }}" class="form-control" placeholder="Name, client…">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All</option>
                        @foreach(App\Models\Project::STATUSES as $st)
                            <option value="{{ $st }}" @selected($status === $st)>{{ ucwords(str_replace('_',' ',$st)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Priority</label>
                    <select name="priority" class="form-select">
                        <option value="">All</option>
                        @foreach(App\Models\Project::PRIORITIES as $pr)
                            <option value="{{ $pr }}" @selected($priority === $pr)>{{ ucfirst($pr) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Project Manager</label>
                    <select name="manager_id" class="form-select">
                        <option value="">All</option>
                        @foreach($managers as $m)
                            <option value="{{ $m->id }}" @selected($managerId === (int)$m->id)>{{ $m->full_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-1">
                    <label class="form-label">From</label>
                    <input type="date" name="date_from" value="{{ $dateFrom }}" class="form-control">
                </div>
                <div class="col-md-1">
                    <label class="form-label">To</label>
                    <input type="date" name="date_to" value="{{ $dateTo }}" class="form-control">
                </div>
                <div class="col-md-1">
                    <button class="btn add-btn w-100" type="submit">Go</button>
                </div>
            </form>
        </div>
    </div>

    <div class="pj-panel">
        <div class="pj-panel-head">
            <h5>All Projects</h5>
            <span class="sub">{{ $projects->total() }} result(s)</span>
        </div>
        <div class="pj-panel-body table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                <tr>
                    <th>Project</th>
                    <th>Client</th>
                    <th>Manager</th>
                    <th>Start</th>
                    <th>Deadline</th>
                    <th>Priority</th>
                    <th>Status</th>
                    <th>Progress</th>
                    <th>Team</th>
                    <th>Created By</th>
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody>
                @forelse($projects as $project)
                    <tr>
                        <td>
                            <a href="{{ route('projects.show', $project) }}" class="name text-decoration-none">{{ $project->name }}</a>
                            @if($project->isOverdue())
                                <div class="sub text-danger fw-bold">Overdue</div>
                            @endif
                        </td>
                        <td>{{ $project->client_name ?: '—' }}</td>
                        <td>{{ $project->manager?->full_name ?: '—' }}</td>
                        <td>{{ optional($project->start_date)->format('d M Y') }}</td>
                        <td>{{ optional($project->end_date)->format('d M Y') }}</td>
                        <td><span class="pill pr-{{ $project->priority }}">{{ $project->priorityLabel() }}</span></td>
                        <td><span class="pill st-{{ $project->status }}">{{ $project->statusLabel() }}</span></td>
                        <td style="min-width:120px;">
                            <div class="d-flex align-items-center gap-2">
                                <div class="prog flex-grow-1"><span style="width:{{ (int)$project->progress }}%"></span></div>
                                <span class="sub fw-bold">{{ (int)$project->progress }}%</span>
                            </div>
                        </td>
                        <td style="max-width:180px;">
                            @forelse($project->activeMemberships as $m)
                                <span class="team-chip">{{ $m->employee?->full_name }}</span>
                            @empty
                                <span class="sub">—</span>
                            @endforelse
                        </td>
                        <td>{{ $project->creator?->full_name ?: '—' }}</td>
                        <td>
                            <div class="d-flex flex-wrap gap-1">
                                <a href="{{ route('projects.show', $project) }}" class="btn btn-sm btn-outline-primary" title="View">View</a>
                                @can('update', $project)
                                    <a href="{{ route('projects.edit', $project) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                                @endcan
                                @can('assign', $project)
                                    <a href="{{ route('projects.show', $project) }}#assign-team" class="btn btn-sm btn-outline-success">Assign</a>
                                @endcan
                                <a href="{{ route('projects.show', $project) }}#activity" class="btn btn-sm btn-outline-dark">Activity</a>
                                @can('delete', $project)
                                    <form method="POST" action="{{ route('projects.destroy', $project) }}"
                                          onsubmit="return confirm('Cancel this project? History will be kept.');">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger" type="submit">Cancel</button>
                                    </form>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="11" class="text-center text-muted py-4">No projects found.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
            <div class="mt-3">{{ $projects->links() }}</div>
        </div>
    </div>
</div>
@endsection
