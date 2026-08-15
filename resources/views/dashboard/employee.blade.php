@extends('layouts.app')

@section('title', 'Employee Dashboard')
@section('heading', 'Employee Dashboard')

@section('page_actions')
<a href="{{ route('leaves.employee') }}" class="btn add-btn"><i class="fa-solid fa-plus me-1"></i> Apply Leave</a>
@endsection

@push('styles')
<style>
    .db-wrap { color: var(--ink, #172033); }
    .db-wrap .welcome-bar {
        background: var(--card, #fff); color: var(--ink, #172033); border-radius: 10px; padding: 1rem 1.15rem;
        margin-bottom: 1rem; display: flex; justify-content: space-between; align-items: center;
        gap: 1rem; flex-wrap: wrap; box-shadow: 0 1px 3px rgba(16,24,40,.05);
        border: 1px solid #E4E7EC;
    }
    .db-wrap .welcome-bar-left { display: flex; align-items: center; gap: .85rem; min-width: 0; }
    .db-wrap .welcome-bar .av {
        width: 48px; height: 48px; border-radius: 50%; object-fit: cover; flex-shrink: 0;
        border: 1px solid #E4E7EC; background: #F9FAFB;
    }
    .db-wrap .welcome-bar h2 { margin: 0; font-size: 1.15rem; font-weight: 700; color: var(--ink, #172033); }
    .db-wrap .welcome-bar p { margin: .15rem 0 0; color: var(--muted, #667085); font-size: .88rem; }
    .db-wrap .welcome-bar .meta { font-size: .78rem; color: var(--muted, #667085); margin-top: .1rem; }
    .db-wrap .welcome-bar .db-date {
        text-align: right; font-size: .88rem; color: var(--muted, #667085); line-height: 1.35;
        background: var(--soft, #F9FAFB); border: 1px solid var(--border, #E4E7EC); border-radius: 8px; padding: .45rem .7rem;
    }
    .db-wrap .welcome-actions { display: flex; gap: .45rem; flex-wrap: wrap; align-items: center; }
    .db-wrap .welcome-actions .btn-hero {
        border: 1px solid var(--border, #E4E7EC); background: var(--card, #fff); color: var(--ink, #172033);
        border-radius: 8px; padding: .4rem .75rem; font-size: .8rem; font-weight: 600; text-decoration: none;
    }
    .db-wrap .welcome-actions .btn-hero.primary { background: var(--primary); border-color: var(--primary); color: var(--primary-text, #fff); }
    .db-wrap .stat-metric {
        display: block; text-decoration: none; color: inherit; height: 100%;
        background: var(--card, #fff); border: 1px solid var(--border, #E4E7EC); border-radius: 10px;
        box-shadow: 0 1px 3px rgba(16,24,40,.05); padding: .85rem 1rem; border-left: 3px solid var(--primary, #2563EB);
    }
    .db-wrap .stat-metric.is-amber { border-left-color: #F79009; }
    .db-wrap .stat-metric.is-red { border-left-color: #F04438; }
    .db-wrap .stat-metric.is-green { border-left-color: #12B76A; }
    .db-wrap .stat-metric .k { margin: 0; font-size: .78rem; color: #667085; font-weight: 600; }
    .db-wrap .stat-metric .v { margin: .3rem 0 0; font-size: 1.4rem; font-weight: 700; color: var(--ink, #172033); line-height: 1.1; }
    .db-wrap .stat-metric .hint { margin: .25rem 0 0; font-size: .75rem; color: var(--muted, #667085); }
    .db-wrap .att-card {
        border: 1px solid var(--border, #E4E7EC); border-radius: 10px; background: var(--card, #fff); padding: .85rem 1rem;
        display: flex; align-items: center; gap: .85rem; box-shadow: 0 1px 3px rgba(16,24,40,.05);
    }
    .db-wrap .att-card .att-ico {
        width: 40px; height: 40px; border-radius: 8px; display: inline-flex; align-items: center; justify-content: center;
        background: #ecfdf3; color: #027a48; font-size: 1rem; flex-shrink: 0;
    }
    .db-wrap .att-card.is-out .att-ico { background: #FFFAEB; color: #B54708; }
    .db-wrap .att-card.is-none .att-ico { background: #F9FAFB; color: #667085; }
    .db-wrap .att-card .att-k { font-size: .72rem; color: #667085; font-weight: 700; text-transform: uppercase; }
    .db-wrap .att-card .att-v { font-size: 1rem; font-weight: 700; color: #172033; margin-top: .1rem; }
    .db-wrap .att-card .att-h { font-size: .8rem; color: #667085; margin-top: .15rem; }
    .db-wrap .hrm-card {
        background: var(--card, #fff); border: 1px solid var(--border, #E4E7EC); border-radius: 10px; overflow: hidden;
        box-shadow: 0 1px 3px rgba(16,24,40,.05); color: var(--ink, #172033);
        min-width: 0;
    }
    .db-wrap .hrm-card.is-fill { height: 100%; }
    .db-wrap .hrm-card > .card-header {
        background: var(--table-head, #F9FAFB) !important; color: var(--ink, #172033) !important; font-weight: 700; font-size: .95rem;
        padding: .85rem 1.1rem; border: 0; border-bottom: 1px solid var(--border, #E4E7EC);
        display: flex; justify-content: space-between; align-items: center; gap: .75rem; flex-wrap: wrap;
    }
    .db-wrap .btn-ghost {
        border: 1px solid var(--border, #E4E7EC); color: var(--ink, #172033); background: var(--card, #fff); font-weight: 600;
        border-radius: 8px; font-size: .78rem; padding: .3rem .65rem; text-decoration: none;
    }
    .db-wrap .pill {
        display: inline-flex; align-items: center; padding: .15rem .45rem; border-radius: 5px;
        font-size: .72rem; font-weight: 700;
    }
    .db-wrap .pill-pending { background: #FFFAEB; color: #B54708; }
    .db-wrap .pill-ok { background: #ECFDF3; color: #027A48; }
    .db-wrap .pill-bad { background: #FEF3F2; color: #B42318; }
    .db-wrap .pill-info { background: #EFF8FF; color: #175CD3; }
    .db-wrap .empty { color: #667085; text-align: center; padding: 1.25rem .5rem; font-size: .88rem; }
    .db-wrap .ticket-row {
        display: flex; justify-content: space-between; align-items: center; gap: .75rem;
        padding: .65rem 0; border-bottom: 1px solid #EAECF0; text-decoration: none; color: inherit;
    }
    .db-wrap .ticket-row:last-child { border-bottom: 0; }
    .db-wrap .ticket-row .tt { font-weight: 650; color: var(--ink, #172033); font-size: .9rem; }
    .db-wrap .ticket-row .ts { font-size: .75rem; color: var(--muted, #667085); margin-top: .1rem; }
    .db-wrap .holiday-row {
        display: flex; justify-content: space-between; align-items: center; gap: .75rem;
        padding: .5rem 0; border-bottom: 1px solid var(--border, #EAECF0);
    }
    .db-wrap .holiday-row:last-child { border-bottom: 0; }
    .db-wrap .holiday-row .hn { font-weight: 650; color: var(--ink, #172033); font-size: .9rem; min-width: 0; overflow: hidden; text-overflow: ellipsis; }
    .db-wrap .holiday-row .hd {
        font-size: .78rem; font-weight: 700; color: #2563EB; background: #EFF4FF;
        border-radius: 5px; padding: .2rem .5rem; white-space: nowrap; flex-shrink: 0;
    }
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
        border: 1px solid var(--border, #E4E7EC); border-radius: 8px; padding: .55rem .65rem;
        background: var(--card, #fff); color: var(--ink, #172033); font-size: .82rem; font-weight: 600;
        min-width: 0; max-width: 100%; overflow: hidden;
    }
    .db-wrap .ql-item > span:last-child {
        min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
    }
    .db-wrap .ql-item .qi {
        width: 28px; height: 28px; border-radius: 6px; display: inline-flex; align-items: center;
        justify-content: center; background: #EFF4FF; color: #2563EB; flex: 0 0 auto; font-size: .8rem;
    }
    .db-wrap .db-bottom-row > [class*="col-"] { min-width: 0; }
</style>
@endpush

@section('content')
@php
    $hour = (int) now()->format('G');
    $hello = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
    $attState = $stats['punched_in'] ? 'in' : ($todayAttendance ? 'out' : 'none');
    $attLabel = $attState === 'in' ? 'Punched In' : ($attState === 'out' ? 'Punched Out' : 'Not marked');
    $attHint = $todayAttendance
        ? 'In '.optional($todayAttendance->clock_in_time)->format('h:i A')
            .($todayAttendance->clock_out_time ? ' · Out '.optional($todayAttendance->clock_out_time)->format('h:i A') : '')
        : 'No attendance recorded for today';
@endphp
<div class="db-wrap">
    <div class="welcome-bar">
        <div class="welcome-bar-left">
            <img class="av" src="{{ $user->profile_image_url }}" alt="" onerror="this.src='{{ asset('assets/img/profiles/avatar-02.jpg') }}'">
            <div>
                <h2>{{ $hello }}, {{ $user->full_name }}</h2>
                <p>{{ $user->job_title ?: ($user->role ?? 'Employee') }}</p>
                <div class="meta">{{ $user->officialEmail() ?: $user->office_email ?: '—' }}</div>
            </div>
        </div>
        <div class="welcome-actions">
            <a class="btn-hero primary" href="{{ route('leaves.employee') }}"><i class="fa-solid fa-plus me-1"></i> Apply Leave</a>
            <a class="btn-hero" href="{{ route('profile.show') }}"><i class="fa-solid fa-user me-1"></i> Profile</a>
            <a class="btn-hero" href="{{ route('chat.index') }}"><i class="fa-solid fa-comments me-1"></i> Chat</a>
            <div class="db-date">
                <div>{{ now()->format('d M Y') }}</div>
                <div>{{ now()->format('l') }}</div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-6 col-md-3">
            <a href="{{ route('leaves.employee') }}" class="stat-metric">
                <p class="k">My leaves</p>
                <p class="v">{{ $stats['my_leaves'] }}</p>
                <p class="hint">Total applications</p>
            </a>
        </div>
        <div class="col-6 col-md-3">
            <a href="{{ route('leaves.employee') }}" class="stat-metric is-amber">
                <p class="k">Pending leaves</p>
                <p class="v">{{ $stats['pending_leaves'] }}</p>
                <p class="hint">Awaiting decision</p>
            </a>
        </div>
        <div class="col-6 col-md-3">
            <a href="{{ route('tickets.index') }}" class="stat-metric is-red">
                <p class="k">Open tickets</p>
                <p class="v">{{ $stats['open_tickets'] }}</p>
                <p class="hint">Support requests</p>
            </a>
        </div>
        <div class="col-6 col-md-3">
            <a href="{{ route('attendance.mine') }}" class="stat-metric is-green">
                <p class="k">Today</p>
                <p class="v" style="font-size:1.1rem;">{{ $attLabel }}</p>
                <p class="hint">{{ $attHint }}</p>
            </a>
        </div>
    </div>

    <div class="att-card mb-3 is-{{ $attState }}">
        <div class="att-ico"><i class="fa-solid fa-{{ $attState === 'in' ? 'circle-check' : ($attState === 'out' ? 'right-from-bracket' : 'clock') }}"></i></div>
        <div class="flex-grow-1">
            <div class="att-k">Attendance snapshot</div>
            <div class="att-v">{{ $attLabel }}</div>
            <div class="att-h">{{ $attHint }}</div>
        </div>
        <a href="{{ route('attendance.mine') }}" class="btn btn-ghost">Open attendance</a>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-lg-8">
            <div class="hrm-card is-fill">
                <div class="card-header">
                    <span>My recent leaves</span>
                    <a href="{{ route('leaves.employee') }}" class="btn btn-ghost">Apply / View</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive border-0">
                        <table class="table mb-0">
                            <thead>
                                <tr>
                                    <th class="ps-3">Type</th>
                                    <th>Dates</th>
                                    <th>Days</th>
                                    <th class="pe-3">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                            @forelse($myLeaves as $leave)
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
                                    <td class="ps-3 fw-semibold">{{ $leave->leaveType?->name ?? '—' }}</td>
                                    <td class="text-muted" style="white-space:nowrap;">
                                        {{ optional($leave->start_date)->format('d M') }} → {{ optional($leave->end_date)->format('d M Y') }}
                                    </td>
                                    <td>{{ $leave->no_of_days }}</td>
                                    <td class="pe-3"><span class="pill {{ $pill }}">{{ $leave->statusLabel() }}</span></td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="empty">No leave applications yet.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="hrm-card is-fill">
                <div class="card-header">
                    <span>My tickets</span>
                    <a href="{{ route('tickets.index') }}" class="btn btn-ghost">All</a>
                </div>
                <div class="card-body">
                    @forelse($myTickets as $ticket)
                        <a class="ticket-row" href="{{ route('tickets.show', $ticket->TicketID) }}">
                            <div>
                                <div class="tt">{{ \Illuminate\Support\Str::limit($ticket->Title, 32) }}</div>
                                <div class="ts">{{ optional($ticket->CreatedAt)->format('d M Y') ?? '—' }}</div>
                            </div>
                            @php
                                $st = strtolower((string) $ticket->Status);
                                $tp = str_contains($st, 'close') || str_contains($st, 'resolved') ? 'pill-ok'
                                    : (str_contains($st, 'progress') ? 'pill-info' : 'pill-pending');
                            @endphp
                            <span class="pill {{ $tp }}">{{ $ticket->Status }}</span>
                        </a>
                    @empty
                        <div class="empty">No tickets yet.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 db-bottom-row">
        <div class="col-lg-8">
            <div class="hrm-card">
                <div class="card-header">Quick actions</div>
                <div class="card-body">
                    <div class="ql-grid">
                        <a class="ql-item" href="{{ route('attendance.mine') }}">
                            <span class="qi"><i class="fa-solid fa-fingerprint"></i></span>
                            <span>Punch attendance</span>
                        </a>
                        <a class="ql-item" href="{{ route('leaves.employee') }}">
                            <span class="qi"><i class="fa-solid fa-calendar-plus"></i></span>
                            <span>Apply leave</span>
                        </a>
                        <a class="ql-item" href="{{ route('expenses.mine') }}">
                            <span class="qi"><i class="fa-solid fa-receipt"></i></span>
                            <span>Add expense</span>
                        </a>
                        <a class="ql-item" href="{{ route('tickets.index') }}">
                            <span class="qi"><i class="fa-solid fa-ticket"></i></span>
                            <span>Raise ticket</span>
                        </a>
                        <a class="ql-item" href="{{ route('chat.index') }}">
                            <span class="qi"><i class="fa-solid fa-comments"></i></span>
                            <span>Chat room</span>
                        </a>
                        <a class="ql-item" href="{{ route('holidays.index') }}">
                            <span class="qi"><i class="fa-solid fa-snowflake"></i></span>
                            <span>Holidays</span>
                        </a>
                        <a class="ql-item" href="{{ route('policies.index') }}">
                            <span class="qi"><i class="fa-solid fa-file-lines"></i></span>
                            <span>Policies</span>
                        </a>
                        <a class="ql-item" href="{{ route('profile.show') }}">
                            <span class="qi"><i class="fa-solid fa-user"></i></span>
                            <span>My profile</span>
                        </a>
                        <a class="ql-item" href="{{ route('resignation.mine') }}">
                            <span class="qi"><i class="fa-solid fa-door-open"></i></span>
                            <span>Resignation</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="hrm-card">
                <div class="card-header">
                    <span>Upcoming holidays</span>
                    <a href="{{ route('holidays.index') }}" class="btn btn-ghost">All</a>
                </div>
                <div class="card-body">
                    @forelse($upcomingHolidays as $h)
                        <div class="holiday-row">
                            <div class="hn">{{ $h['name'] ?? 'Holiday' }}</div>
                            <div class="hd">{{ $h['display_date'] ?? '—' }}</div>
                        </div>
                    @empty
                        <div class="empty">No upcoming holidays.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
