@extends('layouts.app')

@section('title', 'Internal Complaints Committee')
@section('heading', 'Internal Complaints Committee')

@section('page_actions')
<a href="{{ route('posh.guidelines') }}" class="btn btn-outline-secondary me-2">Guidelines</a>
<a href="{{ route('harassment.create') }}" class="btn btn-outline-secondary me-2">File Complaint</a>
@if($isAdmin)
<a href="#" class="btn add-btn" data-bs-toggle="modal" data-bs-target="#add_member">
    <i class="la la-plus-circle"></i> Add Member
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
    .posh-wrap .posh-hero p { margin:0; color:var(--muted); line-height:1.55; max-width:760px; }
    .posh-wrap .member-card {
        border:1px solid var(--line); border-radius:16px; background:var(--card); padding:1.1rem; height:100%;
        display:flex; gap:.9rem; align-items:flex-start;
        transition:transform .15s ease, box-shadow .15s ease;
    }
    .posh-wrap .member-card:hover { transform:translateY(-2px); box-shadow:0 10px 24px var(--shadow, rgba(15,39,68,.07)); }
    .posh-wrap .avatar {
        width:56px; height:56px; border-radius:14px; flex:0 0 56px;
        display:flex; align-items:center; justify-content:center;
        background:linear-gradient(135deg,#0f2744,#1f4a78); color:#fff; font-weight:800;
    }
    .posh-wrap .role {
        display:inline-flex; padding:.2rem .55rem; border-radius:999px; font-size:.7rem; font-weight:700;
        background:rgba(255,155,68,.14); color:#c2410c; border:1px solid rgba(255,155,68,.35); margin-bottom:.35rem;
    }
    .posh-wrap .name { margin:0; font-weight:800; color:var(--ink); }
    .posh-wrap .meta { margin:.2rem 0 0; color:var(--muted); font-size:.88rem; }
    .posh-wrap .form-label { font-size:.8rem; font-weight:600; color:var(--muted); }
    .posh-wrap .posh-panel {
        background:var(--card); border:1px solid var(--line); border-radius:18px; overflow:hidden; margin-bottom:1rem;
    }
    .posh-wrap .posh-panel-head {
        padding:1rem 1.15rem; border-bottom:1px solid var(--line); background:var(--soft);
    }
    .posh-wrap .posh-panel-head h5 { margin:0; font-weight:750; color:var(--ink); }
    .posh-wrap .posh-panel-body { padding:1.15rem; background:var(--card); }
    .posh-wrap .footer-note {
        border:1px solid var(--line); border-radius:16px; background:var(--card); padding:1.1rem 1.2rem;
        color:var(--body); line-height:1.55;
    }

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
            <div class="posh-badge"><i class="fa-solid fa-users"></i> ICC</div>
            <h2>{{ $settings->committee_title }}</h2>
            @if($settings->committee_intro)
                <p>{{ $settings->committee_intro }}</p>
            @endif
        </div>
    </div>

    @if($isAdmin)
    <div class="posh-panel">
        <div class="posh-panel-head"><h5>Edit committee page settings</h5></div>
        <div class="posh-panel-body">
            <form method="POST" action="{{ route('posh.settings') }}" class="row g-3">
                @csrf
                <div class="col-md-6">
                    <label class="form-label">Committee title</label>
                    <input type="text" name="committee_title" class="form-control" value="{{ $settings->committee_title }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Contact email</label>
                    <input type="email" name="contact_email" class="form-control" value="{{ $settings->contact_email }}">
                </div>
                <div class="col-12">
                    <label class="form-label">Intro</label>
                    <textarea name="committee_intro" class="form-control" rows="2">{{ $settings->committee_intro }}</textarea>
                </div>
                <div class="col-12">
                    <label class="form-label">Footer note</label>
                    <textarea name="committee_footer" class="form-control" rows="2">{{ $settings->committee_footer }}</textarea>
                </div>
                <div class="col-12">
                    <button class="btn add-btn btn-sm">Save Settings</button>
                </div>
            </form>
        </div>
    </div>
    @endif

    <div class="row g-3 mb-3">
        @forelse($members as $member)
            <div class="col-lg-6">
                <div class="member-card">
                    <div class="avatar">{{ $member->initials() }}</div>
                    <div class="flex-grow-1">
                        <div class="d-flex justify-content-between gap-2">
                            <span class="role">{{ $member->role_title }}</span>
                            @if($isAdmin)
                                <div class="d-inline-flex gap-1">
                                    <button type="button" class="btn btn-sm btn-outline-primary edit-member-btn"
                                            data-bs-toggle="modal" data-bs-target="#edit_member"
                                            data-url="{{ route('posh.members.update', $member) }}"
                                            data-role="{{ $member->role_title }}"
                                            data-name="{{ $member->name }}"
                                            data-phone="{{ $member->phone }}"
                                            data-email="{{ $member->email }}"
                                            data-notes="{{ $member->notes }}"
                                            data-order="{{ $member->sort_order }}">
                                        <i class="fa-solid fa-pen"></i>
                                    </button>
                                    <form method="POST" action="{{ route('posh.members.destroy', $member) }}"
                                          onsubmit="return confirm('Remove this member?');">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger"><i class="fa-regular fa-trash-can"></i></button>
                                    </form>
                                </div>
                            @endif
                        </div>
                        <p class="name">{{ $member->name }}</p>
                        @if($member->phone)
                            <p class="meta"><i class="fa-solid fa-phone me-1"></i>{{ $member->phone }}</p>
                        @endif
                        @if($member->email)
                            <p class="meta"><i class="fa-regular fa-envelope me-1"></i>
                                <a href="mailto:{{ $member->email }}">{{ $member->email }}</a>
                            </p>
                        @endif
                        @if($member->notes)
                            <p class="meta">{{ $member->notes }}</p>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="member-card justify-content-center text-muted">No committee members added yet.</div>
            </div>
        @endforelse
    </div>

    @if($settings->committee_footer)
        <div class="footer-note">{{ $settings->committee_footer }}</div>
    @endif
</div>

@if($isAdmin)
<div class="modal fade" id="add_member" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="{{ route('posh.members.store') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Add Committee Member</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Role <span class="text-danger">*</span></label>
                        <input type="text" name="role_title" class="form-control" required placeholder="Presiding Officer / Member / External Member">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Phone</label>
                        <input type="text" name="phone" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control">
                    </div>
                    <div class="mb-1">
                        <label class="form-label">Notes</label>
                        <input type="text" name="notes" class="form-control" placeholder="e.g. Faculty, MDI">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button class="btn add-btn">Save Member</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="edit_member" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" id="edit_member_form" action="#">
                @csrf @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title">Edit Member</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Role <span class="text-danger">*</span></label>
                        <input type="text" name="role_title" id="edit_role" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="edit_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Phone</label>
                        <input type="text" name="phone" id="edit_phone" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" id="edit_email" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <input type="text" name="notes" id="edit_notes" class="form-control">
                    </div>
                    <div class="mb-1">
                        <label class="form-label">Sort order</label>
                        <input type="number" name="sort_order" id="edit_member_order" class="form-control" min="0">
                    </div>
                    <input type="hidden" name="is_active" value="1">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button class="btn add-btn">Update Member</button>
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
document.querySelectorAll('.edit-member-btn').forEach(function (btn) {
    btn.addEventListener('click', function () {
        document.getElementById('edit_member_form').action = this.dataset.url;
        document.getElementById('edit_role').value = this.dataset.role || '';
        document.getElementById('edit_name').value = this.dataset.name || '';
        document.getElementById('edit_phone').value = this.dataset.phone || '';
        document.getElementById('edit_email').value = this.dataset.email || '';
        document.getElementById('edit_notes').value = this.dataset.notes || '';
        document.getElementById('edit_member_order').value = this.dataset.order || 0;
    });
});
</script>
@endpush
@endif
