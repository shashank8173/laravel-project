@extends('layouts.app')

@section('title', 'Notice Period - '.$emp->full_name)
@section('heading', 'Notice Period')

@section('page_actions')
<a href="{{ route('notice-period.index') }}" class="btn btn-outline-secondary me-2">Back</a>
<a href="{{ route('notice-period-steps.index') }}" class="btn btn-outline-secondary me-2">Manage Steps</a>
<a href="{{ route('notice-period.history') }}" class="btn btn-outline-secondary">History</a>
@endsection

@push('styles')
<style>
    .np-wrap { --ink:#0f2744; --muted:#6b7c93; --line:#e8eef5; --soft:#f4f7fb; --accent:#ff9b44; }
    .np-wrap .np-panel { background:#fff; border:1px solid var(--line); border-radius:18px; overflow:hidden; margin-bottom:1rem; }
    .np-wrap .np-panel-head {
        display:flex; align-items:center; justify-content:space-between; gap:1rem; flex-wrap:wrap;
        padding:1rem 1.15rem; border-bottom:1px solid var(--line); background:linear-gradient(180deg,#fff,#fafbfd);
    }
    .np-wrap .np-panel-head h5 { margin:0; font-weight:750; color:var(--ink); font-size:1.05rem; }
    .np-wrap .np-panel-body { padding:1.15rem; }
    .np-wrap .np-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:.75rem 1.25rem; }
    @media (max-width:767px){ .np-wrap .np-grid { grid-template-columns:1fr; } }
    .np-wrap .k { font-size:.72rem; text-transform:uppercase; letter-spacing:.04em; color:var(--muted); font-weight:700; }
    .np-wrap .v { margin:0; color:var(--ink); font-weight:650; }
    .np-wrap .np-chip {
        display:inline-flex; padding:.28rem .65rem; border-radius:999px; font-size:.75rem; font-weight:650;
        background:#fff7ed; color:#c2410c; border:1px solid #ffd7b0;
    }
    .np-wrap .np-chip.ok { background:#ecfdf5; color:#047857; border-color:#a7f3d0; }
    .np-wrap .np-chip.warn { background:#fffbeb; color:#b45309; border-color:#fde68a; }
    .np-wrap .progress-wrap { background:#eef2f7; border-radius:999px; height:10px; overflow:hidden; }
    .np-wrap .progress-bar-fill { height:100%; background:linear-gradient(90deg,#ff9b44,#ffb874); border-radius:999px; }
    .np-wrap .step-card {
        border:1px solid var(--line); border-radius:14px; margin-bottom:.75rem; overflow:hidden; background:#fff;
    }
    .np-wrap .step-card.done { border-color:#a7f3d0; }
    .np-wrap .step-card .step-head {
        display:flex; justify-content:space-between; gap:1rem; align-items:center; flex-wrap:wrap;
        padding:.85rem 1rem; background:#fafbfd; border-bottom:1px solid var(--line); cursor:pointer;
    }
    .np-wrap .step-card .step-body { padding:1rem; display:none; }
    .np-wrap .step-card.open .step-body { display:block; }
    .np-wrap .form-label { font-size:.8rem; font-weight:600; color:var(--muted); }
    .np-wrap .file-row { display:flex; justify-content:space-between; gap:.5rem; align-items:center; padding:.4rem 0; border-bottom:1px dashed var(--line); }
</style>
@endpush

@section('content')
<div class="np-wrap">
    <div class="np-panel">
        <div class="np-panel-head">
            <h5>{{ $emp->full_name }}</h5>
            <span class="small text-muted">{{ $emp->email }}</span>
        </div>
        <div class="np-panel-body">
            @if(! $resignation)
                <div class="alert alert-warning mb-0">No active resignation found for this employee.</div>
            @else
                <div class="np-grid mb-3">
                    <div><div class="k">Reason</div><p class="v">{{ $resignation->resignation_reason }}</p></div>
                    <div><div class="k">Intended last date</div><p class="v">{{ optional($resignation->intended_last_date)->format('d M Y') }}</p></div>
                    <div><div class="k">Submitted</div><p class="v">{{ optional($resignation->submitted_at)->format('d M Y H:i') }}</p></div>
                    <div><div class="k">Approved by</div><p class="v">{{ $resignation->approver?->full_name ?: '—' }}</p></div>
                </div>

                <form method="POST" action="{{ route('notice-period.resignation', $emp->id) }}" id="resignationForm" class="row g-2 align-items-end">
                    @csrf
                    <input type="hidden" name="resignation_id" value="{{ $resignation->id }}">
                    <div class="col-md-3">
                        <label class="form-label">Status</label>
                        <select name="status" id="resignation_status" class="form-select" required>
                            @foreach(['Pending','Approved','Declined'] as $st)
                                <option value="{{ $st }}" @selected($resignation->status === $st)>{{ $st }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Notice period days</label>
                        <select name="notice_period_days" class="form-select" required onchange="this.form.submit()">
                            <option value="15" @selected((int)$resignation->notice_period_days === 15)>15</option>
                            <option value="30" @selected((int)$resignation->notice_period_days === 30)>30</option>
                        </select>
                    </div>
                    <div class="col-md-3" id="decline_reason_wrap" style="{{ $resignation->status === 'Declined' ? '' : 'display:none' }}">
                        <label class="form-label">Decline reason</label>
                        <input type="text" name="decline_reason" id="decline_reason" class="form-control" value="{{ $resignation->decline_reason }}">
                    </div>
                    <div class="col-md-3">
                        <button class="btn add-btn w-100">Update Resignation</button>
                    </div>
                </form>
            @endif
        </div>
    </div>

    @if($resignation)
        <div class="np-panel">
            <div class="np-panel-head">
                <h5>Progress</h5>
                <span class="small text-muted">{{ $completed }}/{{ $total }} completed ({{ $percent }}%)</span>
            </div>
            <div class="np-panel-body">
                <div class="progress-wrap mb-3"><div class="progress-bar-fill" style="width:{{ $percent }}%"></div></div>
                <div class="d-flex gap-2 mb-3">
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="showAllSteps">All steps</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="showIncomplete">Incomplete only</button>
                </div>

                @forelse($steps as $index => $stepRow)
                    @php
                        $stepFiles = $files->get($stepRow->step_id, collect());
                        $isDone = (int)$stepRow->status === 1;
                    @endphp
                    <div class="step-card {{ $isDone ? 'done' : '' }} {{ $index === 0 ? 'open' : '' }}" data-incomplete="{{ $isDone ? '0' : '1' }}">
                        <div class="step-head step-toggle">
                            <div>
                                <strong style="color:var(--ink)">{{ $index + 1 }}. {{ $stepRow->step?->step_name }}</strong>
                                <div class="small text-muted">{{ \Illuminate\Support\Str::limit(strip_tags((string) ($stepRow->step?->description ?? '')), 120) }}</div>
                            </div>
                            <span class="np-chip {{ $isDone ? 'ok' : 'warn' }}">{{ $isDone ? 'Completed' : 'Pending' }}</span>
                        </div>
                        <div class="step-body">
                            <form method="POST" action="{{ route('notice-period.step.update', $stepRow) }}" enctype="multipart/form-data" class="mb-2">
                                @csrf
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" name="status" value="1" id="status_{{ $stepRow->id }}" @checked($isDone)>
                                    <label class="form-check-label" for="status_{{ $stepRow->id }}">Mark as completed</label>
                                </div>
                                <div class="mb-2">
                                    <label class="form-label">Comment</label>
                                    <textarea name="comment" class="form-control" rows="2">{{ $stepRow->comment }}</textarea>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Upload files</label>
                                    <input type="file" name="files[]" class="form-control" multiple>
                                </div>
                                <button class="btn add-btn btn-sm">Save Step</button>
                                <span class="small text-muted ms-2">Updated: {{ optional($stepRow->update_date)->format('d M Y H:i') ?: '—' }}</span>
                            </form>
                            @if($stepFiles->isNotEmpty())
                                <div class="mt-2">
                                    <div class="k mb-1">Attached files</div>
                                    @foreach($stepFiles as $file)
                                        <div class="file-row">
                                            <a href="{{ route('notice-period.files.download', $file) }}">{{ $file->document_name }}</a>
                                            <form method="POST" action="{{ route('notice-period.files.destroy', $file) }}" onsubmit="return confirm('Delete this file?');">
                                                @csrf @method('DELETE')
                                                <button class="btn btn-sm btn-outline-danger"><i class="fa-regular fa-trash-can"></i></button>
                                            </form>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="text-muted">No steps configured. <a href="{{ route('notice-period-steps.index') }}">Add steps</a></div>
                @endforelse
            </div>
        </div>
    @endif

    <div class="np-panel">
        <div class="np-panel-head"><h5>Resignation history</h5></div>
        <div class="np-panel-body">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                    <tr>
                        <th>When</th>
                        <th>Status</th>
                        <th>Days</th>
                        <th>By</th>
                        <th>Comment</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($history as $row)
                        <tr>
                            <td>{{ optional($row->changed_at)->format('d M Y H:i') }}</td>
                            <td>{{ $row->status }}</td>
                            <td>{{ $row->notice_period_days }}</td>
                            <td>{{ $row->changer?->full_name ?: '—' }}</td>
                            <td>{{ $row->comment }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-muted">No history yet.</td></tr>
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
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.step-toggle').forEach(function (el) {
        el.addEventListener('click', function () {
            el.closest('.step-card').classList.toggle('open');
        });
    });

    const status = document.getElementById('resignation_status');
    const wrap = document.getElementById('decline_reason_wrap');
    const reason = document.getElementById('decline_reason');
    status?.addEventListener('change', function () {
        const declined = this.value === 'Declined';
        if (wrap) wrap.style.display = declined ? '' : 'none';
        if (reason) reason.required = declined;
        if (declined && !reason.value) {
            reason?.focus();
        }
    });

    document.getElementById('showAllSteps')?.addEventListener('click', function () {
        document.querySelectorAll('.step-card').forEach(c => c.style.display = '');
    });
    document.getElementById('showIncomplete')?.addEventListener('click', function () {
        document.querySelectorAll('.step-card').forEach(c => {
            c.style.display = c.dataset.incomplete === '1' ? '' : 'none';
        });
    });
});
</script>
@endpush
