@extends('layouts.app')

@section('title', $asset->asset_name)
@section('heading', 'Asset details')

@section('page_actions')
<a href="{{ route('assets.index') }}" class="btn btn-outline-secondary me-2">
    <i class="la la-arrow-left"></i> Catalog
</a>
<a href="{{ route('assets.assignments') }}" class="btn add-btn">
    <i class="la la-exchange"></i> Assignments
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
    .ast-wrap .ast-panel-body { padding:1.15rem; }
    .ast-wrap .ast-hero {
        display:grid; grid-template-columns:160px 1fr; gap:1.25rem; align-items:center;
        background:linear-gradient(135deg,#fff8f2,#fff); border:1px solid #ffe1c4;
        border-radius:18px; padding:1.25rem;
    }
    @media (max-width:575px) {
        .ast-wrap .ast-hero { grid-template-columns:1fr; text-align:center; }
        .ast-wrap .ast-hero .photo { margin:0 auto; }
    }
    .ast-wrap .ast-hero .photo {
        width:160px; height:160px; border-radius:22px; object-fit:cover;
        border:3px solid #fff; box-shadow:0 10px 24px rgba(15,39,68,.12); background:#fff;
    }
    .ast-wrap .ast-hero .photo.placeholder {
        display:flex; align-items:center; justify-content:center;
        background:linear-gradient(135deg,#0f2744,#1f4a78); color:#fff; font-size:2rem; font-weight:800;
    }
    .ast-wrap .badge-open {
        display:inline-flex; padding:.3rem .7rem; border-radius:999px;
        background:#fff7ed; color:#c2410c; border:1px solid #ffd7b0; font-size:.72rem; font-weight:700;
        text-transform:uppercase; letter-spacing:.04em;
    }
    .ast-wrap .badge-free {
        display:inline-flex; padding:.3rem .7rem; border-radius:999px;
        background:#ecfdf5; color:#047857; border:1px solid #a7f3d0; font-size:.72rem; font-weight:700;
        text-transform:uppercase; letter-spacing:.04em;
    }
    .ast-wrap .meta-row { display:flex; flex-wrap:wrap; gap:1rem 1.5rem; margin-top:.85rem; }
    .ast-wrap .meta-row .k { display:block; font-size:.7rem; text-transform:uppercase; letter-spacing:.04em; color:var(--muted); font-weight:700; }
    .ast-wrap .meta-row .v { font-weight:700; color:var(--ink); }
    .ast-wrap .table > :not(caption) > * > * { vertical-align:middle; }
</style>
@endpush

@section('content')
@php
    $open = $asset->openAssignments->first();
    $img = $asset->imageUrl();
    $initials = strtoupper(substr((string) $asset->asset_name, 0, 2));
@endphp
<div class="ast-wrap">
    <div class="ast-hero mb-3">
        @if($img)
            <img src="{{ $img }}" alt="" class="photo">
        @else
            <div class="photo placeholder">{{ $initials }}</div>
        @endif
        <div>
            @if($open)
                <span class="badge-open mb-2">Assigned</span>
            @else
                <span class="badge-free mb-2">Available</span>
            @endif
            <h3 class="mb-1 fw-bold" style="color:var(--ink);">{{ $asset->asset_name }}</h3>
            <div class="text-muted fw-semibold">ID · <code>{{ $asset->asset_id }}</code></div>
            <div class="meta-row">
                <div>
                    <span class="k">Quantity</span>
                    <span class="v">{{ $asset->quantity }}</span>
                </div>
                <div>
                    <span class="k">Current holder</span>
                    <span class="v">{{ $open?->assignee?->full_name ?? '—' }}</span>
                </div>
                <div>
                    <span class="k">Issued</span>
                    <span class="v">{{ $open?->issued_date?->format('d M Y') ?? $open?->assigned_date?->format('d M Y') ?? '—' }}</span>
                </div>
            </div>
            <div class="mt-3 d-flex flex-wrap gap-2">
                <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#edit_asset">Edit</button>
                @if(!$open)
                    <form method="POST" action="{{ route('assets.destroy', $asset) }}"
                          onsubmit="return confirm('Delete this asset?')">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-sm btn-outline-danger">Delete</button>
                    </form>
                @endif
            </div>
        </div>
    </div>

    <div class="ast-panel">
        <div class="ast-panel-head">
            <h5>Assignment history</h5>
            <span class="small text-muted">{{ $history->total() }} record(s)</span>
        </div>
        <div class="ast-panel-body">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                    <tr>
                        <th>Assignee</th>
                        <th>Issued</th>
                        <th>Returned</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($history as $row)
                        <tr>
                            <td>
                                <div class="fw-semibold" style="color:var(--ink);">{{ $row->assignee?->full_name ?? '—' }}</div>
                                <div class="small text-muted">{{ $row->assignee?->emp_id ?? '' }}</div>
                            </td>
                            <td>{{ $row->issued_date?->format('d M Y') ?? $row->assigned_date?->format('d M Y') ?? '—' }}</td>
                            <td>{{ $row->return_date?->format('d M Y') ?? '—' }}</td>
                            <td>
                                @if($row->return_date)
                                    <span class="badge-free">Returned</span>
                                @else
                                    <span class="badge-open">Open</span>
                                @endif
                            </td>
                            <td class="text-end text-nowrap">
                                @if(! $row->return_date)
                                    <form method="POST" action="{{ route('assets.return', $row) }}" class="d-inline">
                                        @csrf
                                        <button class="btn btn-sm btn-outline-success">Return</button>
                                    </form>
                                @endif
                                <form method="POST" action="{{ route('assets.assignment.destroy', $row) }}" class="d-inline"
                                      onsubmit="return confirm('Delete this assignment record?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-muted py-4 text-center">No assignment history yet.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $history->links() }}</div>
        </div>
    </div>
</div>

<div class="modal fade" id="edit_asset" tabindex="-1" aria-hidden="true">
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
@endsection
