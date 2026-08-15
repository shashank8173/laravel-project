@extends('layouts.app')

@section('title', 'Resignations Admin')
@section('heading', 'Resignations')

@section('page_actions')
<button type="button" class="btn btn-notice" data-bs-toggle="modal" data-bs-target="#adminResignationNoticeModal">
    <i class="fa-solid fa-circle-info me-1"></i> Notice
</button>
<a href="{{ route('notice-period.index') }}" class="btn add-btn">
    <i class="fa-solid fa-list-check"></i> Notice period
</a>
@endsection

@push('styles')
<style>
    .rga-wrap { --ink:#0f2744; --muted:#6b7c93; --line:#e8eef5; --soft:#f4f7fb; --accent:#ff9b44; }
    .rga-wrap .rga-panel { background:#fff; border:1px solid var(--line); border-radius:18px; overflow:hidden; }
    .rga-wrap .rga-panel-head {
        display:flex; justify-content:space-between; align-items:center; gap:1rem; flex-wrap:wrap;
        padding:1rem 1.15rem; border-bottom:1px solid var(--line);
        background:linear-gradient(180deg,#fff,#fafbfd);
    }
    .rga-wrap .rga-panel-head h5 { margin:0; font-weight:750; color:var(--ink); }
    .rga-wrap .rga-panel-body { padding:1.15rem; }
    .rga-wrap .rga-metric {
        border:1px solid var(--line); border-radius:14px; background:#fff; padding:1rem 1.05rem; height:100%;
        position:relative; overflow:hidden; text-decoration:none; display:block; color:inherit;
        transition: border-color .15s ease, transform .15s ease;
    }
    .rga-wrap .rga-metric:hover { border-color:#ffd0a8; transform:translateY(-1px); }
    .rga-wrap .rga-metric.is-active { border-color:var(--accent); box-shadow:0 0 0 2px rgba(255,155,68,.15); }
    .rga-wrap .rga-metric::before { content:""; position:absolute; left:0; top:0; bottom:0; width:4px; background:var(--accent); }
    .rga-wrap .rga-metric.is-amber::before { background:#f59e0b; }
    .rga-wrap .rga-metric.is-green::before { background:#16a34a; }
    .rga-wrap .rga-metric.is-red::before { background:#ef4444; }
    .rga-wrap .rga-metric .k { font-size:.68rem; text-transform:uppercase; letter-spacing:.04em; color:var(--muted); font-weight:700; }
    .rga-wrap .rga-metric .v { font-size:1.35rem; font-weight:800; color:var(--ink); margin:.15rem 0 0; line-height:1.1; }
    .rga-wrap .form-label { font-size:.8rem; font-weight:600; color:var(--muted); }
    .rga-wrap .table > :not(caption) > * > * { vertical-align:middle; }
    .rga-wrap .name { font-weight:750; color:var(--ink); margin:0; }
    .rga-wrap .sub { font-size:.78rem; color:var(--muted); }
    .rga-wrap .rga-pill {
        display:inline-flex; padding:.28rem .65rem; border-radius:999px; font-size:.72rem; font-weight:700;
    }
    .rga-wrap .st-pending { background:#fef3c7; color:#b45309; }
    .rga-wrap .st-approved { background:#dcfce7; color:#15803d; }
    .rga-wrap .st-declined { background:#fee2e2; color:#b91c1c; }
    .rga-wrap .avatar {
        width:34px; height:34px; border-radius:50%; display:inline-flex; align-items:center; justify-content:center;
        background:#fff7ed; color:#9a3412; font-size:.75rem; font-weight:800; flex-shrink:0;
    }
    .rga-wrap .person { display:flex; align-items:center; gap:.65rem; }
    .btn-notice {
        border:1px solid #f59e0b; color:#92400e; background:#fffbeb; font-weight:700; border-radius:999px;
        padding:.35rem .9rem; font-size:.8rem;
    }
    .btn-notice:hover { background:#fef3c7; color:#78350f; border-color:#d97706; }
</style>
@endpush

@section('content')
@php
    $statusClass = fn ($s) => match ($s) {
        'Approved' => 'st-approved',
        'Declined' => 'st-declined',
        default => 'st-pending',
    };
    $initials = function (?string $name): string {
        $name = trim((string) $name);
        if ($name === '') {
            return '?';
        }
        $parts = preg_split('/\s+/', $name) ?: [];
        $a = strtoupper(substr($parts[0] ?? '', 0, 1));
        $b = strtoupper(substr($parts[1] ?? '', 0, 1));

        return $a.($b ?: '');
    };
@endphp
<div class="rga-wrap">
    <div class="row g-3 mb-3">
        <div class="col-6 col-lg-3">
            <a href="{{ route('resignation.admin', array_filter(['q' => $q ?: null])) }}"
               class="rga-metric {{ ! $status ? 'is-active' : '' }}">
                <div class="k">All</div>
                <p class="v">{{ $stats['all'] }}</p>
            </a>
        </div>
        <div class="col-6 col-lg-3">
            <a href="{{ route('resignation.admin', array_filter(['q' => $q ?: null, 'status' => 'Pending'])) }}"
               class="rga-metric is-amber {{ $status === 'Pending' ? 'is-active' : '' }}">
                <div class="k">Pending</div>
                <p class="v">{{ $stats['pending'] }}</p>
            </a>
        </div>
        <div class="col-6 col-lg-3">
            <a href="{{ route('resignation.admin', array_filter(['q' => $q ?: null, 'status' => 'Approved'])) }}"
               class="rga-metric is-green {{ $status === 'Approved' ? 'is-active' : '' }}">
                <div class="k">Approved</div>
                <p class="v">{{ $stats['approved'] }}</p>
            </a>
        </div>
        <div class="col-6 col-lg-3">
            <a href="{{ route('resignation.admin', array_filter(['q' => $q ?: null, 'status' => 'Declined'])) }}"
               class="rga-metric is-red {{ $status === 'Declined' ? 'is-active' : '' }}">
                <div class="k">Declined</div>
                <p class="v">{{ $stats['declined'] }}</p>
            </a>
        </div>
    </div>

    <div class="rga-panel mb-3">
        <div class="rga-panel-head">
            <h5><i class="fa-solid fa-filter me-1" style="color:var(--accent)"></i> Filters</h5>
            @if($q !== '' || $status)
                <a href="{{ route('resignation.admin') }}" class="btn btn-sm btn-outline-secondary">Clear</a>
            @endif
        </div>
        <div class="rga-panel-body">
            <form method="GET" action="{{ route('resignation.admin') }}" class="row g-2 align-items-end">
                <div class="col-md-5">
                    <label class="form-label">Search</label>
                    <input type="search" name="q" value="{{ $q }}" class="form-control" placeholder="Employee name or reason">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All</option>
                        @foreach(['Pending', 'Approved', 'Declined'] as $s)
                            <option value="{{ $s }}" @selected($status === $s)>{{ $s }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <button class="btn add-btn me-1">Apply</button>
                    <a href="{{ route('resignation.admin') }}" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="rga-panel">
        <div class="rga-panel-head">
            <h5><i class="fa-solid fa-user-minus me-1" style="color:var(--accent)"></i> Resignation requests</h5>
            <span class="small text-muted">{{ $resignations->total() }} record(s)</span>
        </div>
        <div class="table-responsive p-2">
            <table class="table align-middle mb-0">
                <thead>
                <tr>
                    <th>Employee</th>
                    <th>Last date</th>
                    <th>Notice</th>
                    <th>Reason</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
                </thead>
                <tbody>
                @forelse($resignations as $resignation)
                    @php $empName = $resignation->employee?->full_name ?: '—'; @endphp
                    <tr>
                        <td>
                            <div class="person">
                                <span class="avatar">{{ $initials($empName) }}</span>
                                <div>
                                    <p class="name">{{ $empName }}</p>
                                    <div class="sub">
                                        Submitted {{ optional($resignation->submitted_at)->format('d M Y') ?: '—' }}
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td>{{ optional($resignation->intended_last_date)->format('d M Y') ?: '—' }}</td>
                        <td>{{ $resignation->notice_period_days }} days</td>
                        <td>
                            <div class="sub" title="{{ $resignation->resignation_reason }}">
                                {{ \Illuminate\Support\Str::limit($resignation->resignation_reason, 55) }}
                            </div>
                        </td>
                        <td>
                            <span class="rga-pill {{ $statusClass($resignation->status) }}">{{ $resignation->status }}</span>
                            @if($resignation->approver)
                                <div class="sub mt-1">By {{ $resignation->approver->full_name }}</div>
                            @endif
                        </td>
                        <td class="text-end text-nowrap">
                            <button type="button"
                                    class="btn btn-sm btn-outline-secondary review-resignation-btn"
                                    data-bs-toggle="modal"
                                    data-bs-target="#reviewResignationModal"
                                    data-url="{{ route('resignation.status', $resignation) }}"
                                    data-name="{{ $empName }}"
                                    data-status="{{ $resignation->status }}"
                                    data-reason="{{ $resignation->resignation_reason }}"
                                    data-last-date="{{ optional($resignation->intended_last_date)->format('d M Y') }}"
                                    data-notice="{{ $resignation->notice_period_days }}"
                                    data-decline="{{ $resignation->decline_reason }}"
                                    data-submitted="{{ optional($resignation->submitted_at)->format('d M Y') }}">
                                Review
                            </button>
                            <a href="{{ route('notice-period.show', $resignation->employee_id) }}" class="btn btn-sm btn-outline-primary">
                                Steps
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">No resignations found.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-3 border-top">{{ $resignations->links() }}</div>
    </div>
</div>

<div class="modal fade" id="reviewResignationModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content" style="border:0;border-radius:16px;">
            <form method="POST" id="reviewResignationForm" action="#">
                @csrf
                @method('PATCH')
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold">Review resignation</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body rga-wrap">
                    <div class="mb-3 p-3" style="background:var(--soft);border-radius:12px;">
                        <p class="name" id="reviewEmpName">—</p>
                        <div class="row g-2 mt-1">
                            <div class="col-md-4">
                                <div class="sub">Submitted</div>
                                <div class="fw-semibold" id="reviewSubmitted">—</div>
                            </div>
                            <div class="col-md-4">
                                <div class="sub">Last date</div>
                                <div class="fw-semibold" id="reviewLastDate">—</div>
                            </div>
                            <div class="col-md-4">
                                <div class="sub">Current status</div>
                                <div><span class="rga-pill" id="reviewStatusPill">—</span></div>
                            </div>
                        </div>
                        <div class="mt-3">
                            <div class="sub">Reason</div>
                            <div id="reviewReason" style="color:var(--ink);font-size:.92rem;"></div>
                        </div>
                        <div class="mt-2" id="reviewDeclineWrap" style="display:none;">
                            <div class="sub">Previous decline reason</div>
                            <div id="reviewDeclinePrev" style="color:#b91c1c;font-size:.9rem;"></div>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Notice period (days)</label>
                            <select name="notice_period_days" id="reviewNoticeDays" class="form-select">
                                <option value="15">15</option>
                                <option value="30">30</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Decline reason <span class="text-muted">(required to decline)</span></label>
                            <input type="text" name="decline_reason" id="reviewDeclineReason" class="form-control" placeholder="Why declining?">
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0 d-flex justify-content-between flex-wrap gap-2">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <div class="d-flex gap-2">
                        <button type="submit" name="status" value="Declined" class="btn btn-outline-danger"
                                onclick="return confirm('Decline this resignation?')">Decline</button>
                        <button type="submit" name="status" value="Approved" class="btn add-btn"
                                onclick="return confirm('Approve this resignation and sync notice period?')">Approve</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="adminResignationNoticeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border:0;border-radius:16px;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold">Admin notice</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="color:#334155;font-size:.92rem;">
                <ul class="mb-0 ps-3">
                    <li class="mb-2"><strong>Approve</strong> starts/syncs notice-period exit steps for the employee.</li>
                    <li class="mb-2"><strong>Decline</strong> requires a reason and clears notice-period steps.</li>
                    <li class="mb-2">You can adjust notice period (15/30 days) before approving.</li>
                    <li>Use <strong>Steps</strong> to open that employee’s notice-period checklist.</li>
                </ul>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn add-btn" data-bs-dismiss="modal">Got it</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('reviewResignationModal');
    const form = document.getElementById('reviewResignationForm');
    const statusPill = document.getElementById('reviewStatusPill');

    modal.addEventListener('show.bs.modal', function (e) {
        const btn = e.relatedTarget;
        if (!btn || !btn.classList.contains('review-resignation-btn')) return;

        form.action = btn.dataset.url;
        document.getElementById('reviewEmpName').textContent = btn.dataset.name || '—';
        document.getElementById('reviewSubmitted').textContent = btn.dataset.submitted || '—';
        document.getElementById('reviewLastDate').textContent = btn.dataset.lastDate || '—';
        document.getElementById('reviewReason').textContent = btn.dataset.reason || '—';
        document.getElementById('reviewNoticeDays').value = btn.dataset.notice || '30';
        document.getElementById('reviewDeclineReason').value = '';

        const status = btn.dataset.status || 'Pending';
        statusPill.textContent = status;
        statusPill.className = 'rga-pill ' + (
            status === 'Approved' ? 'st-approved' :
            status === 'Declined' ? 'st-declined' : 'st-pending'
        );

        const declinePrev = (btn.dataset.decline || '').trim();
        const wrap = document.getElementById('reviewDeclineWrap');
        if (declinePrev) {
            wrap.style.display = 'block';
            document.getElementById('reviewDeclinePrev').textContent = declinePrev;
        } else {
            wrap.style.display = 'none';
            document.getElementById('reviewDeclinePrev').textContent = '';
        }
    });
});
</script>
@endpush
