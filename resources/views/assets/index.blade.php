@extends('layouts.app')

@section('title', 'Assets')
@section('heading', 'Assets')

@section('page_actions')
<a href="{{ route('assets.assignments') }}" class="btn btn-outline-secondary me-2">
    <i class="la la-exchange"></i> Assignments
</a>
<a href="#" class="btn add-btn" data-bs-toggle="modal" data-bs-target="#add_asset">
    <i class="la la-plus-circle"></i> Add Asset
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
        width:48px; height:48px; border-radius:12px; object-fit:cover;
        border:1px solid var(--line); background:var(--soft);
    }
    .ast-wrap .ast-thumb.placeholder {
        display:inline-flex; align-items:center; justify-content:center;
        background:linear-gradient(135deg,#0f2744,#1f4a78); color:#fff; font-weight:800; font-size:.85rem;
    }
    .ast-wrap .badge-open {
        display:inline-flex; padding:.28rem .65rem; border-radius:999px;
        background:#fff7ed; color:#c2410c; border:1px solid #ffd7b0; font-size:.72rem; font-weight:650;
    }
    .ast-wrap .badge-free {
        display:inline-flex; padding:.28rem .65rem; border-radius:999px;
        background:#ecfdf5; color:#047857; border:1px solid #a7f3d0; font-size:.72rem; font-weight:650;
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
                <div class="k">Total assets</div>
                <p class="v">{{ $total }}</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="ast-metric">
                <div class="k">Currently assigned</div>
                <p class="v">{{ $assignedCount }}</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="ast-metric">
                <div class="k">Available</div>
                <p class="v">{{ max(0, $total - $assignedCount) }}</p>
            </div>
        </div>
    </div>

    <div class="ast-panel mb-3">
        <div class="ast-panel-head">
            <h5><i class="fa-solid fa-magnifying-glass me-1" style="color:var(--accent)"></i> Search assets</h5>
        </div>
        <div class="ast-panel-body">
            <form method="GET" action="{{ route('assets.index') }}" class="row g-2 align-items-end">
                <div class="col-md-9">
                    <label class="form-label">Name / asset ID</label>
                    <input type="search" name="q" value="{{ $q }}" class="form-control" placeholder="Search assets...">
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button class="btn add-btn flex-fill">Search</button>
                    @if($q !== '')
                        <a href="{{ route('assets.index') }}" class="btn btn-outline-secondary">Clear</a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <div class="ast-panel">
        <div class="ast-panel-head">
            <h5>Asset catalog</h5>
            <span class="small text-muted">{{ $assets->total() }} record(s)</span>
        </div>
        <div class="ast-panel-body">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                    <tr>
                        <th style="width:64px;"></th>
                        <th>Asset</th>
                        <th>Asset ID</th>
                        <th>Qty</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($assets as $asset)
                        @php
                            $open = $asset->openAssignments->first();
                            $img = $asset->imageUrl();
                            $initials = strtoupper(substr((string) $asset->asset_name, 0, 2));
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
                                <a class="ast-name" href="{{ route('assets.show', $asset) }}">{{ $asset->asset_name }}</a>
                            </td>
                            <td><code>{{ $asset->asset_id }}</code></td>
                            <td>{{ $asset->quantity }}</td>
                            <td>
                                @if($open)
                                    <span class="badge-open">Assigned · {{ $open->assignee?->full_name ?? '—' }}</span>
                                @else
                                    <span class="badge-free">Available</span>
                                @endif
                            </td>
                            <td class="text-end text-nowrap">
                                <a href="{{ route('assets.show', $asset) }}" class="btn btn-sm btn-outline-secondary">View</a>
                                <button type="button" class="btn btn-sm btn-outline-primary"
                                    data-bs-toggle="modal" data-bs-target="#edit_asset_{{ $asset->id }}">Edit</button>
                                <form method="POST" action="{{ route('assets.destroy', $asset) }}" class="d-inline"
                                      onsubmit="return confirm('Delete this asset?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger" @disabled($open)>Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-muted py-4 text-center">No assets found.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $assets->links() }}</div>
        </div>
    </div>
</div>

{{-- Add modal --}}
<div class="modal fade" id="add_asset" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:16px;border:0;">
            <form method="POST" action="{{ route('assets.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold">Add asset</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-2">
                        <label class="form-label">Asset name</label>
                        <input type="text" name="asset_name" class="form-control" value="{{ old('asset_name') }}" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Asset ID</label>
                        <input type="text" name="asset_id" class="form-control" value="{{ old('asset_id') }}" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Quantity</label>
                        <input type="number" name="quantity" min="0" class="form-control" value="{{ old('quantity', 1) }}" required>
                    </div>
                    <div class="mb-0">
                        <label class="form-label">Image (optional)</label>
                        <input type="file" name="image" class="form-control" accept="image/*">
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button class="btn add-btn">Create</button>
                </div>
            </form>
        </div>
    </div>
</div>

@foreach($assets as $asset)
<div class="modal fade" id="edit_asset_{{ $asset->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:16px;border:0;">
            <form method="POST" action="{{ route('assets.update', $asset) }}" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold">Edit asset</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-2">
                        <label class="form-label">Asset name</label>
                        <input type="text" name="asset_name" class="form-control" value="{{ old('asset_name', $asset->asset_name) }}" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Asset ID</label>
                        <input type="text" name="asset_id" class="form-control" value="{{ old('asset_id', $asset->asset_id) }}" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Quantity</label>
                        <input type="number" name="quantity" min="0" class="form-control" value="{{ old('quantity', $asset->quantity) }}" required>
                    </div>
                    <div class="mb-0">
                        <label class="form-label">Replace image (optional)</label>
                        <input type="file" name="image" class="form-control" accept="image/*">
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button class="btn add-btn">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endforeach
@endsection
