@extends('layouts.app')

@section('title', 'Admin Dashboard')
@section('heading', 'Admin Dashboard')

@section('page_actions')
<a href="{{ route('employees.create') }}" class="btn add-btn"><i class="fa-solid fa-plus me-1"></i> Add Employee</a>
@endsection

@push('styles')
<style>
    .db-wrap { color: var(--ink, #172033); }
    .db-wrap .welcome-bar {
        background: var(--card, #fff); color: var(--ink, #172033); border-radius: 10px; padding: 1rem 1.15rem;
        margin-bottom: 1rem; display: flex; justify-content: space-between; align-items: center;
        gap: 1rem; flex-wrap: wrap; box-shadow: 0 1px 3px rgba(16,24,40,.05);
        border: 1px solid var(--border, #E4E7EC);
    }
    .db-wrap .welcome-bar h2 {
        margin: 0;
        font-size: 1.2rem;
        font-weight: 700;
        color: var(--ink, #172033);
    }
    .db-wrap .welcome-bar p {
        margin: .2rem 0 0;
        color: var(--muted, #667085);
        font-size: .88rem;
    }
    .db-wrap .welcome-bar .db-date {
        text-align: right;
        font-size: .88rem;
        color: var(--muted, #667085);
        line-height: 1.35;
        background: var(--soft, #F9FAFB);
        border: 1px solid var(--border, #E4E7EC);
        border-radius: 8px;
        padding: .45rem .7rem;
    }
    .db-wrap .hrm-card {
        border: 1px solid var(--border, #E4E7EC);
        border-radius: 10px;
        background: var(--card, #fff);
        box-shadow: 0 1px 3px rgba(16,24,40,.05);
        overflow: hidden;
        height: 100%;
        color: var(--ink, #172033);
    }
    .db-wrap .hrm-card > .card-header {
        background: var(--table-head, #F9FAFB) !important;
        color: var(--ink, #172033) !important;
        font-weight: 700;
        font-size: .95rem;
        padding: .85rem 1.1rem;
        border: 0;
        border-bottom: 1px solid var(--border, #E4E7EC);
        border-radius: 0;
    }
    .db-wrap .hrm-card.hrm-card-danger > .card-header {
        background: rgba(240, 68, 56, 0.12) !important;
        color: #F04438 !important;
        border-bottom-color: rgba(240, 68, 56, 0.25);
    }
    .db-wrap .hrm-card .card-body {
        padding: 1rem 1.1rem;
        background: var(--card, #fff);
        color: var(--ink, #172033);
    }
    .db-wrap .stat-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: .35rem 0;
        font-size: .92rem;
        font-weight: 600;
        color: var(--ink, #172033);
        border-bottom: 1px solid var(--line, #f0f2f5);
    }
    .db-wrap .stat-row:last-child { border-bottom: 0; }
    .db-wrap .stat-row .num {
        font-size: 1.35rem;
        font-weight: 700;
        color: var(--ink, #172033);
        margin: 0;
        line-height: 1;
    }
    .db-wrap .stat-row .num.is-orange { color: #F79009; }
    .db-wrap .stat-row .num.is-red { color: #F04438; }
    .db-wrap .stat-row .hint {
        display: block;
        font-size: .72rem;
        color: var(--muted, #667085);
        font-weight: 500;
        margin-top: .15rem;
    }
    .db-wrap .stat-metric {
        display: block;
        text-decoration: none;
        color: inherit;
        background: var(--card, #fff);
        border: 1px solid var(--border, #e4e7ec);
        border-radius: 8px;
        box-shadow: 0 1px 3px rgba(16,24,40,.05);
        padding: .85rem 1rem;
        height: 100%;
        border-left: 3px solid var(--primary, #2563EB);
        transition: box-shadow .12s ease;
    }
    .db-wrap .stat-metric:hover { box-shadow: 0 2px 10px rgba(0,0,0,.1); color: inherit; }
    .db-wrap .stat-metric.is-amber { border-left-color: #F79009; }
    .db-wrap .stat-metric.is-red { border-left-color: #F04438; }
    .db-wrap .stat-metric.is-green { border-left-color: #12B76A; }
    .db-wrap .stat-metric .k {
        margin: 0;
        font-size: .78rem;
        color: var(--muted, #667085);
        font-weight: 600;
    }
    .db-wrap .stat-metric .v {
        margin: .3rem 0 0;
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--ink, #172033);
        line-height: 1.1;
    }
    .db-wrap .setup-banner {
        border: 1px solid var(--border, #e4e7ec);
        border-radius: 10px;
        background: var(--card, #fff);
        padding: .85rem 1rem;
        margin-bottom: 1rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 1rem;
        flex-wrap: wrap;
        box-shadow: 0 1px 3px rgba(16,24,40,.05);
        color: var(--ink, #172033);
    }
    .db-wrap .setup-banner h6 { margin: 0; font-weight: 700; color: var(--ink, #172033); font-size: .95rem; }
    .db-wrap .setup-banner p { margin: .2rem 0 0; color: var(--muted, #667085); font-size: .82rem; }
    .db-wrap .setup-banner .btn-setup {
        background: var(--primary, #2563EB); border: 0; color: var(--primary-text, #fff); font-weight: 600;
        border-radius: 8px; padding: .4rem .85rem; text-decoration: none; white-space: nowrap; font-size: .84rem;
    }
    .db-wrap .setup-banner .btn-setup:hover { filter: brightness(.96); color: var(--primary-text, #fff); }
    .db-wrap .setup-chips { display: flex; flex-wrap: wrap; gap: .35rem; margin-top: .45rem; }
    .db-wrap .setup-chip {
        font-size: .72rem; font-weight: 600; padding: .15rem .45rem; border-radius: 5px;
        background: color-mix(in srgb, var(--primary, #2563EB) 14%, transparent); color: var(--primary, #2563EB); border: 1px solid color-mix(in srgb, var(--primary, #2563EB) 35%, transparent);
    }
    .db-wrap .setup-chip.ok { background: #ecfdf3; color: #027a48; border-color: #abefc6; }
    .db-wrap .btn-ghost {
        border: 1px solid var(--border, #e4e7ec); color: var(--ink, #172033); background: var(--card, #fff); font-weight: 600;
        border-radius: 8px; font-size: .78rem; padding: .3rem .65rem; text-decoration: none;
    }
    .db-wrap .btn-ghost:hover { background: var(--soft, #f5f7fa); color: var(--ink, #172033); }
    .db-wrap .pill {
        display: inline-flex; align-items: center; padding: .15rem .45rem; border-radius: 5px;
        font-size: .72rem; font-weight: 700;
    }
    .db-wrap .pill-pending { background: #FFFAEB; color: #B54708; }
    .db-wrap .pill-ok { background: #ecfdf3; color: #027a48; }
    .db-wrap .pill-bad { background: #fef3f2; color: #b42318; }
    .db-wrap .pill-info { background: #eff8ff; color: #175cd3; }
    .db-wrap .emp { font-weight: 650; color: var(--ink, #172033); text-decoration: none; }
    .db-wrap .emp:hover { color: var(--primary, #2563EB); }
    .db-wrap .empty { color: var(--muted, #667085); text-align: center; padding: 1.25rem .5rem; font-size: .88rem; }
    .db-wrap .table thead th {
        font-size: .72rem; text-transform: uppercase; letter-spacing: .03em; color: var(--muted, #667085) !important;
        font-weight: 700; background: var(--table-head, #F9FAFB) !important; white-space: nowrap;
    }
    .db-wrap .table td { font-size: .88rem; vertical-align: middle; color: var(--ink, #172033); }
    .db-wrap .ql-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
        gap: .55rem;
        width: 100%;
        max-width: 100%;
        overflow: hidden;
    }
    .db-wrap .ql-item {
        display: flex; align-items: center; gap: .55rem; text-decoration: none;
        border: 1px solid var(--border, #e4e7ec); border-radius: 8px; padding: .55rem .65rem;
        background: var(--card, #fff); color: var(--ink, #172033); font-size: .82rem; font-weight: 600;
        min-width: 0; max-width: 100%; overflow: hidden;
    }
    .db-wrap .ql-item > span:last-child {
        min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
    }
    .db-wrap .ql-item:hover { background: var(--soft, #f5f7fa); color: var(--ink, #172033); }
    .db-wrap .ql-item .qi {
        width: 28px; height: 28px; border-radius: 6px; display: inline-flex;
        align-items: center; justify-content: center; background: color-mix(in srgb, var(--primary, #2563EB) 14%, transparent); color: var(--primary, #2563EB); flex: 0 0 auto;
        font-size: .8rem;
    }
</style>
@endpush

@section('content')
@php
    $userName = auth()->user()?->full_name ?: 'Admin';
    $hour = (int) now()->format('G');
    $hello = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
    $present = (int) ($stats['attendance_today'] ?? 0);
    $employees = (int) ($stats['employees'] ?? 0);
    $absent = max(0, $employees - $present);
@endphp
<div class="db-wrap">
    <div class="welcome-bar">
        <div>
            <h2>{{ $hello }}, {{ $userName }}</h2>
            <p>Here is what is happening across HRM today.</p>
        </div>
        <div class="db-date">
            <div>{{ now()->format('d M Y') }}</div>
            <div>{{ now()->format('l') }}</div>
        </div>
    </div>

    @if(($setupTotal ?? 0) > 0 && ($setupPercent ?? 100) < 100 && auth()->user()?->isSuperAdmin())
        <div class="setup-banner">
            <div>
                <h6><i class="fa-solid fa-sliders me-1" style="color:#2563EB"></i> Optional setup · {{ $setupDone }}/{{ $setupTotal }} ready</h6>
                <p>Geofence and notification emails are optional, but configuring them unlocks punch location checks and alert mail.</p>
                <div class="setup-chips">
                    @foreach($setupItems as $item)
                        <span class="setup-chip {{ $item['ready'] ? 'ok' : '' }}">
                            {{ $item['ready'] ? '✓' : '○' }} {{ $item['title'] }}
                        </span>
                    @endforeach
                </div>
            </div>
            <a href="{{ route('developer.optional-setup') }}" class="btn-setup">Open Optional Setup</a>
        </div>
    @endif

    <div class="row g-3 mb-3">
        <div class="col-md-6">
            <div class="hrm-card">
                <div class="card-header">Leave</div>
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-8">
                            <a href="{{ route('leaves.admin', ['status' => 0]) }}" class="stat-row text-decoration-none">
                                <span>New / Pending Leave</span>
                                <span class="num">{{ $stats['pending_leaves'] }}</span>
                            </a>
                            <a href="{{ route('leaves.admin') }}" class="stat-row text-decoration-none">
                                <span>Open leave queue</span>
                                <span class="num" style="font-size:1rem;color:#667085;">View all</span>
                            </a>
                            <div class="stat-row">
                                <span>Holidays {{ now()->year }}</span>
                                <span class="num">{{ $stats['holidays'] }}</span>
                            </div>
                            <div class="stat-row">
                                <span>Pending resignations</span>
                                <span class="num">{{ $stats['pending_resignations'] }}</span>
                            </div>
                        </div>
                        <div class="col-4 text-end">
                            <a href="{{ route('leaves.admin') }}" title="Leaves">
                                <i class="fa-solid fa-calendar-days" style="font-size:2.4rem;color:#2563EB;opacity:.85;"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="hrm-card">
                <div class="card-header">Attendance</div>
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-8">
                            <a href="{{ route('attendance.all') }}" class="stat-row text-decoration-none">
                                <div>
                                    <span class="num is-orange">{{ $present }}</span>
                                    <span class="hint">Overall Employees: {{ $employees }}</span>
                                </div>
                                <span>Today Present</span>
                            </a>
                            <a href="{{ route('attendance.all') }}" class="stat-row text-decoration-none">
                                <div>
                                    <span class="num is-red">{{ $absent }}</span>
                                    <span class="hint">Overall Employees: {{ $employees }}</span>
                                </div>
                                <span>Today Absent</span>
                            </a>
                        </div>
                        <div class="col-4 text-end">
                            <a href="{{ route('attendance.all') }}" title="Attendance">
                                <i class="fa-solid fa-clipboard-user" style="font-size:2.4rem;color:#2563EB;opacity:.85;"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-6 col-md-3">
            <a href="{{ route('employees.index') }}" class="stat-metric">
                <p class="k">Active Employees</p>
                <p class="v">{{ $stats['employees'] }}</p>
            </a>
        </div>
        <div class="col-6 col-md-3">
            <a href="{{ route('leaves.admin') }}" class="stat-metric is-amber">
                <p class="k">Pending Leaves</p>
                <p class="v">{{ $stats['pending_leaves'] }}</p>
            </a>
        </div>
        <div class="col-6 col-md-3">
            <a href="{{ route('tickets.manage') }}" class="stat-metric is-red">
                <p class="k">Open Tickets</p>
                <p class="v">{{ $stats['open_tickets'] }}</p>
            </a>
        </div>
        <div class="col-6 col-md-3">
            <a href="{{ route('attendance.all') }}" class="stat-metric is-green">
                <p class="k">Attendance Today</p>
                <p class="v">{{ $stats['attendance_today'] }}</p>
            </a>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-4">
            <div class="hrm-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Recent leave applications</span>
                    <a href="{{ route('leaves.admin') }}" class="btn btn-ghost">View all</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive border-0">
                        <table class="table mb-0">
                            <thead>
                            <tr>
                                <th class="ps-3">Employee</th>
                                <th>Type</th>
                                <th>Status</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($recentLeaves as $leave)
                                @php
                                    $label = strtolower((string) $leave->statusLabel());
                                    $pill = 'pill-pending';
                                    if (in_array($label, ['approved'], true)) {
                                        $pill = 'pill-ok';
                                    } elseif (in_array($label, ['declined', 'rejected'], true)) {
                                        $pill = 'pill-bad';
                                    }
                                @endphp
                                <tr>
                                    <td class="ps-3">
                                        <a class="emp" href="{{ $leave->employee ? route('employees.show', $leave->employee) : '#' }}">
                                            {{ $leave->employee?->full_name ?? '—' }}
                                        </a>
                                        <div class="small text-muted">{{ optional($leave->start_date)->format('d M') }} → {{ optional($leave->end_date)->format('d M') }}</div>
                                    </td>
                                    <td>{{ $leave->leaveType?->name ?? '—' }}</td>
                                    <td><span class="pill {{ $pill }}">{{ $leave->statusLabel() }}</span></td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="empty">No leave records yet.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="hrm-card hrm-card-danger">
                <div class="card-header">Pending expenses / resignations</div>
                <div class="card-body">
                    <a href="{{ route('expenses.admin') }}" class="stat-row text-decoration-none">
                        <span>Pending expenses</span>
                        <span class="num is-red">{{ $stats['pending_expenses'] }}</span>
                    </a>
                    <a href="{{ route('resignation.admin') }}" class="stat-row text-decoration-none">
                        <span>Pending resignations</span>
                        <span class="num is-red">{{ $stats['pending_resignations'] }}</span>
                    </a>
                    <a href="{{ route('holidays.index') }}" class="stat-row text-decoration-none">
                        <span>Holidays {{ now()->year }}</span>
                        <span class="num">{{ $stats['holidays'] }}</span>
                    </a>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="hrm-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Ticket Status</span>
                    <a href="{{ route('tickets.manage') }}" class="btn btn-ghost">View all</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive border-0">
                        <table class="table mb-0">
                            <thead>
                            <tr>
                                <th class="ps-3">#</th>
                                <th>Title</th>
                                <th>Status</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($recentTickets as $ticket)
                                @php
                                    $st = strtolower((string) $ticket->Status);
                                    $tpill = 'pill-pending';
                                    if (str_contains($st, 'close') || str_contains($st, 'resolved')) {
                                        $tpill = 'pill-ok';
                                    } elseif (str_contains($st, 'progress')) {
                                        $tpill = 'pill-info';
                                    }
                                @endphp
                                <tr>
                                    <td class="ps-3 text-muted">{{ $ticket->TicketID }}</td>
                                    <td>
                                        <a class="emp" href="{{ route('tickets.show', $ticket->TicketID) }}">
                                            {{ \Illuminate\Support\Str::limit($ticket->Title, 28) }}
                                        </a>
                                    </td>
                                    <td><span class="pill {{ $tpill }}">{{ $ticket->Status }}</span></td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="empty">No tickets yet.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-12">
            <div class="hrm-card">
                <div class="card-header">Quick links</div>
                <div class="card-body">
                    <div class="ql-grid">
                        @if(auth()->user()?->isSuperAdmin())
                        <a href="{{ route('developer.optional-setup') }}" class="ql-item">
                            <span class="qi"><i class="fa-solid fa-sliders"></i></span>
                            <span>Optional Setup</span>
                        </a>
                        @endif
                        <a href="{{ route('employees.index') }}" class="ql-item">
                            <span class="qi"><i class="fa-solid fa-user"></i></span>
                            <span>All Employees</span>
                        </a>
                        <a href="{{ route('attendance.all') }}" class="ql-item">
                            <span class="qi"><i class="fa-solid fa-calendar-check"></i></span>
                            <span>Attendance</span>
                        </a>
                        <a href="{{ route('candidates.index') }}" class="ql-item">
                            <span class="qi"><i class="fa-solid fa-user-group"></i></span>
                            <span>Candidates</span>
                        </a>
                        <a href="{{ route('overtime.index') }}" class="ql-item">
                            <span class="qi"><i class="fa-solid fa-clock"></i></span>
                            <span>Overtime</span>
                        </a>
                        <a href="{{ route('analytics.index') }}" class="ql-item">
                            <span class="qi"><i class="fa-solid fa-chart-pie"></i></span>
                            <span>Analytics</span>
                        </a>
                        <a href="{{ route('salary.calculate') }}" class="ql-item">
                            <span class="qi"><i class="fa-solid fa-calculator"></i></span>
                            <span>Salary</span>
                        </a>
                        <a href="{{ route('onboarding.index') }}" class="ql-item">
                            <span class="qi"><i class="fa-solid fa-user-plus"></i></span>
                            <span>Onboarding</span>
                        </a>
                        <a href="{{ route('activities.index') }}" class="ql-item">
                            <span class="qi"><i class="fa-solid fa-bullhorn"></i></span>
                            <span>Announcements</span>
                        </a>
                        <a href="{{ route('chat.index') }}" class="ql-item">
                            <span class="qi"><i class="fa-solid fa-comments"></i></span>
                            <span>Chat Room</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
