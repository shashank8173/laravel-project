@extends('layouts.app')

@section('title', 'Overtime')
@section('heading', 'Overtime Report')

@section('page_actions')
<button type="button" class="btn btn-notice" data-bs-toggle="modal" data-bs-target="#overtimeNoticeModal">
    <i class="fa-solid fa-circle-info me-1"></i> Notice
</button>
@endsection

@push('styles')
<style>
    .ot-wrap { --ink:#0f2744; --muted:#6b7c93; --line:#e8eef5; --soft:#f4f7fb; --accent:#ff9b44; }
    .ot-wrap .ot-panel { background:#fff; border:1px solid var(--line); border-radius:18px; overflow:hidden; height:100%; }
    .ot-wrap .ot-panel-head {
        display:flex; justify-content:space-between; align-items:center; gap:1rem; flex-wrap:wrap;
        padding:1rem 1.15rem; border-bottom:1px solid var(--line);
        background:linear-gradient(180deg,#fff,#fafbfd);
    }
    .ot-wrap .ot-panel-head h5 { margin:0; font-weight:750; color:var(--ink); font-size:1rem; }
    .ot-wrap .ot-panel-head .sub { font-size:.78rem; color:var(--muted); }
    .ot-wrap .ot-panel-body { padding:1.15rem; }
    .ot-wrap .ot-metric {
        border:1px solid var(--line); border-radius:14px; background:#fff; padding:1rem 1.1rem; height:100%;
        position:relative; overflow:hidden;
    }
    .ot-wrap .ot-metric::before { content:""; position:absolute; left:0; top:0; bottom:0; width:4px; background:var(--accent); }
    .ot-wrap .ot-metric.is-blue::before { background:#2563eb; }
    .ot-wrap .ot-metric.is-green::before { background:#16a34a; }
    .ot-wrap .ot-metric.is-amber::before { background:#f59e0b; }
    .ot-wrap .ot-metric .k { font-size:.7rem; text-transform:uppercase; letter-spacing:.04em; color:var(--muted); font-weight:700; }
    .ot-wrap .ot-metric .v { font-size:1.45rem; font-weight:800; color:var(--ink); margin:.15rem 0 0; line-height:1.1; }
    .ot-wrap .ot-metric .hint { font-size:.72rem; color:var(--muted); margin-top:.2rem; }
    .ot-wrap .form-label { font-size:.8rem; font-weight:600; color:var(--muted); }
    .ot-wrap .table > :not(caption) > * > * { vertical-align:middle; }
    .ot-wrap .name { font-weight:700; color:var(--ink); margin:0; }
    .ot-wrap .time-chip {
        display:inline-flex; padding:.28rem .55rem; border-radius:8px; background:var(--soft);
        color:var(--ink); font-size:.8rem; font-weight:650; font-variant-numeric:tabular-nums;
    }
    .ot-wrap .extra-pill {
        display:inline-flex; padding:.28rem .65rem; border-radius:999px;
        background:#fff7ed; color:#9a3412; font-size:.75rem; font-weight:750;
    }
    .ot-wrap .extra-pill.is-high { background:#fee2e2; color:#b91c1c; }
    .ot-wrap .rank-row {
        display:flex; justify-content:space-between; align-items:center; gap:.75rem;
        padding:.7rem 0; border-bottom:1px solid var(--line);
    }
    .ot-wrap .rank-row:last-child { border-bottom:0; padding-bottom:0; }
    .ot-wrap .rank-badge {
        width:28px; height:28px; border-radius:50%; display:inline-flex; align-items:center; justify-content:center;
        background:#fff7ed; color:#9a3412; font-size:.75rem; font-weight:800; flex-shrink:0;
    }
    .ot-wrap .btn-notice {
        border:1px solid #f59e0b; color:#92400e; background:#fffbeb; font-weight:700; border-radius:999px;
        padding:.35rem .9rem; font-size:.8rem;
    }
    .ot-wrap .btn-notice:hover { background:#fef3c7; color:#78350f; border-color:#d97706; }
    .btn-notice {
        border:1px solid #f59e0b; color:#92400e; background:#fffbeb; font-weight:700; border-radius:999px;
        padding:.35rem .9rem; font-size:.8rem;
    }
    .ot-wrap .ot-notice-body p { margin:0 0 .75rem; color:var(--ink); font-size:.92rem; }
    .ot-wrap .ot-notice-body ul { margin:0; padding-left:1.1rem; color:#4b5c73; font-size:.9rem; }
    .ot-wrap .ot-notice-body li { margin-bottom:.4rem; }
</style>
@endpush

@section('content')
<div class="ot-wrap">
    <div class="row g-3 mb-3">
        <div class="col-6 col-lg-3">
            <div class="ot-metric">
                <div class="k">Employees with OT</div>
                <p class="v">{{ $stats['employees'] }}</p>
                <div class="hint">{{ $monthLabel }}</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="ot-metric is-blue">
                <div class="k">Total overtime</div>
                <p class="v">{{ $stats['hours'] }}h</p>
                <div class="hint">Across all entries</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="ot-metric is-green">
                <div class="k">OT entries</div>
                <p class="v">{{ $stats['entries'] }}</p>
                <div class="hint">Days with extra time</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="ot-metric is-amber">
                <div class="k">Average extra</div>
                <p class="v">{{ sprintf('%dh %02dm', intdiv($stats['avg_mins'], 60), $stats['avg_mins'] % 60) }}</p>
                <div class="hint">Per OT entry</div>
            </div>
        </div>
    </div>

    <div class="ot-panel mb-3">
        <div class="ot-panel-head">
            <div>
                <h5><i class="fa-solid fa-filter me-1" style="color:var(--accent)"></i> Filters</h5>
                <span class="sub">Office logout time: {{ $officeLogout }}</span>
            </div>
            @if($q !== '' || (int)$month !== (int)now()->month || (int)$year !== (int)now()->year)
                <a href="{{ route('overtime.index') }}" class="btn btn-sm btn-outline-secondary">Clear</a>
            @endif
        </div>
        <div class="ot-panel-body">
            <form method="GET" action="{{ route('overtime.index') }}" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Search employee</label>
                    <input type="search" name="q" value="{{ $q }}" class="form-control" placeholder="Name">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Month</label>
                    <select name="month" class="form-select">
                        @for($m = 1; $m <= 12; $m++)
                            <option value="{{ $m }}" @selected((int)$month === $m)>{{ \Carbon\Carbon::create()->month($m)->format('F') }}</option>
                        @endfor
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Year</label>
                    <input type="number" name="year" value="{{ $year }}" class="form-control" min="2020" max="2100">
                </div>
                <div class="col-md-4">
                    <button class="btn add-btn me-1">Apply</button>
                    <a href="{{ route('overtime.index') }}" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="ot-panel">
                <div class="ot-panel-head">
                    <h5><i class="fa-solid fa-clock me-1" style="color:var(--accent)"></i> Overtime entries</h5>
                    <span class="sub">{{ $records->count() }} record(s)</span>
                </div>
                <div class="table-responsive p-2">
                    <table class="table align-middle mb-0">
                        <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Date</th>
                            <th>In</th>
                            <th>Out</th>
                            <th class="text-end">Extra</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($records as $row)
                            <tr>
                                <td><p class="name">{{ $row['employee'] }}</p></td>
                                <td>{{ $row['date'] }}</td>
                                <td><span class="time-chip">{{ $row['in'] }}</span></td>
                                <td><span class="time-chip">{{ $row['out'] }}</span></td>
                                <td class="text-end">
                                    <span class="extra-pill {{ $row['extra_mins'] >= 120 ? 'is-high' : '' }}">
                                        {{ $row['extra_label'] }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">
                                    No overtime found for this period (clock-out after office logout time).
                                </td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="ot-panel">
                <div class="ot-panel-head">
                    <h5><i class="fa-solid fa-trophy me-1" style="color:var(--accent)"></i> Top OT this month</h5>
                </div>
                <div class="ot-panel-body">
                    @forelse($topEmployees as $i => $emp)
                        <div class="rank-row">
                            <div class="d-flex align-items-center gap-2">
                                <span class="rank-badge">{{ $i + 1 }}</span>
                                <div>
                                    <div class="name" style="font-size:.92rem;">{{ $emp['employee'] }}</div>
                                    <div class="small text-muted">{{ $emp['entries'] }} day(s)</div>
                                </div>
                            </div>
                            <span class="extra-pill">
                                {{ sprintf('%dh %02dm', intdiv($emp['mins'], 60), $emp['mins'] % 60) }}
                            </span>
                        </div>
                    @empty
                        <p class="text-muted mb-0 small">No overtime ranked for this month.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="overtimeNoticeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border:0;border-radius:16px;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold">
                    <i class="fa-solid fa-circle-info me-1" style="color:#f59e0b;"></i> Notice — Overtime
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body ot-wrap ot-notice-body">
                <p><strong>How overtime is calculated</strong></p>
                <ul>
                    <li>Uses attendance clock-in / clock-out records for the selected month.</li>
                    <li>Office logout time is taken from Office Timing (currently <strong>{{ $officeLogout }}</strong>).</li>
                    <li>If an employee clocks out <strong>after</strong> that time, the difference is counted as overtime.</li>
                    <li>Entries with zero extra minutes are hidden from this report.</li>
                </ul>
                <p class="mb-0" style="font-size:.85rem;color:#6b7c93;">
                    Tip: Update Office Timing if your standard end time has changed.
                </p>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection
