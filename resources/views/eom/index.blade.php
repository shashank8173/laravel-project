@extends('layouts.app')

@section('title', 'Employee of the Month')
@section('heading', 'Employee of the Month')

@section('page_actions')
@if($isAdmin)
<a href="#" class="btn add-btn" data-bs-toggle="modal" data-bs-target="#add_eom">
    <i class="la la-plus-circle"></i> Add Winner
</a>
@endif
@endsection

@push('styles')
<style>
    .eom-wrap { --ink:#0f2744; --muted:#6b7c93; --line:#e8eef5; --soft:#f4f7fb; --accent:#ff9b44; }
    .eom-wrap .eom-panel {
        background:#fff; border:1px solid var(--line); border-radius:18px; overflow:hidden;
    }
    .eom-wrap .eom-panel-head {
        display:flex; align-items:center; justify-content:space-between; gap:1rem; flex-wrap:wrap;
        padding:1rem 1.15rem; border-bottom:1px solid var(--line);
        background:linear-gradient(180deg,#fff,#fafbfd);
    }
    .eom-wrap .eom-panel-head h5 { margin:0; font-weight:750; color:var(--ink); font-size:1.05rem; }
    .eom-wrap .eom-panel-body { padding:1.15rem; }
    .eom-wrap .eom-metric {
        border:1px solid var(--line); border-radius:14px; background:#fff; padding:1rem 1.1rem; height:100%;
        position:relative; overflow:hidden;
    }
    .eom-wrap .eom-metric::before {
        content:""; position:absolute; left:0; top:0; bottom:0; width:4px; background:var(--accent);
    }
    .eom-wrap .eom-metric .k {
        font-size:.7rem; text-transform:uppercase; letter-spacing:.04em; color:var(--muted); font-weight:700;
    }
    .eom-wrap .eom-metric .v { font-size:1.55rem; font-weight:800; color:var(--ink); margin:0; line-height:1.1; }

    .eom-wrap .eom-hero {
        display:grid; grid-template-columns:140px 1fr; gap:1.25rem; align-items:center;
        background:linear-gradient(135deg,#fff8f2,#fff); border:1px solid #ffe1c4;
        border-radius:18px; padding:1.25rem;
    }
    @media (max-width:575px) {
        .eom-wrap .eom-hero { grid-template-columns:1fr; text-align:center; }
        .eom-wrap .eom-hero .photo { margin:0 auto; }
    }
    .eom-wrap .eom-hero .photo {
        width:140px; height:140px; border-radius:22px; object-fit:cover;
        border:3px solid #fff; box-shadow:0 10px 24px rgba(15,39,68,.12); background:#fff;
    }
    .eom-wrap .eom-hero .photo.placeholder {
        display:flex; align-items:center; justify-content:center;
        background:linear-gradient(135deg,#0f2744,#1f4a78); color:#fff; font-size:2rem; font-weight:800;
    }
    .eom-wrap .eom-hero .badge-soft {
        display:inline-flex; align-items:center; gap:.35rem; padding:.3rem .7rem; border-radius:999px;
        background:#fff7ed; color:#c2410c; border:1px solid #ffd7b0; font-size:.72rem; font-weight:700;
        text-transform:uppercase; letter-spacing:.04em; margin-bottom:.55rem;
    }
    .eom-wrap .eom-hero h3 { margin:0 0 .25rem; color:var(--ink); font-weight:800; }
    .eom-wrap .eom-hero .role { color:var(--muted); font-weight:650; margin-bottom:.65rem; }
    .eom-wrap .eom-hero .msg { color:var(--ink); margin:0; line-height:1.5; }

    .eom-wrap .eom-card {
        border:1px solid var(--line); border-radius:16px; background:#fff; height:100%;
        overflow:hidden; display:flex; flex-direction:column;
        transition:transform .15s ease, box-shadow .15s ease;
    }
    .eom-wrap .eom-card:hover { transform:translateY(-2px); box-shadow:0 10px 24px rgba(15,39,68,.07); }
    .eom-wrap .eom-card .top {
        padding:1rem 1rem .5rem; display:flex; gap:.85rem; align-items:center;
    }
    .eom-wrap .eom-card .avatar {
        width:64px; height:64px; border-radius:16px; object-fit:cover; flex:0 0 64px;
        border:1px solid var(--line); background:var(--soft);
    }
    .eom-wrap .eom-card .avatar.placeholder {
        display:flex; align-items:center; justify-content:center;
        background:linear-gradient(135deg,#0f2744,#1f4a78); color:#fff; font-weight:800;
    }
    .eom-wrap .eom-card .name { margin:0; font-weight:800; color:var(--ink); }
    .eom-wrap .eom-card .desig { margin:0; color:var(--muted); font-size:.85rem; font-weight:600; }
    .eom-wrap .eom-card .body { padding:.25rem 1rem 1rem; flex:1; }
    .eom-wrap .eom-card .quote {
        margin:0; color:var(--ink); font-size:.9rem; line-height:1.45;
        display:-webkit-box; -webkit-line-clamp:3; -webkit-box-orient:vertical; overflow:hidden;
    }
    .eom-wrap .eom-card .foot {
        display:flex; justify-content:space-between; align-items:center; gap:.5rem; flex-wrap:wrap;
        padding:.75rem 1rem; border-top:1px solid var(--line); background:#fafbfd;
    }
    .eom-wrap .eom-card .date { font-size:.75rem; color:var(--muted); font-weight:600; }
    .eom-wrap .form-label { font-size:.8rem; font-weight:600; color:var(--muted); }
</style>
@endpush

@section('content')
@php $total = method_exists($entries, 'total') ? $entries->total() : $entries->count(); @endphp
<div class="eom-wrap">
    <div class="row g-3 mb-3">
        <div class="col-md-4">
            <div class="eom-metric">
                <div class="k">Total winners</div>
                <p class="v">{{ $total }}</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="eom-metric">
                <div class="k">Latest</div>
                <p class="v" style="font-size:1.05rem;padding-top:.35rem;">{{ $latest?->name ?: '—' }}</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="eom-metric">
                <div class="k">Access</div>
                <p class="v" style="font-size:1.05rem;padding-top:.35rem;">{{ $isAdmin ? 'Admin · manage' : 'View only' }}</p>
            </div>
        </div>
    </div>

    @if($latest)
    <div class="eom-panel mb-3">
        <div class="eom-panel-head">
            <h5><i class="fa-solid fa-trophy me-1" style="color:var(--accent)"></i> Current highlight</h5>
            <span class="small text-muted">{{ optional($latest->created_at)->format('d M Y') ?: '—' }}</span>
        </div>
        <div class="eom-panel-body">
            <div class="eom-hero">
                @if($latest->photoUrl())
                    <img src="{{ $latest->photoUrl() }}" alt="{{ $latest->name }}" class="photo">
                @else
                    <div class="photo placeholder">{{ $latest->initials() }}</div>
                @endif
                <div>
                    <div class="badge-soft"><i class="fa-solid fa-star"></i> Employee of the Month</div>
                    <h3>{{ $latest->name }}</h3>
                    <div class="role">{{ $latest->designation }}</div>
                    <p class="msg">{{ $latest->message }}</p>
                </div>
            </div>
        </div>
    </div>
    @endif

    <div class="eom-panel mb-3">
        <div class="eom-panel-head">
            <h5><i class="fa-solid fa-magnifying-glass me-1" style="color:var(--accent)"></i> Search</h5>
        </div>
        <div class="eom-panel-body">
            <form method="GET" action="{{ route('eom.index') }}" class="row g-2 align-items-end">
                <div class="col-md-9">
                    <label class="form-label">Name / designation / message</label>
                    <input type="text" name="q" value="{{ $q }}" class="form-control" placeholder="Search winners...">
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button class="btn add-btn flex-fill">Search</button>
                    @if($q !== '')
                        <a href="{{ route('eom.index') }}" class="btn btn-outline-secondary">Clear</a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <div class="eom-panel">
        <div class="eom-panel-head">
            <h5>All winners</h5>
            <span class="small text-muted">{{ $total }} record(s)</span>
        </div>
        <div class="eom-panel-body">
            <div class="row g-3">
                @forelse($entries as $entry)
                    <div class="col-lg-4 col-md-6">
                        <div class="eom-card">
                            <div class="top">
                                @if($entry->photoUrl())
                                    <img src="{{ $entry->photoUrl() }}" alt="{{ $entry->name }}" class="avatar">
                                @else
                                    <div class="avatar placeholder">{{ $entry->initials() }}</div>
                                @endif
                                <div>
                                    <p class="name">{{ $entry->name }}</p>
                                    <p class="desig">{{ $entry->designation }}</p>
                                </div>
                            </div>
                            <div class="body">
                                <p class="quote">{{ $entry->message }}</p>
                            </div>
                            <div class="foot">
                                <span class="date">{{ optional($entry->created_at)->format('d M Y') ?: '—' }}</span>
                                @if($isAdmin)
                                    <div class="d-inline-flex gap-1">
                                        <button type="button"
                                                class="btn btn-sm btn-outline-primary edit-eom-btn"
                                                data-bs-toggle="modal"
                                                data-bs-target="#edit_eom"
                                                data-name="{{ $entry->name }}"
                                                data-designation="{{ $entry->designation }}"
                                                data-message="{{ $entry->message }}"
                                                data-photo="{{ $entry->photoUrl() }}"
                                                data-url="{{ route('eom.update', $entry) }}">
                                            <i class="fa-solid fa-pen"></i>
                                        </button>
                                        <form method="POST" action="{{ route('eom.destroy', $entry) }}" class="d-inline"
                                              onsubmit="return confirm('Delete this entry?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                <i class="fa-regular fa-trash-can"></i>
                                            </button>
                                        </form>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-12">
                        <div class="text-center text-muted py-5">
                            <div class="mb-2" style="font-size:2rem;opacity:.4;"><i class="fa-solid fa-trophy"></i></div>
                            No employee of the month entries yet.
                        </div>
                    </div>
                @endforelse
            </div>
            <div class="mt-3">{{ $entries->links() }}</div>
        </div>
    </div>
</div>

@if($isAdmin)
<div class="modal fade" id="add_eom" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <form method="POST" action="{{ route('eom.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Add Employee of the Month</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" required value="{{ old('name') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Designation <span class="text-danger">*</span></label>
                            <input type="text" name="designation" class="form-control" required value="{{ old('designation') }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Message <span class="text-danger">*</span></label>
                            <textarea name="message" class="form-control" rows="4" required>{{ old('message') }}</textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Photo</label>
                            <input type="file" name="image" class="form-control" accept="image/*">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button class="btn add-btn">Save Winner</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="edit_eom" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <form method="POST" id="edit_eom_form" action="#" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title">Edit Entry</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="edit_eom_name" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Designation <span class="text-danger">*</span></label>
                            <input type="text" name="designation" id="edit_eom_designation" class="form-control" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Message <span class="text-danger">*</span></label>
                            <textarea name="message" id="edit_eom_message" class="form-control" rows="4" required></textarea>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">Replace photo (optional)</label>
                            <input type="file" name="image" class="form-control" accept="image/*">
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <img id="edit_eom_photo" src="" alt="" class="img-fluid rounded border d-none" style="max-height:70px;">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button class="btn add-btn">Update</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
@endsection

@if($isAdmin)
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.edit-eom-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.getElementById('edit_eom_form').action = this.dataset.url;
            document.getElementById('edit_eom_name').value = this.dataset.name || '';
            document.getElementById('edit_eom_designation').value = this.dataset.designation || '';
            document.getElementById('edit_eom_message').value = this.dataset.message || '';
            const photo = document.getElementById('edit_eom_photo');
            if (this.dataset.photo) {
                photo.src = this.dataset.photo;
                photo.classList.remove('d-none');
            } else {
                photo.classList.add('d-none');
                photo.removeAttribute('src');
            }
        });
    });
});
</script>
@endpush
@endif
