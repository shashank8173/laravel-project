@extends('layouts.app')

@section('title', 'Leaves')
@section('heading', 'Leaves')

@section('page_actions')
<a href="{{ route('holidays.index') }}" class="btn btn-outline-secondary me-1">
    <i class="fa-solid fa-snowflake me-1"></i> Holidays
</a>
<a href="{{ route('attendance.mine') }}" class="btn btn-outline-secondary me-1">
    <i class="fa-solid fa-fingerprint me-1"></i> Attendance
</a>
<button type="button" class="btn add-btn" id="focusApplyBtn">
    <i class="fa-solid fa-plus me-1"></i> Apply leave
</button>
@endsection

@push('styles')
<style>
    .lv-wrap { --ink:#0f2744; --muted:#6b7c93; --line:#e8eef5; --soft:#f4f7fb; --accent:#ff9b44; }
    .lv-wrap .lv-hero {
        border:1px solid var(--line); border-radius:18px; overflow:hidden; margin-bottom:1rem;
        background:
            radial-gradient(900px 220px at 0% 0%, rgba(255,155,68,.18), transparent 55%),
            linear-gradient(135deg, #0f2744 0%, #16375f 55%, #1b466f 100%);
        color:#fff; padding:1.15rem 1.3rem;
        display:flex; justify-content:space-between; align-items:center; gap:1rem; flex-wrap:wrap;
    }
    .lv-wrap .lv-hero h2 { margin:0; font-size:1.25rem; font-weight:800; letter-spacing:-.02em; }
    .lv-wrap .lv-hero p { margin:.3rem 0 0; opacity:.85; font-size:.9rem; }
    .lv-wrap .lv-hero .chip {
        background:rgba(255,255,255,.12); border:1px solid rgba(255,255,255,.18);
        border-radius:12px; padding:.55rem .85rem; font-size:.82rem; font-weight:650;
    }

    .lv-wrap .lv-metric {
        display:block; text-decoration:none; color:inherit; height:100%;
        border:1px solid var(--line); border-radius:16px; background:#fff;
        padding:1rem 1.05rem; position:relative; overflow:hidden;
        transition: transform .15s ease, border-color .15s ease, box-shadow .15s ease;
    }
    .lv-wrap .lv-metric:hover {
        transform:translateY(-2px); border-color:#ffd0a8;
        box-shadow:0 10px 24px rgba(15,39,68,.08); color:inherit;
    }
    .lv-wrap .lv-metric::before {
        content:""; position:absolute; left:0; top:0; bottom:0; width:4px; background:var(--accent);
    }
    .lv-wrap .lv-metric.is-blue::before { background:#2563eb; }
    .lv-wrap .lv-metric.is-amber::before { background:#f59e0b; }
    .lv-wrap .lv-metric.is-green::before { background:#16a34a; }
    .lv-wrap .lv-metric.is-red::before { background:#ef4444; }
    .lv-wrap .lv-metric .top { display:flex; justify-content:space-between; align-items:flex-start; gap:.75rem; }
    .lv-wrap .lv-metric .ico {
        width:36px; height:36px; border-radius:11px; display:inline-flex; align-items:center; justify-content:center;
        background:#fff7ed; color:#c2410c; flex:0 0 auto;
    }
    .lv-wrap .lv-metric.is-blue .ico { background:#eff6ff; color:#1d4ed8; }
    .lv-wrap .lv-metric.is-amber .ico { background:#fffbeb; color:#b45309; }
    .lv-wrap .lv-metric.is-green .ico { background:#f0fdf4; color:#15803d; }
    .lv-wrap .lv-metric.is-red .ico { background:#fef2f2; color:#b91c1c; }
    .lv-wrap .lv-metric .k {
        font-size:.68rem; text-transform:uppercase; letter-spacing:.04em; color:var(--muted); font-weight:700; margin:0;
    }
    .lv-wrap .lv-metric .v {
        font-size:1.45rem; font-weight:800; color:var(--ink); margin:.3rem 0 0; line-height:1.1;
    }
    .lv-wrap .lv-metric .hint { font-size:.75rem; color:var(--muted); margin:.3rem 0 0; }

    .lv-wrap .lv-panel {
        background:#fff; border:1px solid var(--line); border-radius:18px; overflow:hidden; height:100%;
    }
    .lv-wrap .lv-panel-head {
        display:flex; justify-content:space-between; align-items:center; gap:1rem; flex-wrap:wrap;
        padding:1rem 1.15rem; border-bottom:1px solid var(--line);
        background:linear-gradient(180deg,#fff,#fafbfd);
    }
    .lv-wrap .lv-panel-head h5 { margin:0; font-weight:750; color:var(--ink); font-size:1rem; }
    .lv-wrap .lv-panel-head .sub { display:block; font-size:.78rem; color:var(--muted); margin-top:.15rem; }
    .lv-wrap .lv-panel-body { padding:1.1rem 1.15rem; }

    .lv-wrap .form-label { font-size:.8rem; font-weight:650; color:var(--muted); margin-bottom:.3rem; }
    .lv-wrap .form-control, .lv-wrap .form-select {
        border-radius:11px; border-color:var(--line); padding:.55rem .75rem; color:var(--ink);
    }
    .lv-wrap .form-control:focus, .lv-wrap .form-select:focus {
        border-color:#ffd0a8; box-shadow:0 0 0 .2rem rgba(255,155,68,.15);
    }
    .lv-wrap .btn-submit {
        background:linear-gradient(145deg,#ff9b44,#f07a1a); border:0; color:#fff;
        font-weight:750; border-radius:12px; padding:.65rem 1rem; width:100%;
        box-shadow:0 8px 18px rgba(255,155,68,.28);
    }
    .lv-wrap .btn-submit:hover { filter:brightness(1.03); color:#fff; }

    .lv-wrap .table > :not(caption) > * > * { vertical-align:middle; }
    .lv-wrap .table thead th {
        font-size:.72rem; text-transform:uppercase; letter-spacing:.04em; color:var(--muted);
        font-weight:700; border-bottom-color:var(--line); background:#fafbfd; white-space:nowrap;
    }
    .lv-wrap .table tbody tr:last-child td { border-bottom:0; }
    .lv-wrap .table td { border-color:var(--line); color:var(--ink); font-size:.9rem; }
    .lv-wrap .type-name { font-weight:700; color:var(--ink); }
    .lv-wrap .reason { color:var(--muted); font-size:.84rem; max-width:220px; }
    .lv-wrap .pill {
        display:inline-flex; align-items:center; padding:.22rem .55rem; border-radius:999px;
        font-size:.72rem; font-weight:700;
    }
    .lv-wrap .pill-pending { background:#fef3c7; color:#b45309; }
    .lv-wrap .pill-ok { background:#dcfce7; color:#15803d; }
    .lv-wrap .pill-bad { background:#fee2e2; color:#b91c1c; }
    .lv-wrap .pill-info { background:#e0f2fe; color:#0369a1; }
    .lv-wrap .empty { color:var(--muted); text-align:center; padding:2rem .5rem; font-size:.9rem; }
    .lv-wrap .btn-ghost {
        border:1px solid var(--line); color:var(--ink); background:#fff; font-weight:650;
        border-radius:10px; font-size:.8rem; padding:.35rem .75rem; text-decoration:none;
    }
    .lv-wrap .btn-ghost:hover { border-color:#ffd0a8; background:#fffaf5; color:var(--ink); }
    .lv-wrap .days-chip {
        display:inline-flex; min-width:34px; justify-content:center; padding:.2rem .5rem;
        border-radius:8px; background:var(--soft); color:var(--ink); font-weight:750; font-size:.82rem;
    }
</style>
@endpush

@section('content')
<div class="lv-wrap">
    <div class="lv-hero">
        <div>
            <h2>My leaves</h2>
            <p>Apply for leave and track approval status in one place.</p>
        </div>
        <div class="chip"><i class="fa-regular fa-calendar me-1"></i> {{ now()->format('d M Y') }}</div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-6 col-md-3">
            <div class="lv-metric is-blue">
                <div class="top">
                    <div>
                        <p class="k">Total</p>
                        <p class="v">{{ $stats['total'] }}</p>
                    </div>
                    <span class="ico"><i class="fa-solid fa-layer-group"></i></span>
                </div>
                <p class="hint">All applications</p>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="lv-metric is-amber">
                <div class="top">
                    <div>
                        <p class="k">Pending</p>
                        <p class="v">{{ $stats['pending'] }}</p>
                    </div>
                    <span class="ico"><i class="fa-solid fa-hourglass-half"></i></span>
                </div>
                <p class="hint">Awaiting decision</p>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="lv-metric is-green">
                <div class="top">
                    <div>
                        <p class="k">Approved</p>
                        <p class="v">{{ $stats['approved'] }}</p>
                    </div>
                    <span class="ico"><i class="fa-solid fa-circle-check"></i></span>
                </div>
                <p class="hint">Accepted leaves</p>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="lv-metric is-red">
                <div class="top">
                    <div>
                        <p class="k">Rejected</p>
                        <p class="v">{{ $stats['declined'] }}</p>
                    </div>
                    <span class="ico"><i class="fa-solid fa-circle-xmark"></i></span>
                </div>
                <p class="hint">Declined requests</p>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-4">
            <div class="lv-panel" id="applyPanel">
                <div class="lv-panel-head">
                    <div>
                        <h5><i class="fa-solid fa-plus me-1" style="color:var(--accent)"></i> Apply leave</h5>
                        <span class="sub">Submit a new request</span>
                    </div>
                </div>
                <div class="lv-panel-body">
                    <form method="POST" action="{{ route('leaves.store') }}" id="leaveForm">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Leave type</label>
                            <select name="leave_type_id" class="form-select" required>
                                @foreach($leaveTypes as $type)
                                    @php $left = $balances[$type->id] ?? (int) $type->number_of_leave; @endphp
                                    <option value="{{ $type->id }}" @selected(old('leave_type_id') == $type->id)>
                                        {{ $type->name }} ({{ $left }} left / {{ (int) $type->number_of_leave }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label">Start date</label>
                                <input type="date" name="start_date" id="startDate" class="form-control" value="{{ old('start_date') }}" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label">End date</label>
                                <input type="date" name="end_date" id="endDate" class="form-control" value="{{ old('end_date') }}" required>
                            </div>
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label">No. of days</label>
                                <input type="number" name="no_of_days" id="noOfDays" min="1" value="{{ old('no_of_days', 1) }}" class="form-control" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label">Day type</label>
                                <select name="day_type" class="form-select" required>
                                    <option value="1" @selected(old('day_type', '1') == '1')>Full day</option>
                                    <option value="2" @selected(old('day_type') == '2')>Half day</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Reason</label>
                            <textarea name="leave_reason" class="form-control" rows="3" placeholder="Why do you need this leave?" required>{{ old('leave_reason') }}</textarea>
                        </div>
                        <button type="submit" class="btn btn-submit">
                            <i class="fa-solid fa-paper-plane me-1"></i> Submit application
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="lv-panel">
                <div class="lv-panel-head">
                    <div>
                        <h5><i class="fa-solid fa-list me-1" style="color:var(--accent)"></i> My applications</h5>
                        <span class="sub">{{ $leaves->total() }} record(s)</span>
                    </div>
                    <a href="{{ route('holidays.index') }}" class="btn btn-ghost">View holidays</a>
                </div>
                <div class="lv-panel-body p-0">
                    <div class="table-responsive">
                        <table class="table mb-0">
                            <thead>
                                <tr>
                                    <th class="ps-3">Leave type</th>
                                    <th>From</th>
                                    <th>To</th>
                                    <th>Days</th>
                                    <th>Reason</th>
                                    <th class="pe-3 text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                            @forelse($leaves as $leave)
                                @php
                                    $label = strtolower((string) $leave->statusLabel());
                                    $pill = 'pill-pending';
                                    if ($label === 'approved') {
                                        $pill = 'pill-ok';
                                    } elseif (in_array($label, ['declined', 'rejected'], true)) {
                                        $pill = 'pill-bad';
                                    } elseif ($label === 'new') {
                                        $pill = 'pill-info';
                                    }
                                @endphp
                                <tr>
                                    <td class="ps-3"><span class="type-name">{{ $leave->leaveType?->name ?? '—' }}</span></td>
                                    <td style="white-space:nowrap;">{{ optional($leave->start_date)->format('d M Y') }}</td>
                                    <td style="white-space:nowrap;">{{ optional($leave->end_date)->format('d M Y') }}</td>
                                    <td><span class="days-chip">{{ $leave->no_of_days }}</span></td>
                                    <td><div class="reason" title="{{ $leave->leave_reason }}">{{ \Illuminate\Support\Str::limit($leave->leave_reason, 48) }}</div></td>
                                    <td class="pe-3 text-center">
                                        <span class="pill {{ $pill }}">{{ $leave->statusLabel() }}</span>
                                        @if($leave->actionByLabel())
                                            <div class="small text-muted mt-1">{{ $leave->actionByLabel() }}</div>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="empty">No leave applications yet. Use the form to apply.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if($leaves->hasPages())
                        <div class="p-3 border-top">{{ $leaves->links() }}</div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const start = document.getElementById('startDate');
    const end = document.getElementById('endDate');
    const days = document.getElementById('noOfDays');
    const focusBtn = document.getElementById('focusApplyBtn');

    function syncDays() {
        if (!start?.value || !end?.value || !days) return;
        const a = new Date(start.value + 'T00:00:00');
        const b = new Date(end.value + 'T00:00:00');
        if (Number.isNaN(a.getTime()) || Number.isNaN(b.getTime()) || b < a) return;
        const diff = Math.floor((b - a) / 86400000) + 1;
        days.value = String(Math.max(1, diff));
    }

    start?.addEventListener('change', syncDays);
    end?.addEventListener('change', syncDays);

    focusBtn?.addEventListener('click', function () {
        document.getElementById('applyPanel')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
        document.querySelector('#leaveForm select[name="leave_type_id"]')?.focus();
    });
})();
</script>
@endpush
