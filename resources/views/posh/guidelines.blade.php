@extends('layouts.app')

@section('title', 'POSH Guidelines')
@section('heading', 'POSH Guidelines')

@section('page_actions')
<a href="{{ route('posh.committee') }}" class="btn btn-outline-secondary me-2">Committee</a>
<a href="{{ route('harassment.create') }}" class="btn btn-outline-secondary me-2">File Complaint</a>
@if($isAdmin)
<a href="#" class="btn add-btn" data-bs-toggle="modal" data-bs-target="#add_section">
    <i class="la la-plus-circle"></i> Add Section
</a>
@endif
@endsection

@push('styles')
<style>
    .posh-wrap { --ink:#0f2744; --muted:#6b7c93; --line:#e8eef5; --soft:#f4f7fb; --accent:#ff9b44; --card:#fff; --body:#334155; }
    .posh-wrap .posh-hero {
        border:1px solid var(--line); border-radius:18px; overflow:hidden; margin-bottom:1rem;
        background:linear-gradient(135deg, rgba(255,155,68,.12), var(--card));
    }
    .posh-wrap .posh-hero-inner { padding:1.5rem 1.35rem; }
    .posh-wrap .posh-badge {
        display:inline-flex; align-items:center; gap:.35rem; padding:.3rem .7rem; border-radius:999px;
        background:rgba(255,155,68,.14); color:#c2410c; border:1px solid rgba(255,155,68,.35); font-size:.72rem; font-weight:700;
        text-transform:uppercase; letter-spacing:.04em; margin-bottom:.65rem;
    }
    .posh-wrap .posh-hero h2 { margin:0 0 .5rem; color:var(--ink); font-weight:800; font-size:1.45rem; }
    .posh-wrap .posh-hero p { margin:0; color:var(--muted); line-height:1.55; max-width:720px; }
    .posh-wrap .posh-section {
        background:var(--card); border:1px solid var(--line); border-radius:16px; padding:1.15rem 1.2rem; margin-bottom:.85rem;
        position:relative;
    }
    .posh-wrap .posh-section h5 { margin:0 0 .55rem; color:var(--ink); font-weight:800; font-size:1.05rem; }
    .posh-wrap .posh-section .body { color:var(--body); line-height:1.65; white-space:pre-line; margin:0; }
    .posh-wrap .posh-actions { position:absolute; top:.75rem; right:.75rem; display:flex; gap:.35rem; }
    .posh-wrap .form-label { font-size:.8rem; font-weight:600; color:var(--muted); }
    .posh-wrap .posh-panel {
        background:var(--card); border:1px solid var(--line); border-radius:18px; overflow:hidden; margin-bottom:1rem;
    }
    .posh-wrap .posh-panel-head {
        padding:1rem 1.15rem; border-bottom:1px solid var(--line); background:var(--soft);
        display:flex; justify-content:space-between; gap:1rem; flex-wrap:wrap;
    }
    .posh-wrap .posh-panel-head h5 { margin:0; font-weight:750; color:var(--ink); }
    .posh-wrap .posh-panel-body { padding:1.15rem; background:var(--card); }

    html[data-theme="dark"] .posh-wrap {
        --card:#141b27; --body:#d5deea; --soft:#1a2232;
    }
    html[data-theme="dark-blue"] .posh-wrap {
        --card:#0c1a31; --body:#d7e4f7; --soft:#102240;
    }
    html[data-theme="light"] .posh-wrap {
        --card:#ffffff; --body:#334155; --soft:#f4f7fb;
    }
</style>
@endpush

@section('content')
<div class="posh-wrap">
    <div class="posh-hero">
        <div class="posh-hero-inner">
            <div class="posh-badge"><i class="fa-solid fa-shield-halved"></i> POSH Policy</div>
            <h2>{{ $settings->guidelines_title }}</h2>
            @if($settings->guidelines_intro)
                <p>{{ $settings->guidelines_intro }}</p>
            @endif
            @if($settings->contact_email)
                <p class="mt-2 mb-0"><strong style="color:var(--ink)">Contact:</strong>
                    <a href="mailto:{{ $settings->contact_email }}">{{ $settings->contact_email }}</a>
                </p>
            @endif
        </div>
    </div>

    @if($isAdmin)
    <div class="posh-panel">
        <div class="posh-panel-head">
            <h5>Edit page settings</h5>
            <span class="small text-muted">Admin only</span>
        </div>
        <div class="posh-panel-body">
            <form method="POST" action="{{ route('posh.settings') }}" class="row g-3">
                @csrf
                <div class="col-md-8">
                    <label class="form-label">Guidelines title</label>
                    <input type="text" name="guidelines_title" class="form-control" value="{{ $settings->guidelines_title }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Contact email</label>
                    <input type="email" name="contact_email" class="form-control" value="{{ $settings->contact_email }}">
                </div>
                <div class="col-12">
                    <label class="form-label">Intro text</label>
                    <textarea name="guidelines_intro" class="form-control" rows="3">{{ $settings->guidelines_intro }}</textarea>
                </div>
                <div class="col-12">
                    <button class="btn add-btn btn-sm">Save Settings</button>
                </div>
            </form>
        </div>
    </div>
    @endif

    @forelse($sections as $section)
        <div class="posh-section">
            @if($isAdmin)
                <div class="posh-actions">
                    <button type="button" class="btn btn-sm btn-outline-primary edit-section-btn"
                            data-bs-toggle="modal" data-bs-target="#edit_section"
                            data-url="{{ route('posh.sections.update', $section) }}"
                            data-heading="{{ $section->heading }}"
                            data-body="{{ $section->body }}"
                            data-order="{{ $section->sort_order }}">
                        <i class="fa-solid fa-pen"></i>
                    </button>
                    <form method="POST" action="{{ route('posh.sections.destroy', $section) }}" class="d-inline"
                          onsubmit="return confirm('Delete this section?');">
                        @csrf @method('DELETE')
                        <button class="btn btn-sm btn-outline-danger"><i class="fa-regular fa-trash-can"></i></button>
                    </form>
                </div>
            @endif
            <h5>{{ $section->heading }}</h5>
            <p class="body">{{ $section->body }}</p>
        </div>
    @empty
        <div class="posh-section text-muted text-center">No guideline sections yet.</div>
    @endforelse
</div>

@if($isAdmin)
<div class="modal fade" id="add_section" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <form method="POST" action="{{ route('posh.sections.store') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Add Section</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Heading <span class="text-danger">*</span></label>
                        <input type="text" name="heading" class="form-control" required>
                    </div>
                    <div class="mb-1">
                        <label class="form-label">Body <span class="text-danger">*</span></label>
                        <textarea name="body" class="form-control" rows="6" required placeholder="Use new lines. Bullet points can start with •"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button class="btn add-btn">Save Section</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="edit_section" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <form method="POST" id="edit_section_form" action="#">
                @csrf @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title">Edit Section</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Heading <span class="text-danger">*</span></label>
                        <input type="text" name="heading" id="edit_heading" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Sort order</label>
                        <input type="number" name="sort_order" id="edit_order" class="form-control" min="0">
                    </div>
                    <div class="mb-1">
                        <label class="form-label">Body <span class="text-danger">*</span></label>
                        <textarea name="body" id="edit_body" class="form-control" rows="6" required></textarea>
                    </div>
                    <input type="hidden" name="is_active" value="1">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button class="btn add-btn">Update Section</button>
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
document.querySelectorAll('.edit-section-btn').forEach(function (btn) {
    btn.addEventListener('click', function () {
        document.getElementById('edit_section_form').action = this.dataset.url;
        document.getElementById('edit_heading').value = this.dataset.heading || '';
        document.getElementById('edit_body').value = this.dataset.body || '';
        document.getElementById('edit_order').value = this.dataset.order || 0;
    });
});
</script>
@endpush
@endif
