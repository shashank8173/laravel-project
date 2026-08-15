@extends('layouts.app')

@section('title', 'My Resignation')
@section('heading', 'My Resignation')

@section('page_actions')
<button type="button" class="btn btn-notice" data-bs-toggle="modal" data-bs-target="#resignationNoticeModal">
    <i class="fa-solid fa-circle-info me-1"></i> Notice
</button>
@if($canApply)
<button type="button" class="btn add-btn" data-bs-toggle="modal" data-bs-target="#applyResignationModal">
    <i class="fa-solid fa-plus"></i> Apply resignation
</button>
@endif
@endsection

@push('styles')
<style>
    .rg-wrap { --ink:#0f2744; --muted:#6b7c93; --line:#e8eef5; --soft:#f4f7fb; --accent:#ff9b44; }
    .rg-wrap .rg-panel { background:#fff; border:1px solid var(--line); border-radius:18px; overflow:hidden; height:100%; }
    .rg-wrap .rg-panel-head {
        display:flex; justify-content:space-between; align-items:center; gap:1rem; flex-wrap:wrap;
        padding:1rem 1.15rem; border-bottom:1px solid var(--line);
        background:linear-gradient(180deg,#fff,#fafbfd);
    }
    .rg-wrap .rg-panel-head h5 { margin:0; font-weight:750; color:var(--ink); font-size:1rem; }
    .rg-wrap .rg-panel-body { padding:1.15rem; }
    .rg-wrap .rg-metric {
        border:1px solid var(--line); border-radius:14px; background:#fff; padding:1rem 1.05rem; height:100%;
        position:relative; overflow:hidden;
    }
    .rg-wrap .rg-metric::before { content:""; position:absolute; left:0; top:0; bottom:0; width:4px; background:var(--accent); }
    .rg-wrap .rg-metric.is-amber::before { background:#f59e0b; }
    .rg-wrap .rg-metric.is-green::before { background:#16a34a; }
    .rg-wrap .rg-metric.is-red::before { background:#ef4444; }
    .rg-wrap .rg-metric .k { font-size:.68rem; text-transform:uppercase; letter-spacing:.04em; color:var(--muted); font-weight:700; }
    .rg-wrap .rg-metric .v { font-size:1.35rem; font-weight:800; color:var(--ink); margin:.15rem 0 0; line-height:1.1; }
    .rg-wrap .form-label { font-size:.8rem; font-weight:600; color:var(--muted); }
    .rg-wrap .table > :not(caption) > * > * { vertical-align:middle; }
    .rg-wrap .name { font-weight:750; color:var(--ink); margin:0; }
    .rg-wrap .sub { font-size:.78rem; color:var(--muted); }
    .rg-wrap .rg-pill {
        display:inline-flex; padding:.28rem .65rem; border-radius:999px; font-size:.72rem; font-weight:700;
    }
    .rg-wrap .st-pending { background:#fef3c7; color:#b45309; }
    .rg-wrap .st-approved { background:#dcfce7; color:#15803d; }
    .rg-wrap .st-declined { background:#fee2e2; color:#b91c1c; }
    .rg-wrap .hero {
        border:1px solid var(--line); border-radius:18px; background:#fff; padding:1.25rem 1.35rem;
        display:flex; justify-content:space-between; gap:1rem; flex-wrap:wrap; align-items:flex-start;
    }
    .rg-wrap .hero .label { font-size:.72rem; text-transform:uppercase; letter-spacing:.04em; color:var(--muted); font-weight:700; }
    .rg-wrap .hero .title { font-size:1.15rem; font-weight:800; color:var(--ink); margin:.2rem 0 .55rem; }
    .rg-wrap .meta-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(140px,1fr)); gap:.75rem 1.25rem; }
    .rg-wrap .meta-grid .k { font-size:.72rem; color:var(--muted); font-weight:600; }
    .rg-wrap .meta-grid .v { font-size:.92rem; color:var(--ink); font-weight:700; }
    .rg-wrap .reason-box {
        margin-top:1rem; padding:0.9rem 1rem; border-radius:12px; background:var(--soft); color:#334155; font-size:.9rem;
    }
    .rg-wrap .timeline-item {
        display:flex; gap:.85rem; padding:.75rem 0; border-bottom:1px solid var(--line);
    }
    .rg-wrap .timeline-item:last-child { border-bottom:0; padding-bottom:0; }
    .rg-wrap .timeline-dot {
        width:10px; height:10px; border-radius:50%; background:var(--accent); margin-top:.45rem; flex-shrink:0;
    }
    .btn-notice {
        border:1px solid #f59e0b; color:#92400e; background:#fffbeb; font-weight:700; border-radius:999px;
        padding:.35rem .9rem; font-size:.8rem;
    }
    .btn-notice:hover { background:#fef3c7; color:#78350f; border-color:#d97706; }
    .rg-wrap .empty {
        text-align:center; padding:2rem 1rem; color:var(--muted);
    }
</style>
@endpush

@section('content')
@php
    $statusClass = fn ($s) => match ($s) {
        'Approved' => 'st-approved',
        'Declined' => 'st-declined',
        default => 'st-pending',
    };
@endphp
<div class="rg-wrap">
    <div class="row g-3 mb-3">
        <div class="col-6 col-lg-3">
            <div class="rg-metric">
                <div class="k">Total</div>
                <p class="v">{{ $stats['total'] }}</p>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="rg-metric is-amber">
                <div class="k">Pending</div>
                <p class="v">{{ $stats['pending'] }}</p>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="rg-metric is-green">
                <div class="k">Approved</div>
                <p class="v">{{ $stats['approved'] }}</p>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="rg-metric is-red">
                <div class="k">Declined</div>
                <p class="v">{{ $stats['declined'] }}</p>
            </div>
        </div>
    </div>

    @if($active)
        <div class="hero mb-3">
            <div style="flex:1;min-width:240px;">
                <div class="label">Active resignation</div>
                <div class="title">
                    Status:
                    <span class="rg-pill {{ $statusClass($active->status) }}">{{ $active->status }}</span>
                </div>
                <div class="meta-grid">
                    <div>
                        <div class="k">Submitted</div>
                        <div class="v">{{ optional($active->submitted_at)->format('d M Y') ?: '—' }}</div>
                    </div>
                    <div>
                        <div class="k">Intended last date</div>
                        <div class="v">{{ optional($active->intended_last_date)->format('d M Y') ?: '—' }}</div>
                    </div>
                    <div>
                        <div class="k">Notice period</div>
                        <div class="v">{{ $active->notice_period_days }} days</div>
                    </div>
                    @if($active->approved_at)
                        <div>
                            <div class="k">Reviewed on</div>
                            <div class="v">{{ $active->approved_at->format('d M Y') }}</div>
                        </div>
                    @endif
                </div>
                @if($active->resignation_reason)
                    <div class="reason-box">
                        <strong style="color:var(--ink)">Reason:</strong>
                        {{ $active->resignation_reason }}
                    </div>
                @endif
                @if($active->status === 'Declined' && $active->decline_reason)
                    <div class="reason-box" style="background:#fef2f2;color:#991b1b;">
                        <strong>Decline reason:</strong> {{ $active->decline_reason }}
                    </div>
                @endif
            </div>
            <div class="d-flex flex-column gap-2">
                @if($active->status === 'Pending')
                    <form method="POST" action="{{ route('resignation.destroy', $active) }}"
                          onsubmit="return confirm('Withdraw this resignation? Notice period steps will be cleared.')">
                        @csrf @method('DELETE')
                        <button class="btn btn-outline-danger">Withdraw</button>
                    </form>
                @elseif($active->status === 'Approved')
                    <span class="rg-pill st-approved align-self-start">In notice period</span>
                @endif
            </div>
        </div>
    @else
        <div class="rg-panel mb-3">
            <div class="rg-panel-body empty">
                <p class="name mb-1">No active resignation</p>
                <p class="sub mb-3">You can submit a new resignation when you are ready.</p>
                @if($canApply)
                    <button type="button" class="btn add-btn" data-bs-toggle="modal" data-bs-target="#applyResignationModal">
                        <i class="fa-solid fa-plus"></i> Apply resignation
                    </button>
                @endif
            </div>
        </div>
    @endif

    <div class="row g-3">
        <div class="col-lg-7">
            <div class="rg-panel">
                <div class="rg-panel-head">
                    <h5><i class="fa-solid fa-file-lines me-1" style="color:var(--accent)"></i> My applications</h5>
                    <span class="small text-muted">{{ $resignations->count() }} record(s)</span>
                </div>
                <div class="table-responsive p-2">
                    <table class="table align-middle mb-0">
                        <thead>
                        <tr>
                            <th>Submitted</th>
                            <th>Last date</th>
                            <th>Notice</th>
                            <th>Status</th>
                            <th class="text-end">Action</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($resignations as $resignation)
                            <tr>
                                <td>
                                    <p class="name">{{ optional($resignation->submitted_at)->format('d M Y') ?: '—' }}</p>
                                    <div class="sub">{{ \Illuminate\Support\Str::limit($resignation->resignation_reason, 42) }}</div>
                                </td>
                                <td>{{ optional($resignation->intended_last_date)->format('d M Y') ?: '—' }}</td>
                                <td>{{ $resignation->notice_period_days }} days</td>
                                <td><span class="rg-pill {{ $statusClass($resignation->status) }}">{{ $resignation->status }}</span></td>
                                <td class="text-end">
                                    @if($resignation->status === 'Pending')
                                        <form method="POST" action="{{ route('resignation.destroy', $resignation) }}" class="d-inline"
                                              onsubmit="return confirm('Withdraw this resignation?')">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger">Withdraw</button>
                                        </form>
                                    @elseif($resignation->status === 'Declined' && $resignation->decline_reason)
                                        <span class="sub" title="{{ $resignation->decline_reason }}">Declined</span>
                                    @else
                                        <span class="sub">—</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">No resignation applications yet.</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="rg-panel">
                <div class="rg-panel-head">
                    <h5><i class="fa-solid fa-clock-rotate-left me-1" style="color:var(--accent)"></i> History</h5>
                </div>
                <div class="rg-panel-body">
                    @forelse($history as $item)
                        <div class="timeline-item">
                            <span class="timeline-dot"></span>
                            <div>
                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                    <span class="rg-pill {{ $statusClass($item->status) }}">{{ $item->status }}</span>
                                    <span class="sub">{{ optional($item->changed_at)->format('d M Y, h:i A') }}</span>
                                </div>
                                <div class="name mt-1" style="font-size:.9rem;">{{ $item->comment ?: 'Status update' }}</div>
                                <div class="sub">
                                    By {{ $item->changer?->full_name ?: 'System' }}
                                    @if($item->notice_period_days)
                                        · Notice {{ $item->notice_period_days }} days
                                    @endif
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="empty py-3">No history yet.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>

@if($canApply)
<div class="modal fade" id="applyResignationModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border:0;border-radius:16px;">
            <form method="POST" action="{{ route('resignation.store') }}">
                @csrf
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold">Apply resignation</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body rg-wrap">
                    @error('resignation')
                        <div class="alert alert-danger py-2">{{ $message }}</div>
                    @enderror
                    <div class="mb-3">
                        <label class="form-label">Reason</label>
                        <textarea name="resignation_reason" class="form-control" rows="4" required placeholder="Share your reason for leaving">{{ old('resignation_reason') }}</textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Intended last working date</label>
                        <input type="date" name="intended_last_date" class="form-control"
                               value="{{ old('intended_last_date') }}" min="{{ now()->addDay()->format('Y-m-d') }}" required>
                    </div>
                    <div class="mb-1">
                        <label class="form-label">Notice period</label>
                        <select name="notice_period_days" class="form-select" required>
                            <option value="15" @selected(old('notice_period_days') == 15)>15 days</option>
                            <option value="30" @selected(old('notice_period_days', 30) == 30)>30 days</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button class="btn add-btn">Submit</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

<div class="modal fade" id="resignationNoticeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border:0;border-radius:16px;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold">Resignation notice</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body rg-wrap" style="color:#334155;font-size:.92rem;">
                <p>Use this page to submit your resignation and track approval status.</p>
                <ul class="mb-0 ps-3">
                    <li class="mb-2">You can have only one active resignation (Pending or Approved).</li>
                    <li class="mb-2">Pending resignations can be withdrawn anytime.</li>
                    <li class="mb-2">Once approved, notice-period steps start for exit clearance.</li>
                    <li>If declined, you can apply again after reviewing the reason.</li>
                </ul>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn add-btn" data-bs-dismiss="modal">Got it</button>
            </div>
        </div>
    </div>
</div>
@endsection

@if($errors->any() && $canApply)
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const el = document.getElementById('applyResignationModal');
    if (el) new bootstrap.Modal(el).show();
});
</script>
@endpush
@endif
