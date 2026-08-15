@extends('layouts.app')

@section('title', 'Notice Period')
@section('heading', 'Notice Period')

@section('page_actions')
<a href="{{ route('notice-period-steps.index') }}" class="btn btn-outline-secondary me-2">Manage Steps</a>
<a href="{{ route('notice-period.history') }}" class="btn btn-outline-secondary">History</a>
@endsection

@push('styles')
<style>
    .np-wrap { --ink:#0f2744; --muted:#6b7c93; --line:#e8eef5; --soft:#f4f7fb; --accent:#ff9b44; }
    .np-wrap .np-panel { background:#fff; border:1px solid var(--line); border-radius:18px; overflow:hidden; }
    .np-wrap .np-panel-head {
        display:flex; align-items:center; justify-content:space-between; gap:1rem; flex-wrap:wrap;
        padding:1rem 1.15rem; border-bottom:1px solid var(--line); background:linear-gradient(180deg,#fff,#fafbfd);
    }
    .np-wrap .np-panel-head h5 { margin:0; font-weight:750; color:var(--ink); font-size:1.05rem; }
    .np-wrap .np-panel-body { padding:1.15rem; }
    .np-wrap .np-metric {
        border:1px solid var(--line); border-radius:14px; background:#fff; padding:1rem 1.1rem; height:100%; position:relative; overflow:hidden;
    }
    .np-wrap .np-metric::before { content:""; position:absolute; left:0; top:0; bottom:0; width:4px; background:var(--accent); }
    .np-wrap .np-metric .k { font-size:.7rem; text-transform:uppercase; letter-spacing:.04em; color:var(--muted); font-weight:700; }
    .np-wrap .np-metric .v { font-size:1.55rem; font-weight:800; color:var(--ink); margin:0; line-height:1.1; }
    .np-wrap .np-chip {
        display:inline-flex; padding:.28rem .65rem; border-radius:999px; font-size:.75rem; font-weight:650;
        background:#fff7ed; color:#c2410c; border:1px solid #ffd7b0;
    }
    .np-wrap .np-chip.ok { background:#ecfdf5; color:#047857; border-color:#a7f3d0; }
    .np-wrap .np-chip.warn { background:#fffbeb; color:#b45309; border-color:#fde68a; }
    .np-wrap .form-label { font-size:.8rem; font-weight:600; color:var(--muted); }
</style>
@endpush

@section('content')
<div class="np-wrap">
    <div class="row g-3 mb-3">
        <div class="col-md-4">
            <div class="np-metric">
                <div class="k">Active resignations</div>
                <p class="v">{{ $employees->count() }}</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="np-metric">
                <div class="k">Pending</div>
                <p class="v">{{ $employees->where('status', 'Pending')->count() }}</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="np-metric">
                <div class="k">Approved</div>
                <p class="v">{{ $employees->where('status', 'Approved')->count() }}</p>
            </div>
        </div>
    </div>

    <div class="np-panel mb-3">
        <div class="np-panel-head">
            <h5><i class="fa-solid fa-user-check me-1" style="color:var(--accent)"></i> Select employee</h5>
        </div>
        <div class="np-panel-body">
            <form method="GET" action="{{ url('/notice-period') }}" id="npSelectForm" class="row g-2 align-items-end" onsubmit="return false;">
                <div class="col-md-8">
                    <label class="form-label">Employee with active resignation</label>
                    <select id="npEmployee" class="form-select js-employee-select"
                            data-placeholder="Search employee…"
                            data-ajax="0">
                        <option value="">Select employee</option>
                        @foreach($employees as $row)
                            <option
                                value="{{ $row->employee_id }}"
                                data-name="{{ $row->employee?->full_name ?? ('Employee #'.$row->employee_id) }}"
                                data-department="{{ $row->employee?->department?->name }}"
                                data-designation="{{ $row->employee?->designation?->name }}"
                                data-department-id="{{ $row->employee?->department_id }}"
                                data-designation-id="{{ $row->employee?->designation_id }}"
                            >
                                {{ $row->employee?->full_name ?? ('Employee #'.$row->employee_id) }}
                                — {{ $row->status }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4 d-flex gap-2">
                    <button type="button" id="npViewBtn" class="btn add-btn flex-fill">View Notice Period</button>
                </div>
            </form>
            @if($employees->isEmpty())
                <p class="text-muted mb-0 mt-3">No active resignations found.</p>
            @endif
        </div>
    </div>

    <div class="np-panel">
        <div class="np-panel-head">
            <h5>Active list</h5>
        </div>
        <div class="np-panel-body">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Status</th>
                        <th>Notice days</th>
                        <th class="text-end">Action</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($employees as $row)
                        <tr>
                            <td class="fw-semibold" style="color:var(--ink)">{{ $row->employee?->full_name ?? ('#'.$row->employee_id) }}</td>
                            <td>
                                @php
                                    $chip = match($row->status) {
                                        'Approved' => 'ok',
                                        'Pending' => 'warn',
                                        default => '',
                                    };
                                @endphp
                                <span class="np-chip {{ $chip }}">{{ $row->status }}</span>
                            </td>
                            <td>{{ $row->notice_period_days }} days</td>
                            <td class="text-end">
                                <a href="{{ route('notice-period.show', $row->employee_id) }}" class="btn btn-sm btn-outline-primary">Open</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-muted py-4">No records.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.getElementById('npViewBtn')?.addEventListener('click', function () {
    const id = document.getElementById('npEmployee')?.value;
    if (!id) { alert('Please select an employee'); return; }
    window.location.href = @json(url('/notice-period')) + '/' + id;
});
</script>
@endpush
