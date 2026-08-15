@extends('layouts.app')

@section('title', 'Attendance Reports')
@section('heading', 'Attendance Reports')

@section('page_actions')
<a href="{{ route('attendance.mine') }}" class="btn btn-outline-secondary me-1">
    <i class="fa-solid fa-user-clock me-1"></i> My Attendance
</a>
@if($isAdmin)
    <a href="{{ route('attendance.admin') }}" class="btn btn-outline-secondary me-1">Attendance (MN)</a>
    <a href="{{ route('attendance.upload') }}" class="btn add-btn">
        <i class="fa-solid fa-upload"></i> Upload
    </a>
@endif
@endsection

@push('styles')
<style>
    .att-wrap {
        --ink:#0f2744; --muted:#6b7c93; --line:#e8eef5; --soft:#f4f7fb; --accent:#ff9b44;
        --card:#fff; --panel-head:linear-gradient(180deg,#fff,#fafbfd); --table-head:#fafbfd; --input-bg:#fff;
    }
    .att-wrap .att-hero {
        border:1px solid var(--line); border-radius:18px; margin-bottom:1rem;
        background:
            radial-gradient(900px 220px at 0% 0%, rgba(255,155,68,.18), transparent 55%),
            var(--hero-grad, linear-gradient(135deg, #0f2744 0%, #16375f 55%, #1b466f 100%));
        color:#fff; padding:1.15rem 1.3rem;
        display:flex; justify-content:space-between; align-items:center; gap:1rem; flex-wrap:wrap;
    }
    .att-wrap .att-hero h2 { margin:0; font-size:1.25rem; font-weight:800; color:#fff; }
    .att-wrap .att-hero p { margin:.3rem 0 0; opacity:.85; font-size:.9rem; color:#fff; }
    .att-wrap .att-hero .chip {
        background:rgba(255,255,255,.12); border:1px solid rgba(255,255,255,.18);
        border-radius:12px; padding:.55rem .85rem; font-size:.82rem; font-weight:650; color:#fff;
    }

    .att-wrap .att-panel {
        background:var(--card); border:1px solid var(--line); border-radius:18px; overflow:visible; margin-bottom:1rem;
    }
    .att-wrap .att-panel-head {
        display:flex; align-items:center; justify-content:space-between; gap:1rem; flex-wrap:wrap;
        padding:1rem 1.15rem; border-bottom:1px solid var(--line);
        background:var(--panel-head);
        border-radius:18px 18px 0 0;
    }
    .att-wrap .att-panel-head h5 { margin:0; font-weight:750; color:var(--ink); font-size:1rem; }
    .att-wrap .att-panel-head .sub { display:block; font-size:.78rem; color:var(--muted); margin-top:.15rem; }
    .att-wrap .att-panel-body { padding:1.15rem; background:var(--card); }
    .att-wrap .form-label { font-size:.8rem; font-weight:650; color:var(--muted); }
    .att-wrap .form-select, .att-wrap .form-control {
        border-radius:11px; border-color:var(--line);
        background:var(--input-bg); color:var(--ink);
    }
    .att-wrap .form-select:focus, .att-wrap .form-control:focus {
        border-color:#ffd0a8; box-shadow:0 0 0 .2rem rgba(255,155,68,.15);
        background:var(--input-bg); color:var(--ink);
    }

    .att-wrap .today-tabs { display:flex; gap:.75rem; flex-wrap:wrap; margin-bottom:1rem; }
    .att-wrap .today-tab {
        flex:1 1 240px; border:1px solid var(--line); border-radius:16px; background:var(--card);
        padding:1rem 1.05rem; display:flex; align-items:center; gap:.85rem; text-align:left;
        position:relative; overflow:hidden; cursor:pointer; color:var(--ink);
        transition: border-color .15s ease, transform .15s ease, box-shadow .15s ease;
    }
    .att-wrap .today-tab:hover {
        transform:translateY(-2px); border-color:#ffd0a8;
        box-shadow:0 10px 24px var(--shadow, rgba(15,39,68,.08));
    }
    .att-wrap .today-tab::before { content:""; position:absolute; left:0; top:0; bottom:0; width:4px; }
    .att-wrap .today-tab.is-leave::before { background:#f59e0b; }
    .att-wrap .today-tab.is-absent::before { background:#ef4444; }
    .att-wrap .today-tab .ico {
        width:42px; height:42px; border-radius:12px; display:grid; place-items:center; flex-shrink:0;
    }
    .att-wrap .today-tab.is-leave .ico { background:#fef3c7; color:#b45309; }
    .att-wrap .today-tab.is-absent .ico { background:#fee2e2; color:#b91c1c; }
    .att-wrap .today-tab .k { font-size:.86rem; font-weight:750; color:var(--ink); margin:0; }
    .att-wrap .today-tab .d { font-size:.75rem; color:var(--muted); margin:.15rem 0 0; }
    .att-wrap .today-tab .count {
        margin-left:auto; min-width:2.1rem; height:2.1rem; padding:0 .55rem; border-radius:999px;
        display:inline-flex; align-items:center; justify-content:center; font-weight:800; font-size:.95rem;
    }
    .att-wrap .today-tab.is-leave .count { background:#fff7ed; color:#9a3412; }
    .att-wrap .today-tab.is-absent .count { background:#fef2f2; color:#b91c1c; }

    .att-today-modal .modal-content { border:0; border-radius:18px; overflow:hidden; background:var(--card, #fff); color:var(--ink, #0f2744); }
    .att-today-modal .modal-header {
        background:linear-gradient(135deg,#0f2744,#1b466f); color:#fff; border:0;
    }
    .att-today-modal .modal-header .btn-close { filter:invert(1); }
    .att-today-modal .modal-body { background:var(--card, #fff); color:var(--ink, #0f2744); }
    .att-today-modal .nav-pills .nav-link {
        border-radius:999px; font-weight:700; color:var(--ink, #0f2744); border:1px solid var(--line, #e8eef5);
        background:var(--soft, #f4f7fb);
    }
    .att-today-modal .nav-pills .nav-link.active {
        background:#ff9b44; border-color:#ff9b44; color:#fff;
    }
    .att-today-modal .list-wrap {
        max-height:360px; overflow:auto; border:1px solid var(--line, #e8eef5); border-radius:12px; padding:.35rem .9rem;
        background:var(--soft, #f4f7fb);
    }
    .att-today-modal .att-person {
        display:flex; justify-content:space-between; gap:.5rem; align-items:flex-start;
        padding:.65rem 0; border-bottom:1px solid var(--line, #e8eef5);
    }
    .att-today-modal .att-person:last-child { border-bottom:0; }
    .att-today-modal .att-person strong { color:var(--ink, #0f2744); font-size:.92rem; }
    .att-today-modal .att-empty { color:var(--muted, #6b7c93); font-size:.9rem; padding:.85rem 0; text-align:center; }

    .att-wrap .att-metric {
        border:1px solid var(--line); border-radius:16px; background:var(--card); padding:1rem 1.05rem; height:100%;
        position:relative; overflow:hidden;
    }
    .att-wrap .att-metric::before { content:""; position:absolute; left:0; top:0; bottom:0; width:4px; background:var(--accent); }
    .att-wrap .att-metric.is-green::before { background:#16a34a; }
    .att-wrap .att-metric.is-red::before { background:#ef4444; }
    .att-wrap .att-metric.is-amber::before { background:#f59e0b; }
    .att-wrap .att-metric.is-blue::before { background:#2563eb; }
    .att-wrap .att-metric.is-purple::before { background:#7c3aed; }
    .att-wrap .att-metric .k { font-size:.68rem; text-transform:uppercase; letter-spacing:.04em; color:var(--muted); font-weight:700; margin:0; }
    .att-wrap .att-metric .v { font-size:1.35rem; font-weight:800; color:var(--ink); margin:.2rem 0 0; line-height:1.1; }

    .att-wrap .emp-hero {
        border:1px solid var(--line); border-radius:18px; background:var(--card); overflow:hidden; margin-bottom:1rem;
    }
    .att-wrap .emp-hero-top {
        padding:1.15rem 1.25rem; display:flex; gap:1rem; align-items:center; flex-wrap:wrap;
        background:linear-gradient(135deg, rgba(255,155,68,.12), transparent);
        border-bottom:1px solid var(--line);
    }
    .att-wrap .emp-avatar {
        width:58px; height:58px; border-radius:50%; object-fit:cover;
        border:3px solid #ffe0c2; background:var(--soft);
    }
    .att-wrap .emp-name { font-size:1.15rem; font-weight:800; color:var(--ink); margin:0 0 .15rem; }
    .att-wrap .emp-sub { color:var(--muted); font-size:.86rem; margin:0; }

    .att-wrap .today-banner {
        border:1px solid var(--line); border-radius:18px; background:var(--card); padding:1.1rem 1.25rem; margin-bottom:1rem;
        display:flex; justify-content:space-between; gap:1rem; flex-wrap:wrap; align-items:center;
    }
    .att-wrap .today-banner .t { font-weight:800; color:var(--ink); margin:0; }
    .att-wrap .today-banner .d { color:var(--muted); font-size:.86rem; margin:.15rem 0 0; }

    .att-wrap .table > :not(caption) > * > * { vertical-align:middle; }
    .att-wrap .table thead th {
        font-size:.72rem; text-transform:uppercase; letter-spacing:.04em; color:var(--muted);
        font-weight:700; border-bottom-color:var(--line); background:var(--table-head); white-space:nowrap;
    }
    .att-wrap .table td { border-color:var(--line); color:var(--ink); font-size:.9rem; background:var(--card); }
    .att-wrap .table tbody tr:last-child td { border-bottom:0; }
    .att-wrap .name { font-weight:750; color:var(--ink); margin:0; }
    .att-wrap .sub { font-size:.78rem; color:var(--muted); }
    .att-wrap .date-chip {
        display:inline-flex; flex-direction:column; align-items:center; justify-content:center;
        width:46px; height:46px; border-radius:11px; background:var(--soft); color:var(--ink);
        border:1px solid var(--line); flex-shrink:0;
    }
    .att-wrap .date-chip .m { font-size:.62rem; font-weight:800; text-transform:uppercase; line-height:1; }
    .att-wrap .date-chip .d { font-size:1.05rem; font-weight:800; line-height:1.1; margin-top:.05rem; }
    .att-wrap .att-pill {
        display:inline-flex; align-items:center; padding:.28rem .65rem; border-radius:999px;
        font-size:.72rem; font-weight:700;
    }
    .att-wrap .att-pill.holiday { background:#dcfce7; color:#15803d; }
    .att-wrap .att-pill.leave { background:#fef3c7; color:#b45309; }
    .att-wrap .att-pill.absent { background:#fee2e2; color:#b91c1c; }
    .att-wrap .att-pill.off { background:#e0e7ff; color:#4338ca; }
    .att-wrap .att-pill.short { background:#fee2e2; color:#b91c1c; }
    .att-wrap .att-pill.ontime { background:#dcfce7; color:#15803d; }
    .att-wrap .att-pill.late { background:#ffedd5; color:#c2410c; }
    .att-wrap .time-chip {
        display:inline-flex; padding:.25rem .5rem; border-radius:8px; background:var(--soft);
        color:var(--ink); font-size:.82rem; font-weight:700; font-variant-numeric:tabular-nums;
    }
    .att-wrap .map-link {
        font-size:.78rem; font-weight:700; color:#0b5cab; text-decoration:none; white-space:nowrap;
    }
    .att-wrap .map-link:hover { text-decoration:underline; }
    .att-wrap .map-muted { font-size:.78rem; color:var(--muted); }
    .att-wrap .btn-edit {
        border-radius:10px; background:var(--accent); border:0; color:#fff; width:34px; height:34px;
        display:inline-grid; place-items:center;
    }
    .att-wrap .btn-edit:hover { background:#ff8326; color:#fff; }
    .att-wrap .btn-del {
        border-radius:10px; background:#fee2e2; border:1px solid #fecaca; color:#b91c1c; width:34px; height:34px;
        display:inline-grid; place-items:center; padding:0;
    }
    .att-wrap .btn-del:hover { background:#ef4444; border-color:#ef4444; color:#fff; }
    .att-wrap .action-btns { display:inline-flex; gap:.35rem; align-items:center; justify-content:flex-end; }
    .att-wrap .btn-ghost {
        border:1px solid var(--line); color:var(--ink); background:var(--card); font-weight:650;
        border-radius:10px; font-size:.8rem; padding:.35rem .75rem; text-decoration:none;
    }
    .att-wrap .btn-ghost:hover { border-color:#ffd0a8; background:var(--soft); color:var(--ink); }

    html[data-theme="dark"] .att-wrap {
        --ink:#e8eef8; --muted:#a8b6cc; --line:#243044; --soft:#1a2232; --card:#141b27;
        --panel-head:linear-gradient(180deg,#171e2c,#141b27); --table-head:#171e2c; --input-bg:#0f1520;
    }
    html[data-theme="dark-blue"] .att-wrap {
        --ink:#eaf2ff; --muted:#9db4d4; --line:#1a3358; --soft:#102240; --card:#0c1a31;
        --panel-head:linear-gradient(180deg,#0e1f3c,#0c1a31); --table-head:#0e1f3c; --input-bg:#081528;
    }
    html[data-theme="light"] .att-wrap {
        --ink:#0f2744; --muted:#6b7c93; --line:#e8eef5; --soft:#f4f7fb; --card:#ffffff;
        --panel-head:linear-gradient(180deg,#fff,#fafbfd); --table-head:#fafbfd; --input-bg:#ffffff;
    }
    html[data-theme="dark"] .att-wrap .map-link,
    html[data-theme="dark-blue"] .att-wrap .map-link { color:#7dd3fc; }

    html[data-theme="dark"] .att-today-modal,
    html[data-theme="dark-blue"] .att-today-modal {
        --ink:#e8eef8; --muted:#a8b6cc; --line:#243044; --soft:#1a2232; --card:#141b27;
    }
    html[data-theme="dark-blue"] .att-today-modal {
        --ink:#eaf2ff; --muted:#9db4d4; --line:#1a3358; --soft:#102240; --card:#0c1a31;
    }
</style>
@endpush

@section('content')
@php
    $parseDate = function ($value) {
        try {
            return $value ? \Carbon\Carbon::parse($value) : null;
        } catch (\Throwable) {
            return null;
        }
    };
@endphp
<div class="att-wrap">
    <div class="att-hero">
        <div>
            <h2>Attendance reports</h2>
            <p>Review team status today or open a full month report for any employee.</p>
        </div>
        <div class="chip"><i class="fa-regular fa-calendar me-1"></i> {{ \Carbon\Carbon::parse($today)->format('d M Y') }}</div>
    </div>

@if($isAdmin)
<div class="today-tabs">
    <button type="button" class="today-tab is-leave" data-bs-toggle="modal" data-bs-target="#todayStatusModal" data-tab="leave">
        <span class="ico"><i class="fas fa-calendar-times"></i></span>
        <div>
            <p class="k">On leave today</p>
            <p class="d">{{ \Carbon\Carbon::parse($today)->format('d M Y') }}</p>
        </div>
        <span class="count">{{ $onLeave->count() }}</span>
    </button>
    <button type="button" class="today-tab is-absent" data-bs-toggle="modal" data-bs-target="#todayStatusModal" data-tab="absent">
        <span class="ico"><i class="fas fa-user-times"></i></span>
        <div>
            <p class="k">Absent today</p>
            <p class="d">{{ \Carbon\Carbon::parse($today)->format('d M Y') }}</p>
        </div>
        <span class="count">{{ $absentToday->count() }}</span>
    </button>
</div>

<div class="modal fade att-today-modal" id="todayStatusModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title fw-bold mb-0">Today status</h5>
                    <div class="small" style="opacity:.8;">{{ \Carbon\Carbon::parse($today)->format('l, d M Y') }}</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <ul class="nav nav-pills gap-2 mb-3" id="todayStatusTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="tab-leave-btn" data-bs-toggle="pill" data-bs-target="#tab-leave" type="button" role="tab">
                            On leave ({{ $onLeave->count() }})
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="tab-absent-btn" data-bs-toggle="pill" data-bs-target="#tab-absent" type="button" role="tab">
                            Absent ({{ $absentToday->count() }})
                        </button>
                    </li>
                </ul>
                <div class="tab-content">
                    <div class="tab-pane fade show active" id="tab-leave" role="tabpanel">
                        <div class="list-wrap">
                            @forelse($onLeave as $leave)
                                <div class="att-person">
                                    <strong>{{ $leave['name'] }}</strong>
                                    <span class="small text-muted">{{ $leave['start_date'] }} → {{ $leave['end_date'] }}</span>
                                </div>
                            @empty
                                <div class="att-empty">No employees on leave today.</div>
                            @endforelse
                        </div>
                    </div>
                    <div class="tab-pane fade" id="tab-absent" role="tabpanel">
                        <div class="list-wrap">
                            @forelse($absentToday as $absent)
                                <div class="att-person">
                                    <strong>{{ $absent['name'] }}</strong>
                                </div>
                            @empty
                                <div class="att-empty">No absentees found for today.</div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn add-btn" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endif

<div class="att-panel">
    <div class="att-panel-head">
        <div>
            <h5><i class="fa-solid fa-filter me-1" style="color:var(--accent)"></i> Find attendance</h5>
            <span class="sub">Select employee, month and year</span>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <button type="button" id="exportButton" class="btn btn-sm btn-success">
                <i class="fa-solid fa-file-csv me-1"></i>Export CSV
            </button>
            @if($isAdmin && $filtered && $employeeId && $month && $year)
                <a href="{{ route('salary.calculate', ['id' => $employeeId, 'month' => $month, 'year' => $year]) }}" class="btn btn-sm add-btn">
                    <i class="fa-solid fa-calculator me-1"></i>Salary
                </a>
            @endif
            @if($isAdmin)
                <a href="{{ route('leaves.admin') }}" class="btn btn-sm btn-ghost">Leaves</a>
                <a href="{{ route('holidays.index') }}" class="btn btn-sm btn-ghost">Holidays</a>
            @endif
        </div>
    </div>
    <div class="att-panel-body">
        <form method="GET" action="{{ route('attendance.all') }}" id="attendanceFilterForm" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label" for="employee">Employee</label>
                <select name="employee_id" id="employee" class="form-select js-employee-select" required
                        data-placeholder="Search employee by name, department or designation…"
                        data-ajax="0">
                    <option value="">Select employee</option>
                    @foreach($employees as $employee)
                        <option
                            value="{{ $employee->id }}"
                            data-name="{{ $employee->full_name }}"
                            data-department="{{ $employee->department?->name }}"
                            data-designation="{{ $employee->designation?->name }}"
                            data-department-id="{{ $employee->department_id }}"
                            data-designation-id="{{ $employee->designation_id }}"
                            data-emp-id="{{ $employee->emp_id }}"
                            data-doj="{{ optional($employee->doj)->format('Y-m-d') }}"
                            @selected((string) $employeeId === (string) $employee->id)
                        >{{ $employee->full_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label" for="month">Month</label>
                <select name="month" id="month" class="form-select" required>
                    <option value="">Select month</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label" for="year">Year</label>
                <select name="year" id="year" class="form-select" required>
                    <option value="">Select year</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn add-btn w-100">
                    <i class="fa-solid fa-magnifying-glass me-1"></i>Search
                </button>
            </div>
        </form>
    </div>
</div>

@if($filtered && $selectedEmployee)
    <div class="emp-hero">
        <div class="emp-hero-top">
            <img src="{{ $selectedEmployee->profile_image_url }}" class="emp-avatar" alt=""
                 onerror="this.src='{{ asset('assets/img/profiles/avatar-02.jpg') }}'">
            <div style="flex:1;min-width:200px;">
                <p class="emp-name" id="name">{{ $selectedEmployee->full_name }}</p>
                <p class="emp-sub">
                    {{ $selectedEmployee->designation?->name ?: '—' }}
                    · {{ $selectedEmployee->department?->name ?: '—' }}
                    · {{ \Carbon\Carbon::createFromDate($year, $month, 1)->format('F Y') }}
                </p>
                <input type="hidden" id="email" value="{{ $selectedEmployee->officialEmail() }}">
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-6 col-md-3 col-xl">
            <div class="att-metric is-green"><div class="k">Present</div><p class="v" id="presentDaysCount">{{ $counts['present'] }}</p></div>
        </div>
        <div class="col-6 col-md-3 col-xl">
            <div class="att-metric is-blue"><div class="k">Saturdays</div><p class="v" id="saturdayDaysCount">{{ $counts['saturday'] }}</p></div>
        </div>
        <div class="col-6 col-md-3 col-xl">
            <div class="att-metric is-purple"><div class="k">Sundays</div><p class="v" id="sundayDaysCount">{{ $counts['sunday'] }}</p></div>
        </div>
        <div class="col-6 col-md-3 col-xl">
            <div class="att-metric"><div class="k">Holidays</div><p class="v" id="holidayDaysCount">{{ $counts['holiday'] }}</p></div>
        </div>
        <div class="col-6 col-md-3 col-xl">
            <div class="att-metric is-amber"><div class="k">Leaves</div><p class="v" id="leaveDaysCount">{{ $counts['leave'] }}</p></div>
        </div>
        <div class="col-6 col-md-3 col-xl">
            <div class="att-metric is-red"><div class="k">Absent</div><p class="v" id="absentDaysCount">{{ $counts['absent'] }}</p></div>
        </div>
        <div class="col-6 col-md-3 col-xl">
            <div class="att-metric is-amber"><div class="k">Total late</div><p class="v" id="totallateCount">{{ $counts['late'] }}</p></div>
        </div>
    </div>
@elseif(! $filtered)
    <div class="today-banner">
        <div>
            <p class="t" id="name">Today attendance</p>
            <p class="d">{{ \Carbon\Carbon::parse($today)->format('l, d M Y') }} · select employee + month + year for full report</p>
        </div>
        <a href="{{ route('attendance.all', ['employee_id' => auth()->id(), 'month' => now()->month, 'year' => now()->year]) }}" class="btn add-btn">
            My month report
        </a>
    </div>
@endif

<div class="att-panel mb-0">
    <div class="att-panel-head">
        <div>
            <h5><i class="fa-solid fa-clipboard-list me-1" style="color:var(--accent)"></i> Attendance log</h5>
            <span class="sub">{{ count($dayRows) }} row(s)</span>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table align-middle mb-0" id="attendanceTable">
            <thead>
            <tr>
                <th class="ps-3">#</th>
                <th>Employee</th>
                <th>Date</th>
                <th>Clock in</th>
                <th>Clock out</th>
                <th>Working time</th>
                <th>Extra / remaining</th>
                <th>Late</th>
                <th>Location</th>
                @if($isAdmin)
                    <th class="text-end pe-3">Action</th>
                @endif
            </tr>
            </thead>
            <tbody>
            @if($unauthorized)
                <tr>
                    <td colspan="{{ $isAdmin ? 10 : 9 }}" class="text-center text-danger py-4">
                        You are not authorized to view this employee's attendance or employee is restricted.
                    </td>
                </tr>
            @elseif(count($dayRows) === 0)
                <tr>
                    <td colspan="{{ $isAdmin ? 10 : 9 }}" class="text-center text-muted py-4">
                        @if($filtered)
                            No data available for this employee.
                        @else
                            No attendance data found for today. Use the filter above to search.
                        @endif
                    </td>
                </tr>
            @else
                @foreach($dayRows as $row)
                    @php $date = $parseDate($row['date'] ?? null); @endphp
                    <tr>
                        <td class="ps-3 text-muted">{{ $row['index'] }}</td>
                        <td>
                            <p class="name">{{ $row['employee_name'] }}</p>
                        </td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <span class="date-chip">
                                    <span class="m">{{ $date ? $date->format('M') : '—' }}</span>
                                    <span class="d">{{ $date ? $date->format('d') : '?' }}</span>
                                </span>
                                <div>
                                    <div class="name" style="font-size:.9rem;">{{ $date ? $date->format('D') : ($row['date'] ?? '—') }}</div>
                                    <div class="sub">{{ $row['date'] ?? '' }}</div>
                                </div>
                            </div>
                        </td>
                        @if(($row['type'] ?? '') === 'present')
                            @php
                                $inLat = $row['clock_in_latitude'] ?? null;
                                $inLng = $row['clock_in_longitude'] ?? null;
                                $outLat = $row['clock_out_latitude'] ?? null;
                                $outLng = $row['clock_out_longitude'] ?? null;
                            @endphp
                            <td><span class="time-chip">{{ $row['clock_in'] }}</span></td>
                            <td><span class="time-chip">{{ $row['clock_out'] ?: '—' }}</span></td>
                            <td>{{ $row['total_working_time'] }}</td>
                            <td class="sub">{{ $row['extra_label'] }}</td>
                            <td>
                                @php $late = $row['late_status'] ?? ''; @endphp
                                @if($late === 'Short Leave')
                                    <span class="att-pill short">Short Leave</span>
                                @elseif(stripos($late, 'Late') !== false)
                                    <span class="att-pill late">{{ $late }}</span>
                                @elseif($late !== '')
                                    <span class="att-pill ontime">{{ $late }}</span>
                                @endif
                            </td>
                            <td style="white-space:nowrap;">
                                @if($inLat && $inLng)
                                    <a class="map-link" target="_blank" rel="noopener"
                                       href="https://www.google.com/maps?q={{ $inLat }},{{ $inLng }}"
                                       title="Open punch-in location">Map in</a>
                                @else
                                    <span class="map-muted">In —</span>
                                @endif
                                <span class="map-muted"> · </span>
                                @if($outLat && $outLng)
                                    <a class="map-link" target="_blank" rel="noopener"
                                       href="https://www.google.com/maps?q={{ $outLat }},{{ $outLng }}"
                                       title="Open punch-out location">Map out</a>
                                @else
                                    <span class="map-muted">Out —</span>
                                @endif
                            </td>
                        @else
                            @php
                                $pill = match($row['type'] ?? '') {
                                    'holiday' => 'holiday',
                                    'leave' => 'leave',
                                    'absent' => 'absent',
                                    default => 'off',
                                };
                            @endphp
                            <td colspan="5" class="text-center">
                                <span class="att-pill {{ $pill }}">{{ $row['label'] ?? '' }}</span>
                            </td>
                            <td><span class="map-muted">—</span></td>
                        @endif
                        @if($isAdmin)
                            <td class="text-end pe-3">
                                <div class="action-btns">
                                    <a href="#"
                                       class="btn btn-edit edit-btn"
                                       data-bs-toggle="modal"
                                       data-bs-target="#editModal"
                                       data-id="{{ $row['attendance_id'] ?? '' }}"
                                       data-employee="{{ $row['employee_id'] ?? $employeeId ?? '' }}"
                                       data-date="{{ $row['date'] }}"
                                       data-clockin="{{ $row['edit_clock_in'] ?? '' }}"
                                       data-clockout="{{ $row['edit_clock_out'] ?? '' }}"
                                       title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    @if(! empty($row['attendance_id']))
                                        <form method="POST" action="{{ route('attendance.delete-record') }}" class="d-inline"
                                              onsubmit="return confirm('Delete this attendance record? This cannot be undone.');">
                                            @csrf
                                            <input type="hidden" name="delete_id" value="{{ $row['attendance_id'] }}">
                                            <button type="submit" class="btn btn-del" title="Delete">
                                                <i class="fas fa-trash-can"></i>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        @endif
                    </tr>
                @endforeach
            @endif
            </tbody>
        </table>
    </div>
</div>
</div>

@if($isAdmin)
<div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border:0;border-radius:16px;">
            <form method="POST" action="{{ route('attendance.update-record') }}" id="editForm">
                @csrf
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold">Edit attendance</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body att-wrap">
                    <input type="hidden" name="id" id="editId">
                    <input type="hidden" name="employee_id" id="editEmployeeId" value="{{ $employeeId ?? auth()->id() }}">
                    <div class="mb-3">
                        <label class="form-label">Date</label>
                        <input type="date" name="date" id="editDate" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Clock in</label>
                        <input type="time" name="clock_in" id="editClockIn" class="form-control" required>
                    </div>
                    <div class="mb-1">
                        <label class="form-label">Clock out</label>
                        <input type="time" name="clock_out" id="editClockOut" class="form-control">
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0 d-flex justify-content-between flex-wrap gap-2">
                    <button type="button" class="btn btn-outline-danger" id="modalDeleteBtn" style="display:none;">
                        <i class="fa-solid fa-trash-can me-1"></i> Delete record
                    </button>
                    <div class="d-flex gap-2 ms-auto">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn add-btn">Save changes</button>
                    </div>
                </div>
            </form>
            <form method="POST" action="{{ route('attendance.delete-record') }}" id="modalDeleteForm" class="d-none">
                @csrf
                <input type="hidden" name="delete_id" id="modalDeleteId">
            </form>
        </div>
    </div>
</div>
@endif
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const todayModal = document.getElementById('todayStatusModal');
    if (todayModal) {
        todayModal.addEventListener('show.bs.modal', function (event) {
            const trigger = event.relatedTarget;
            const which = trigger?.getAttribute('data-tab') || 'leave';
            const btn = document.getElementById(which === 'absent' ? 'tab-absent-btn' : 'tab-leave-btn');
            if (btn) {
                bootstrap.Tab.getOrCreateInstance(btn).show();
            }
        });
    }

    const employeeSelect = document.getElementById('employee');
    const monthSelect = document.getElementById('month');
    const yearSelect = document.getElementById('year');
    const initialMonth = @json((string) ($month ?? ''));
    const initialYear = @json((string) ($year ?? ''));
    const monthNames = ['January','February','March','April','May','June','July','August','September','October','November','December'];

    function parseDojParts(doj) {
        if (!doj) return null;
        const m = String(doj).match(/^(\d{4})-(\d{2})-(\d{2})/);
        if (m) {
            return { year: parseInt(m[1], 10), month: parseInt(m[2], 10) };
        }
        const d = new Date(doj);
        if (Number.isNaN(d.getTime())) return null;
        return { year: d.getFullYear(), month: d.getMonth() + 1 };
    }

    function rebuildYearOptions(preferredYear) {
        const selectedOption = employeeSelect.options[employeeSelect.selectedIndex];
        const doj = selectedOption ? selectedOption.getAttribute('data-doj') : null;
        const dojParts = parseDojParts(doj);
        const currentYear = new Date().getFullYear();
        const endYear = Math.max(currentYear, preferredYear || currentYear, 2026);
        const startYear = dojParts ? dojParts.year : (currentYear - 10);

        const prev = preferredYear || yearSelect.value || initialYear || String(currentYear);
        yearSelect.innerHTML = '<option value="">Select Year</option>';
        for (let y = startYear; y <= endYear; y++) {
            const option = document.createElement('option');
            option.value = String(y);
            option.text = String(y);
            yearSelect.appendChild(option);
        }
        yearSelect.value = String(prev);
        if (!yearSelect.value) {
            yearSelect.value = String(currentYear);
        }
    }

    function rebuildMonthOptions(preferredMonth) {
        const selectedOption = employeeSelect.options[employeeSelect.selectedIndex];
        const doj = selectedOption ? selectedOption.getAttribute('data-doj') : null;
        const dojParts = parseDojParts(doj);
        const yearValue = parseInt(yearSelect.value || initialYear || String(new Date().getFullYear()), 10);
        const prev = preferredMonth || monthSelect.value || initialMonth || String(new Date().getMonth() + 1);

        monthSelect.innerHTML = '<option value="">Select Month</option>';
        for (let m = 1; m <= 12; m++) {
            if (dojParts && yearValue === dojParts.year && m < dojParts.month) {
                continue;
            }
            const option = document.createElement('option');
            option.value = String(m);
            option.text = monthNames[m - 1];
            monthSelect.appendChild(option);
        }
        monthSelect.value = String(prev);
        if (!monthSelect.value && monthSelect.options.length > 1) {
            monthSelect.selectedIndex = 1;
        }
    }

    function refreshDateFilters(opts = {}) {
        rebuildYearOptions(opts.year);
        rebuildMonthOptions(opts.month);
    }

    employeeSelect.addEventListener('change', function () {
        refreshDateFilters({
            year: yearSelect.value || initialYear,
            month: monthSelect.value || initialMonth,
        });
    });

    yearSelect.addEventListener('change', function () {
        rebuildMonthOptions(monthSelect.value || initialMonth);
    });

    refreshDateFilters({
        year: initialYear,
        month: initialMonth,
    });

    document.querySelectorAll('.edit-btn').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            const id = this.dataset.id || '';
            document.getElementById('editId').value = id;
            document.getElementById('editDate').value = this.dataset.date || '';
            document.getElementById('editClockIn').value = this.dataset.clockin || '';
            document.getElementById('editClockOut').value = this.dataset.clockout || '';
            if (this.dataset.employee) {
                document.getElementById('editEmployeeId').value = this.dataset.employee;
            }
            const delBtn = document.getElementById('modalDeleteBtn');
            const delId = document.getElementById('modalDeleteId');
            if (delBtn && delId) {
                if (id) {
                    delId.value = id;
                    delBtn.style.display = '';
                } else {
                    delId.value = '';
                    delBtn.style.display = 'none';
                }
            }
        });
    });

    const modalDeleteBtn = document.getElementById('modalDeleteBtn');
    if (modalDeleteBtn) {
        modalDeleteBtn.addEventListener('click', function () {
            const id = document.getElementById('modalDeleteId')?.value || '';
            if (!id) {
                alert('No saved attendance record to delete.');
                return;
            }
            if (!confirm('Delete this attendance record? This cannot be undone.')) return;
            document.getElementById('modalDeleteForm').submit();
        });
    }

    const exportBtn = document.getElementById('exportButton');
    if (exportBtn) {
        exportBtn.addEventListener('click', function () {
            const table = document.getElementById('attendanceTable');
            let csv = [];
            table.querySelectorAll('tr').forEach(function (row) {
                const cols = [];
                row.querySelectorAll('th,td').forEach(function (cell) {
                    let text = (cell.innerText || '').replace(/\s+/g, ' ').trim();
                    cols.push('"' + text.replace(/"/g, '""') + '"');
                });
                csv.push(cols.join(','));
            });
            const blob = new Blob([csv.join('\n')], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            const empName = (document.getElementById('name')?.innerText || 'attendance').trim().replace(/\s+/g, '_');
            link.download = empName + '_attendance.csv';
            link.click();
        });
    }
});
</script>
@endpush
