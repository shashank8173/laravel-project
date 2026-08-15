@extends('layouts.app')

@section('title', 'Asset Assignments')
@section('heading', 'Asset Assignments')

@section('page_actions')
<a href="{{ route('assets.index') }}" class="btn btn-outline-secondary me-2">
    <i class="la la-boxes"></i> Asset catalog
</a>
<a href="#" class="btn add-btn" data-bs-toggle="modal" data-bs-target="#assign_asset">
    <i class="la la-plus-circle"></i> Assign asset
</a>
@endsection

@push('styles')
<style>
    .ast-wrap { --ink:#0f2744; --muted:#6b7c93; --line:#e8eef5; --soft:#f4f7fb; --accent:#ff9b44; }
    .ast-wrap .ast-panel {
        background:#fff; border:1px solid var(--line); border-radius:18px; overflow:hidden;
    }
    .ast-wrap .ast-panel-head {
        display:flex; align-items:center; justify-content:space-between; gap:1rem; flex-wrap:wrap;
        padding:1rem 1.15rem; border-bottom:1px solid var(--line);
        background:linear-gradient(180deg,#fff,#fafbfd);
    }
    .ast-wrap .ast-panel-head h5 { margin:0; font-weight:750; color:var(--ink); font-size:1.05rem; }
    .ast-wrap .ast-panel-body { padding:1rem 1.15rem; }
    .ast-wrap .ast-metric {
        border:1px solid var(--line); border-radius:14px; background:#fff; padding:1rem 1.1rem; height:100%;
        position:relative; overflow:hidden;
    }
    .ast-wrap .ast-metric::before {
        content:""; position:absolute; left:0; top:0; bottom:0; width:4px; background:var(--accent);
    }
    .ast-wrap .ast-metric .k {
        font-size:.7rem; text-transform:uppercase; letter-spacing:.04em; color:var(--muted); font-weight:700;
    }
    .ast-wrap .ast-metric .v { font-size:1.55rem; font-weight:800; color:var(--ink); margin:0; line-height:1.1; }
    .ast-wrap .ast-thumb {
        width:40px; height:40px; border-radius:10px; object-fit:cover;
        border:1px solid var(--line); background:var(--soft);
    }
    .ast-wrap .ast-thumb.placeholder {
        display:inline-flex; align-items:center; justify-content:center;
        background:linear-gradient(135deg,#0f2744,#1f4a78); color:#fff; font-weight:800; font-size:.75rem;
    }
    .ast-wrap .badge-open {
        display:inline-flex; padding:.28rem .65rem; border-radius:999px;
        background:#fff7ed; color:#c2410c; border:1px solid #ffd7b0; font-size:.72rem; font-weight:650;
    }
    .ast-wrap .badge-free {
        display:inline-flex; padding:.28rem .65rem; border-radius:999px;
        background:#ecfdf5; color:#047857; border:1px solid #a7f3d0; font-size:.72rem; font-weight:650;
    }
    .ast-wrap .filter-pill {
        display:inline-flex; align-items:center; padding:.4rem .85rem; border-radius:999px;
        border:1px solid var(--line); color:var(--muted); font-weight:650; font-size:.82rem; text-decoration:none;
        background:#fff;
    }
    .ast-wrap .filter-pill.active {
        background:#fff7ed; border-color:#ffd7b0; color:#c2410c;
    }
    .ast-wrap .table > :not(caption) > * > * { vertical-align:middle; }
    .ast-wrap .form-label { font-size:.8rem; font-weight:600; color:var(--muted); }
    .ast-wrap a.ast-name { color:var(--ink); font-weight:700; text-decoration:none; }
    .ast-wrap a.ast-name:hover { color:var(--accent); }
</style>
@endpush

@section('content')
<div class="ast-wrap">
    <div class="row g-3 mb-3">
        <div class="col-md-4">
            <div class="ast-metric">
                <div class="k">Open assignments</div>
                <p class="v">{{ $openCount }}</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="ast-metric">
                <div class="k">Returned</div>
                <p class="v">{{ $returnedCount }}</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="ast-metric">
                <div class="k">Available assets</div>
                <p class="v">{{ $availableAssets->count() }}</p>
            </div>
        </div>
    </div>

    <div class="ast-panel mb-3">
        <div class="ast-panel-head">
            <h5><i class="fa-solid fa-magnifying-glass me-1" style="color:var(--accent)"></i> Filter assignments</h5>
            <div class="d-flex flex-wrap gap-2">
                <a class="filter-pill {{ $status === 'all' ? 'active' : '' }}"
                   href="{{ route('assets.assignments', array_filter(['q' => $q ?: null, 'status' => 'all'])) }}">All</a>
                <a class="filter-pill {{ $status === 'open' ? 'active' : '' }}"
                   href="{{ route('assets.assignments', array_filter(['q' => $q ?: null, 'status' => 'open'])) }}">Open</a>
                <a class="filter-pill {{ $status === 'returned' ? 'active' : '' }}"
                   href="{{ route('assets.assignments', array_filter(['q' => $q ?: null, 'status' => 'returned'])) }}">Returned</a>
            </div>
        </div>
        <div class="ast-panel-body">
            <form method="GET" action="{{ route('assets.assignments') }}" class="row g-2 align-items-end">
                <input type="hidden" name="status" value="{{ $status }}">
                <div class="col-md-9">
                    <label class="form-label">Asset / employee / dates</label>
                    <input type="search" name="q" value="{{ $q }}" class="form-control" placeholder="Search assignments...">
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button class="btn add-btn flex-fill">Search</button>
                    @if($q !== '' || $status !== 'all')
                        <a href="{{ route('assets.assignments') }}" class="btn btn-outline-secondary">Clear</a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <div class="ast-panel">
        <div class="ast-panel-head">
            <h5>Assignment log</h5>
            <span class="small text-muted">{{ $assignments->total() }} record(s)</span>
        </div>
        <div class="ast-panel-body">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                    <tr>
                        <th style="width:52px;"></th>
                        <th>Asset</th>
                        <th>Assignee</th>
                        <th>Issued</th>
                        <th>Returned</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($assignments as $assignment)
                        @php
                            $img = $assignment->asset?->imageUrl();
                            $initials = strtoupper(substr((string) ($assignment->asset?->asset_name ?? 'AS'), 0, 2));
                            $isOpen = empty($assignment->return_date);
                        @endphp
                        <tr>
                            <td>
                                @if($img)
                                    <img src="{{ $img }}" alt="" class="ast-thumb">
                                @else
                                    <span class="ast-thumb placeholder">{{ $initials }}</span>
                                @endif
                            </td>
                            <td>
                                @if($assignment->asset)
                                    <a class="ast-name" href="{{ route('assets.show', $assignment->asset) }}">
                                        {{ $assignment->asset->asset_name }}
                                    </a>
                                    <div class="small text-muted"><code>{{ $assignment->asset->asset_id }}</code></div>
                                @else
                                    —
                                @endif
                            </td>
                            <td>
                                <div class="fw-semibold" style="color:var(--ink);">{{ $assignment->assignee?->full_name ?? '—' }}</div>
                                <div class="small text-muted">{{ $assignment->assignee?->emp_id ?? '' }}</div>
                            </td>
                            <td>{{ $assignment->issued_date?->format('d M Y') ?? $assignment->assigned_date?->format('d M Y') ?? '—' }}</td>
                            <td>{{ $assignment->return_date?->format('d M Y') ?? '—' }}</td>
                            <td>
                                @if($isOpen)
                                    <span class="badge-open">{{ $assignment->action ?: 'Assigned' }}</span>
                                @else
                                    <span class="badge-free">{{ $assignment->action ?: 'Returned' }}</span>
                                @endif
                            </td>
                            <td class="text-end text-nowrap">
                                @if($isOpen)
                                    <button type="button" class="btn btn-sm btn-outline-success"
                                        data-bs-toggle="modal" data-bs-target="#return_{{ $assignment->id }}">Return</button>
                                @endif
                                <form method="POST" action="{{ route('assets.assignment.destroy', $assignment) }}" class="d-inline"
                                      onsubmit="return confirm('Delete this assignment?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-muted py-4 text-center">No assignments found.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $assignments->links() }}</div>
        </div>
    </div>
</div>

{{-- Assign modal --}}
<div class="modal fade" id="assign_asset" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:16px;border:0;">
            <form method="POST" action="{{ route('assets.assign') }}">
                @csrf
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold">Assign asset</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    @if($availableAssets->isEmpty())
                        <div class="alert alert-warning mb-0">No available assets. Return an open assignment or add a new asset first.</div>
                    @else
                        <div class="mb-2">
                            <label class="form-label">Asset</label>
                            <select name="asset_id" class="form-select" required>
                                @foreach($availableAssets as $asset)
                                    <option value="{{ $asset->id }}">{{ $asset->asset_name }} ({{ $asset->asset_id }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Assignee</label>
                            <x-employee-select
                                name="assignee_id"
                                :employees="$employees"
                                :required="true"
                                placeholder="Search assignee…"
                            />
                        </div>
                        <div class="mb-0">
                            <label class="form-label">Issued date</label>
                            <input type="date" name="issued_date" class="form-control" value="{{ old('issued_date', now()->toDateString()) }}" required>
                        </div>
                    @endif
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    @if($availableAssets->isNotEmpty())
                        <button class="btn add-btn">Assign</button>
                    @endif
                </div>
            </form>
        </div>
    </div>
</div>

@foreach($assignments as $assignment)
    @if(empty($assignment->return_date))
    <div class="modal fade" id="return_{{ $assignment->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border-radius:16px;border:0;">
                <form method="POST" action="{{ route('assets.return', $assignment) }}">
                    @csrf
                    <div class="modal-header border-0 pb-0">
                        <h5 class="modal-title fw-bold">Mark returned</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p class="mb-2 text-muted">
                            {{ $assignment->asset?->asset_name ?? 'Asset' }} ·
                            {{ $assignment->assignee?->full_name ?? 'Assignee' }}
                        </p>
                        <label class="form-label">Return date</label>
                        <input type="date" name="return_date" class="form-control" value="{{ now()->toDateString() }}" required>
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button class="btn add-btn">Confirm return</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif
@endforeach
@endsection
