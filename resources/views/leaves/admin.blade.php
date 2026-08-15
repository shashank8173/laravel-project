@extends('layouts.app')

@section('title', 'Leaves (Admin)')
@section('heading', 'Leaves')

@section('page_actions')
<a href="{{ route('leave-settings.index') }}" class="btn btn-outline-secondary me-1">
    <i class="fa-solid fa-sliders me-1"></i> Settings
</a>
<a href="{{ route('holidays.index') }}" class="btn btn-outline-secondary">
    <i class="fa-solid fa-snowflake me-1"></i> Holidays
</a>
@endsection

@push('styles')
<style>
    .lv-wrap { --ink:#0f2744; --muted:#6b7c93; --line:#e8eef5; --soft:#f4f7fb; --accent:#ff9b44; }
    .lv-wrap .lv-panel { background:#fff; border:1px solid var(--line); border-radius:18px; overflow:hidden; }
    .lv-wrap .lv-panel-head {
        display:flex; justify-content:space-between; align-items:center; gap:1rem; flex-wrap:wrap;
        padding:1rem 1.15rem; border-bottom:1px solid var(--line);
        background:linear-gradient(180deg,#fff,#fafbfd);
    }
    .lv-wrap .lv-panel-head h5 { margin:0; font-weight:750; color:var(--ink); }
    .lv-wrap .lv-panel-body { padding:1.15rem; }
    .lv-wrap .lv-metric {
        border:1px solid var(--line); border-radius:14px; background:#fff; padding:1rem 1.05rem; height:100%;
        position:relative; overflow:hidden; text-decoration:none; display:block; color:inherit;
        transition: border-color .15s ease, transform .15s ease;
    }
    .lv-wrap .lv-metric:hover { border-color:#ffd0a8; transform:translateY(-1px); }
    .lv-wrap .lv-metric.is-active { border-color:var(--accent); box-shadow:0 0 0 2px rgba(255,155,68,.15); }
    .lv-wrap .lv-metric::before { content:""; position:absolute; left:0; top:0; bottom:0; width:4px; background:var(--accent); }
    .lv-wrap .lv-metric.is-green::before { background:#16a34a; }
    .lv-wrap .lv-metric.is-amber::before { background:#f59e0b; }
    .lv-wrap .lv-metric.is-slate::before { background:#64748b; }
    .lv-wrap .lv-metric.is-blue::before { background:#2563eb; }
    .lv-wrap .lv-metric.is-red::before { background:#ef4444; }
    .lv-wrap .lv-metric .k { font-size:.68rem; text-transform:uppercase; letter-spacing:.04em; color:var(--muted); font-weight:700; }
    .lv-wrap .lv-metric .v { font-size:1.35rem; font-weight:800; color:var(--ink); margin:.15rem 0 0; line-height:1.1; }
    .lv-wrap .lv-metric .hint { font-size:.72rem; color:var(--muted); margin-top:.2rem; }
    .lv-wrap .form-label { font-size:.8rem; font-weight:600; color:var(--muted); }
    .lv-wrap .table > :not(caption) > * > * { vertical-align:middle; }
    .lv-wrap .name { font-weight:750; color:var(--ink); margin:0; }
    .lv-wrap .name a { color:inherit; text-decoration:none; }
    .lv-wrap .name a:hover { color:#9a3412; }
    .lv-wrap .sub { font-size:.78rem; color:var(--muted); }
    .lv-wrap .person { display:flex; align-items:center; gap:.7rem; }
    .lv-wrap .avatar {
        width:38px; height:38px; border-radius:50%; object-fit:cover; flex-shrink:0;
        background:#fff7ed; border:1px solid #ffe0c2;
    }
    .lv-wrap .avatar-fallback {
        width:38px; height:38px; border-radius:50%; display:none; align-items:center; justify-content:center;
        background:#fff7ed; color:#9a3412; font-size:.75rem; font-weight:800; flex-shrink:0;
    }
    .lv-wrap .lv-pill {
        display:inline-flex; padding:.28rem .65rem; border-radius:999px; font-size:.72rem; font-weight:700;
    }
    .lv-wrap .st-new { background:#fef9c3; color:#a16207; }
    .lv-wrap .st-pending { background:#ffedd5; color:#c2410c; }
    .lv-wrap .st-approved { background:#dcfce7; color:#15803d; }
    .lv-wrap .st-rejected { background:#fee2e2; color:#b91c1c; }
    .lv-wrap .type-chip {
        display:inline-flex; padding:.22rem .55rem; border-radius:8px; background:var(--soft);
        color:var(--ink); font-size:.78rem; font-weight:700;
    }
    .lv-wrap .quick-pills { display:flex; flex-wrap:wrap; gap:.45rem; }
    .lv-wrap .quick-pill {
        display:inline-flex; align-items:center; gap:.35rem; padding:.35rem .75rem; border-radius:999px;
        border:1px solid var(--line); background:#fff; color:var(--ink); text-decoration:none;
        font-size:.78rem; font-weight:700;
    }
    .lv-wrap .quick-pill:hover { border-color:#ffd0a8; background:#fff7ed; color:#9a3412; }
    .lv-wrap .quick-pill.is-on { background:var(--accent); border-color:var(--accent); color:#fff; }
    .lv-wrap .reason-link { color:inherit; text-decoration:none; }
    .lv-wrap .reason-link:hover { color:#9a3412; }
</style>
@endpush

@section('content')
@php
    $statusPill = function ($leave) {
        $label = strtolower($leave->statusLabel());
        return match (true) {
            str_contains($label, 'approv') => 'st-approved',
            str_contains($label, 'reject') || str_contains($label, 'declin') => 'st-rejected',
            $label === 'new' => 'st-new',
            default => 'st-pending',
        };
    };
    $initials = function (?string $name): string {
        $name = trim((string) $name);
        if ($name === '') return '?';
        $parts = preg_split('/\s+/', $name) ?: [];
        return strtoupper(substr($parts[0] ?? '', 0, 1).substr($parts[1] ?? '', 0, 1));
    };
    $filterBase = array_filter([
        'employee_id' => $employeeId ?: null,
        'q' => $q !== '' ? $q : null,
        'status' => ($status !== null && $status !== '') ? $status : null,
    ], fn ($v) => $v !== null && $v !== '');
@endphp
<div class="lv-wrap">
    <div class="row g-3 mb-3">
        <div class="col-6 col-lg-2">
            <a href="{{ route('leaves.admin', array_merge($filterBase, ['period' => 'today'])) }}"
               class="lv-metric is-green {{ $period === 'today' ? 'is-active' : '' }}">
                <div class="k">On leave today</div>
                <p class="v">{{ $counts['today'] }}</p>
                <div class="hint">{{ \Carbon\Carbon::parse($today)->format('d M Y') }}</div>
            </a>
        </div>
        <div class="col-6 col-lg-2">
            <a href="{{ route('leaves.admin', array_merge($filterBase, ['period' => 'tomorrow'])) }}"
               class="lv-metric is-amber {{ $period === 'tomorrow' ? 'is-active' : '' }}">
                <div class="k">Tomorrow</div>
                <p class="v">{{ $counts['tomorrow'] }}</p>
                <div class="hint">{{ \Carbon\Carbon::parse($tomorrow)->format('d M Y') }}</div>
            </a>
        </div>
        <div class="col-6 col-lg-2">
            <a href="{{ route('leaves.admin', array_merge($filterBase, ['period' => 'yesterday'])) }}"
               class="lv-metric is-slate {{ $period === 'yesterday' ? 'is-active' : '' }}">
                <div class="k">Yesterday</div>
                <p class="v">{{ $counts['yesterday'] }}</p>
                <div class="hint">{{ \Carbon\Carbon::parse($yesterday)->format('d M Y') }}</div>
            </a>
        </div>
        <div class="col-6 col-lg-2">
            <a href="{{ route('leaves.admin', array_filter(['status' => 1, 'q' => $q ?: null, 'employee_id' => $employeeId ?: null])) }}"
               class="lv-metric {{ (string)$status === '1' && ! $period ? 'is-active' : '' }}">
                <div class="k">Pending / new</div>
                <p class="v">{{ $counts['pending'] }}</p>
            </a>
        </div>
        <div class="col-6 col-lg-2">
            <div class="lv-metric is-blue">
                <div class="k">Approved</div>
                <p class="v">{{ $counts['approved'] }}</p>
            </div>
        </div>
        <div class="col-6 col-lg-2">
            <div class="lv-metric is-red">
                <div class="k">Rejected</div>
                <p class="v">{{ $counts['rejected'] }}</p>
            </div>
        </div>
    </div>

    <div class="lv-panel mb-3">
        <div class="lv-panel-head">
            <h5><i class="fa-solid fa-filter me-1" style="color:var(--accent)"></i> Filters</h5>
            @if($period || $dateFrom || $dateTo || ($status !== null && $status !== '') || $employeeId || $q)
                <a href="{{ route('leaves.admin') }}" class="btn btn-sm btn-outline-secondary">Clear</a>
            @endif
        </div>
        <div class="lv-panel-body">
            <form method="GET" action="{{ route('leaves.admin') }}" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Search</label>
                    <input type="search" name="q" value="{{ $q }}" class="form-control" placeholder="Name, reason, type…">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Employee</label>
                    <x-employee-select
                        name="employee_id"
                        :employees="$employees"
                        :selected="$employeeId"
                        placeholder="All employees (search…)"
                    />
                </div>
                <div class="col-md-2">
                    <label class="form-label">Day</label>
                    <select name="period" class="form-select" id="leavePeriodFilter">
                        <option value="">All days</option>
                        <option value="today" @selected($period === 'today')>Today</option>
                        <option value="tomorrow" @selected($period === 'tomorrow')>Tomorrow</option>
                        <option value="yesterday" @selected($period === 'yesterday')>Yesterday</option>
                        <option value="range" @selected($period === 'range' || ($dateFrom && $dateTo && ! in_array($period, ['today','tomorrow','yesterday'], true)))>Date range</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select" id="leaveStatusFilter">
                        <option value="">All</option>
                        @foreach([0 => 'New', 1 => 'Pending', 2 => 'Approved', 3 => 'Rejected'] as $value => $label)
                            <option value="{{ $value }}" @selected((string)$status === (string)$value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-1 leave-range-fields">
                    <label class="form-label">From</label>
                    <input type="date" name="date_from" class="form-control" value="{{ $dateFrom }}">
                </div>
                <div class="col-md-1 leave-range-fields">
                    <label class="form-label">To</label>
                    <input type="date" name="date_to" class="form-control" value="{{ $dateTo }}">
                </div>
                <div class="col-md-1">
                    <button class="btn add-btn w-100">Apply</button>
                </div>
            </form>

            <div class="quick-pills mt-3">
                <a href="{{ route('leaves.admin', array_merge($filterBase, ['period' => 'today'])) }}"
                   class="quick-pill {{ $period === 'today' ? 'is-on' : '' }}">Today</a>
                <a href="{{ route('leaves.admin', array_merge($filterBase, ['period' => 'tomorrow'])) }}"
                   class="quick-pill {{ $period === 'tomorrow' ? 'is-on' : '' }}">Tomorrow</a>
                <a href="{{ route('leaves.admin', array_merge($filterBase, ['period' => 'yesterday'])) }}"
                   class="quick-pill {{ $period === 'yesterday' ? 'is-on' : '' }}">Yesterday</a>
                <a href="{{ route('leaves.admin', array_filter(['status' => 0, 'q' => $q ?: null, 'employee_id' => $employeeId ?: null])) }}"
                   class="quick-pill {{ (string)$status === '0' && ! $period ? 'is-on' : '' }}">New</a>
                <a href="{{ route('leaves.admin', array_filter(['status' => 1, 'q' => $q ?: null, 'employee_id' => $employeeId ?: null])) }}"
                   class="quick-pill {{ (string)$status === '1' && ! $period ? 'is-on' : '' }}">Pending</a>
                <a href="{{ route('leaves.admin', array_filter(['status' => 2, 'q' => $q ?: null, 'employee_id' => $employeeId ?: null])) }}"
                   class="quick-pill {{ (string)$status === '2' && ! $period ? 'is-on' : '' }}">Approved</a>
                <a href="{{ route('leaves.admin') }}" class="quick-pill">All</a>
            </div>
        </div>
    </div>

    <div class="lv-panel">
        <div class="lv-panel-head">
            <h5><i class="fa-solid fa-plane-departure me-1" style="color:var(--accent)"></i> Leave requests</h5>
            <span class="small text-muted">{{ $leaves->total() }} record(s)</span>
        </div>
        <div class="table-responsive p-2">
            <table class="table align-middle mb-0">
                <thead>
                <tr>
                    <th>Employee</th>
                    <th>Type</th>
                    <th>Dates</th>
                    <th>Reason</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
                </thead>
                <tbody>
                @forelse($leaves as $leave)
                    @php
                        $empName = $leave->employee?->full_name ?: '—';
                        $fullReason = (string) ($leave->leave_reason ?? '');
                        $shortReason = \Illuminate\Support\Str::limit($fullReason, 55);
                        $isLong = \Illuminate\Support\Str::length($fullReason) > 55;
                        $label = $leave->statusLabel();
                    @endphp
                    <tr>
                        <td>
                            <div class="person">
                                @if($leave->employee)
                                    <img src="{{ $leave->employee->profile_image_url }}" alt="" class="avatar"
                                         onerror="this.style.display='none'; this.nextElementSibling.style.display='inline-flex';">
                                    <span class="avatar-fallback">{{ $initials($empName) }}</span>
                                @else
                                    <span class="avatar-fallback" style="display:inline-flex;">?</span>
                                @endif
                                <div>
                                    <p class="name">
                                        @if($leave->employee)
                                            <a href="{{ route('employees.show', $leave->employee) }}">{{ $empName }}</a>
                                        @else
                                            {{ $empName }}
                                        @endif
                                    </p>
                                    <div class="sub">{{ optional($leave->created_at)->format('d M Y') ?: '' }}</div>
                                </div>
                            </div>
                        </td>
                        <td><span class="type-chip">{{ $leave->leaveType?->name ?? '—' }}</span></td>
                        <td>
                            <div class="name" style="font-size:.9rem;">
                                {{ optional($leave->start_date)->format('d M Y') }}
                                → {{ optional($leave->end_date)->format('d M Y') }}
                            </div>
                            <div class="sub">{{ $leave->no_of_days }} day(s)</div>
                        </td>
                        <td style="max-width:220px;">
                            @if($fullReason === '')
                                <span class="text-muted">—</span>
                            @elseif($isLong)
                                <a href="#" class="reason-link leave-reason-link"
                                   data-bs-toggle="modal" data-bs-target="#leaveReasonModal"
                                   data-employee="{{ $empName }}"
                                   data-reason="{{ $fullReason }}">
                                    {{ $shortReason }} <i class="fa-regular fa-eye text-muted"></i>
                                </a>
                            @else
                                <span class="sub" style="color:var(--ink);">{{ $fullReason }}</span>
                            @endif
                        </td>
                        <td>
                            <span class="lv-pill {{ $statusPill($leave) }}">{{ $label }}</span>
                            @if($leave->actionByLabel())
                                <div class="sub mt-1">{{ $leave->actionByLabel() }}</div>
                            @endif
                        </td>
                        <td class="text-end text-nowrap">
                            <form method="POST" action="{{ route('leaves.status', $leave) }}" class="d-inline-flex gap-1 flex-wrap justify-content-end">
                                @csrf @method('PATCH')
                                <button name="status" value="2" class="btn btn-sm btn-success">Approve</button>
                                <button name="status" value="3" class="btn btn-sm btn-outline-danger">Reject</button>
                                <button name="status" value="1" class="btn btn-sm btn-outline-secondary">Pending</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">No leave applications found.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-3 border-top">{{ $leaves->links() }}</div>
    </div>
</div>

<div class="modal fade" id="leaveReasonModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border:0;border-radius:16px;">
            <div class="modal-header border-0 pb-0">
                <div>
                    <div class="small text-muted">Leave reason</div>
                    <h5 class="modal-title fw-bold mb-0" id="leaveReasonEmployee">Employee</h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p id="leaveReasonFull" class="mb-0" style="white-space:pre-wrap;line-height:1.55;color:#334155;"></p>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn add-btn" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('leaveReasonModal');
    if (modal) {
        modal.addEventListener('show.bs.modal', function (event) {
            const trigger = event.relatedTarget;
            if (!trigger) return;
            document.getElementById('leaveReasonEmployee').textContent = trigger.getAttribute('data-employee') || 'Employee';
            document.getElementById('leaveReasonFull').textContent = trigger.getAttribute('data-reason') || '';
        });
    }

    const periodSelect = document.getElementById('leavePeriodFilter');
    const statusSelect = document.getElementById('leaveStatusFilter');
    const rangeFields = document.querySelectorAll('.leave-range-fields');

    function syncPeriodUi() {
        const period = periodSelect ? periodSelect.value : '';
        const isDay = ['today', 'tomorrow', 'yesterday'].includes(period);
        const isRange = period === 'range';

        rangeFields.forEach(function (el) {
            el.style.display = isRange ? '' : 'none';
            if (!isRange) {
                el.querySelectorAll('input').forEach(function (input) { input.value = ''; });
            }
        });

        if (statusSelect) {
            statusSelect.disabled = isDay;
            if (isDay) statusSelect.value = '';
        }
    }

    if (periodSelect) {
        periodSelect.addEventListener('change', syncPeriodUi);
        syncPeriodUi();
    }
});
</script>
@endpush
