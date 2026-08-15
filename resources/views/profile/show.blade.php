@extends('layouts.app')

@section('title', 'My Profile')
@section('heading', 'My Profile')

@section('page_actions')
<a href="{{ route('password.edit') }}" class="btn btn-outline-secondary me-1">
    <i class="fa-solid fa-key me-1"></i> Change password
</a>
<a href="{{ route('dashboard.employee') }}" class="btn btn-outline-secondary me-1">
    <i class="fa-solid fa-gauge-high me-1"></i> Dashboard
</a>
<button type="button" class="btn add-btn" id="focusSaveBtn">
    <i class="fa-solid fa-floppy-disk me-1"></i> Save profile
</button>
@endsection

@push('styles')
<style>
    .pf-wrap { --ink:#0f2744; --muted:#6b7c93; --line:#e8eef5; --soft:#f4f7fb; --accent:#ff9b44; }

    .pf-wrap .pf-hero {
        border:1px solid var(--line); border-radius:18px; overflow:hidden; margin-bottom:1rem;
        background:
            radial-gradient(900px 220px at 0% 0%, rgba(255,155,68,.18), transparent 55%),
            linear-gradient(135deg, #0f2744 0%, #16375f 55%, #1b466f 100%);
        color:#fff; padding:1.15rem 1.3rem;
        display:flex; justify-content:space-between; align-items:center; gap:1rem; flex-wrap:wrap;
    }
    .pf-wrap .pf-hero h2 { margin:0; font-size:1.25rem; font-weight:800; letter-spacing:-.02em; }
    .pf-wrap .pf-hero p { margin:.3rem 0 0; opacity:.85; font-size:.9rem; }
    .pf-wrap .pf-hero .chip {
        background:rgba(255,255,255,.12); border:1px solid rgba(255,255,255,.18);
        border-radius:12px; padding:.55rem .85rem; font-size:.82rem; font-weight:650;
    }

    .pf-wrap .pf-metric {
        border:1px solid var(--line); border-radius:16px; background:#fff;
        padding:1rem 1.05rem; height:100%; position:relative; overflow:hidden;
    }
    .pf-wrap .pf-metric::before {
        content:""; position:absolute; left:0; top:0; bottom:0; width:4px; background:var(--accent);
    }
    .pf-wrap .pf-metric.is-blue::before { background:#2563eb; }
    .pf-wrap .pf-metric.is-green::before { background:#16a34a; }
    .pf-wrap .pf-metric.is-purple::before { background:#7c3aed; }
    .pf-wrap .pf-metric .top { display:flex; justify-content:space-between; align-items:flex-start; gap:.75rem; }
    .pf-wrap .pf-metric .ico {
        width:36px; height:36px; border-radius:11px; display:inline-flex; align-items:center; justify-content:center;
        background:#fff7ed; color:#c2410c; flex:0 0 auto;
    }
    .pf-wrap .pf-metric.is-blue .ico { background:#eff6ff; color:#1d4ed8; }
    .pf-wrap .pf-metric.is-green .ico { background:#f0fdf4; color:#15803d; }
    .pf-wrap .pf-metric.is-purple .ico { background:#f5f3ff; color:#6d28d9; }
    .pf-wrap .pf-metric .k {
        font-size:.68rem; text-transform:uppercase; letter-spacing:.04em; color:var(--muted); font-weight:700; margin:0;
    }
    .pf-wrap .pf-metric .v {
        font-size:1.05rem; font-weight:800; color:var(--ink); margin:.3rem 0 0; line-height:1.2;
        word-break:break-word;
    }

    .pf-wrap .pf-panel {
        background:#fff; border:1px solid var(--line); border-radius:18px; overflow:hidden; margin-bottom:1rem;
    }
    .pf-wrap .pf-panel-head {
        display:flex; justify-content:space-between; align-items:center; gap:1rem; flex-wrap:wrap;
        padding:1rem 1.15rem; border-bottom:1px solid var(--line);
        background:linear-gradient(180deg,#fff,#fafbfd);
    }
    .pf-wrap .pf-panel-head h5 { margin:0; font-weight:750; color:var(--ink); font-size:1rem; }
    .pf-wrap .pf-panel-head .sub { display:block; font-size:.78rem; color:var(--muted); margin-top:.15rem; }
    .pf-wrap .pf-panel-body { padding:1.15rem; }

    .pf-wrap .profile-strip {
        display:flex; gap:1.1rem; align-items:center; flex-wrap:wrap;
        padding:1.25rem 1.3rem;
        background:
            radial-gradient(500px 140px at 0% 0%, rgba(255,155,68,.14), transparent 60%),
            linear-gradient(180deg,#fff,#fafbfd);
        border-bottom:1px solid var(--line);
    }
    .pf-wrap .photo-frame { position:relative; width:112px; height:112px; flex-shrink:0; }
    .pf-wrap .photo-frame img {
        width:112px; height:112px; border-radius:50%; object-fit:cover;
        border:3px solid #ffe0c2; background:#fff7ed;
        box-shadow:0 10px 24px rgba(15,39,68,.12);
    }
    .pf-wrap .photo-btn {
        position:absolute; right:2px; bottom:2px; width:36px; height:36px; border-radius:50%;
        border:2px solid #fff; background:var(--accent); color:#fff;
        display:grid; place-items:center; cursor:pointer;
        box-shadow:0 4px 12px rgba(255,155,68,.35);
    }
    .pf-wrap .photo-btn:hover { filter:brightness(1.05); color:#fff; }
    .pf-wrap .photo-btn input { display:none; }
    .pf-wrap .strip-name { margin:0; font-weight:800; color:var(--ink); font-size:1.35rem; letter-spacing:-.02em; }
    .pf-wrap .strip-sub { margin:.25rem 0 0; color:var(--muted); font-size:.9rem; }
    .pf-wrap .pill-row { display:flex; flex-wrap:wrap; gap:.4rem; margin-top:.65rem; }
    .pf-wrap .pill {
        display:inline-flex; align-items:center; gap:.3rem; padding:.25rem .65rem; border-radius:999px;
        font-size:.72rem; font-weight:700; background:#fff7ed; color:#c2410c; border:1px solid #ffd7b0;
    }
    .pf-wrap .pill.muted { background:var(--soft); color:var(--ink); border-color:var(--line); }
    .pf-wrap .pill.ok { background:#ecfdf5; color:#047857; border-color:#a7f3d0; }

    .pf-wrap .form-label { font-size:.8rem; font-weight:650; color:var(--muted); margin-bottom:.3rem; }
    .pf-wrap .form-control, .pf-wrap .form-select {
        border-radius:11px; border-color:var(--line); padding:.55rem .75rem; color:var(--ink);
    }
    .pf-wrap .form-control:focus, .pf-wrap .form-select:focus {
        border-color:#ffd0a8; box-shadow:0 0 0 .2rem rgba(255,155,68,.15);
    }
    .pf-wrap .form-control:disabled {
        background:#f8fafc; color:var(--muted); opacity:1;
    }
    .pf-wrap .form-section {
        border:1px solid var(--line); border-radius:14px; padding:1rem 1.05rem; background:#fafbfd; margin-bottom:1rem;
    }
    .pf-wrap .form-section:last-of-type { margin-bottom:0; }
    .pf-wrap .form-section h6 {
        margin:0 0 .85rem; font-size:.82rem; font-weight:750; color:var(--ink);
        display:flex; align-items:center; gap:.4rem;
    }
    .pf-wrap .form-section h6 i { color:var(--accent); }

    .pf-wrap .btn-submit {
        background:linear-gradient(145deg,#ff9b44,#f07a1a); border:0; color:#fff;
        font-weight:750; border-radius:12px; padding:.65rem 1.15rem;
        box-shadow:0 8px 18px rgba(255,155,68,.28);
    }
    .pf-wrap .btn-submit:hover { filter:brightness(1.03); color:#fff; }
    .pf-wrap .btn-ghost {
        border:1px solid var(--line); color:var(--ink); background:#fff; font-weight:650;
        border-radius:10px; font-size:.85rem; padding:.55rem .9rem; text-decoration:none;
    }
    .pf-wrap .btn-ghost:hover { border-color:#ffd0a8; background:#fffaf5; color:var(--ink); }
    .pf-wrap .side-card {
        border:1px solid var(--line); border-radius:16px; background:#fff; padding:1.05rem 1.1rem; margin-bottom:1rem;
    }
    .pf-wrap .side-card h6 { margin:0 0 .4rem; font-weight:750; color:var(--ink); font-size:.92rem; }
    .pf-wrap .side-card p { margin:0; color:var(--muted); font-size:.84rem; line-height:1.45; }
    .pf-wrap .side-card a {
        display:inline-flex; align-items:center; gap:.35rem; margin-top:.65rem;
        font-size:.82rem; font-weight:700; color:#0b5cab; text-decoration:none;
    }
    .pf-wrap .side-card a:hover { text-decoration:underline; }
    .pf-wrap .upload-hint { font-size:.78rem; color:var(--muted); margin-top:.35rem; }
</style>
@endpush

@section('content')
@php
    $active = (int) ($user->status ?? 1) === 1;
    $role = trim((string) ($user->role ?: 'user'));
@endphp
<div class="pf-wrap">
    <div class="pf-hero">
        <div>
            <h2>My profile</h2>
            <p>Update your personal details, contact info, and profile photo.</p>
        </div>
        <div class="chip">
            <i class="fa-solid fa-user me-1"></i>
            {{ $user->emp_id ?: 'ID #'.$user->id }}
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-6 col-lg-3">
            <div class="pf-metric">
                <div class="top">
                    <div>
                        <p class="k">Role</p>
                        <p class="v" style="text-transform:capitalize;">{{ $role }}</p>
                    </div>
                    <span class="ico"><i class="fa-solid fa-shield-halved"></i></span>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="pf-metric is-blue">
                <div class="top">
                    <div>
                        <p class="k">Department</p>
                        <p class="v">{{ $user->department?->name ?: '—' }}</p>
                    </div>
                    <span class="ico"><i class="fa-solid fa-building"></i></span>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="pf-metric is-green">
                <div class="top">
                    <div>
                        <p class="k">Designation</p>
                        <p class="v">{{ $user->designation?->name ?: '—' }}</p>
                    </div>
                    <span class="ico"><i class="fa-solid fa-briefcase"></i></span>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="pf-metric is-purple">
                <div class="top">
                    <div>
                        <p class="k">Status</p>
                        <p class="v">{{ $active ? 'Active' : 'Inactive' }}</p>
                    </div>
                    <span class="ico"><i class="fa-solid fa-circle-check"></i></span>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="pf-panel" id="profilePanel">
                <div class="profile-strip">
                    <div class="photo-frame">
                        <img id="profilePreview" src="{{ $user->profile_image_url }}" alt="{{ $user->full_name }}"
                             onerror="this.src='{{ asset('assets/img/profiles/avatar-02.jpg') }}'">
                        <label class="photo-btn" title="Change photo">
                            <i class="fa-solid fa-camera"></i>
                            <input type="file" id="quickPhotoInput" accept="image/jpeg,image/png,image/webp,image/gif">
                        </label>
                    </div>
                    <div class="flex-grow-1" style="min-width:200px;">
                        <p class="strip-name">{{ $user->full_name }}</p>
                        <p class="strip-sub">
                            {{ $user->designation?->name ?: 'Employee' }}
                            @if($user->department?->name) · {{ $user->department->name }} @endif
                        </p>
                        <div class="pill-row">
                            <span class="pill {{ $active ? 'ok' : 'muted' }}">
                                <i class="fa-solid fa-circle" style="font-size:.4rem;"></i>
                                {{ $active ? 'Active' : 'Inactive' }}
                            </span>
                            <span class="pill muted" style="text-transform:capitalize;">{{ $role }}</span>
                            @if($user->email)
                                <span class="pill muted"><i class="fa-solid fa-envelope"></i> {{ $user->email }}</span>
                            @endif
                        </div>
                        <form method="POST" action="{{ route('profile.photo') }}" enctype="multipart/form-data"
                              id="quickPhotoForm" class="d-flex flex-wrap gap-2 align-items-center mt-3">
                            @csrf
                            <input type="file" name="image" id="quickPhotoHidden" class="d-none" accept="image/*" required>
                            <button type="submit" class="btn btn-sm add-btn" id="quickPhotoSubmit" disabled>
                                <i class="fa-solid fa-upload me-1"></i> Save photo
                            </button>
                            <span class="upload-hint mb-0">JPG, PNG, WEBP · max 4MB</span>
                        </form>
                    </div>
                </div>

                <div class="pf-panel-head">
                    <div>
                        <h5><i class="fa-solid fa-pen-to-square me-1" style="color:var(--accent)"></i> Edit details</h5>
                        <span class="sub">Keep your information up to date</span>
                    </div>
                </div>

                <div class="pf-panel-body">
                    <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data" id="profileForm">
                        @csrf
                        @method('PUT')

                        <div class="form-section">
                            <h6><i class="fa-solid fa-user"></i> Basic info</h6>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label">First name</label>
                                    <input type="text" name="fname" class="form-control" value="{{ old('fname', $user->fname) }}" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Last name</label>
                                    <input type="text" name="lname" class="form-control" value="{{ old('lname', $user->lname) }}">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Login email</label>
                                    <input type="email" class="form-control" value="{{ $user->email }}" disabled>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Date of birth</label>
                                    <input type="date" name="dob" class="form-control" value="{{ old('dob', optional($user->dob)->format('Y-m-d')) }}">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Blood group</label>
                                    <input type="text" name="bgroup" class="form-control" value="{{ old('bgroup', $user->bgroup) }}" placeholder="e.g. B+">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Nationality</label>
                                    <input type="text" name="nationality" class="form-control" value="{{ old('nationality', $user->nationality) }}">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Religion</label>
                                    <input type="text" name="religion" class="form-control" value="{{ old('religion', $user->religion) }}">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Department</label>
                                    <input type="text" class="form-control" value="{{ $user->department?->name ?? '—' }}" disabled>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Designation</label>
                                    <input type="text" class="form-control" value="{{ $user->designation?->name ?? '—' }}" disabled>
                                </div>
                            </div>
                        </div>

                        <div class="form-section">
                            <h6><i class="fa-solid fa-address-book"></i> Contact</h6>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label">Mobile 1</label>
                                    <input type="text" name="mobile1" class="form-control" value="{{ old('mobile1', $user->mobile1) }}">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Mobile 2</label>
                                    <input type="text" name="mobile2" class="form-control" value="{{ old('mobile2', $user->mobile2) }}">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Office email</label>
                                    <input type="email" name="office_email" class="form-control" value="{{ old('office_email', $user->office_email) }}">
                                </div>
                            </div>
                        </div>

                        <div class="form-section">
                            <h6><i class="fa-solid fa-location-dot"></i> Address</h6>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Current address</label>
                                    <textarea name="current_address" class="form-control" rows="3">{{ old('current_address', $user->current_address) }}</textarea>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Permanent address</label>
                                    <textarea name="permanent_address" class="form-control" rows="3">{{ old('permanent_address', $user->permanent_address) }}</textarea>
                                </div>
                            </div>
                        </div>

                        <div class="form-section">
                            <h6><i class="fa-solid fa-image"></i> Photo (optional)</h6>
                            <div class="row g-3">
                                <div class="col-md-8">
                                    <label class="form-label">Replace profile photo with form save</label>
                                    <input type="file" name="image" class="form-control" accept="image/*">
                                    <div class="upload-hint">Or use the camera button above for a quick photo-only update.</div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex flex-wrap gap-2 mt-3">
                            <button type="submit" class="btn btn-submit">
                                <i class="fa-solid fa-floppy-disk me-1"></i> Save changes
                            </button>
                            <a href="{{ route('password.edit') }}" class="btn btn-ghost">
                                <i class="fa-solid fa-key me-1"></i> Change password
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="side-card">
                <h6><i class="fa-solid fa-id-badge me-1" style="color:var(--accent)"></i> Employee ID</h6>
                <p>{{ $user->emp_id ?: '#'.$user->id }}</p>
            </div>
            <div class="side-card">
                <h6><i class="fa-solid fa-calendar-check me-1" style="color:var(--accent)"></i> Joining date</h6>
                <p>{{ optional($user->doj)->format('d M Y') ?: 'Not set' }}</p>
            </div>
            <div class="side-card">
                <h6><i class="fa-solid fa-key me-1" style="color:var(--accent)"></i> Security</h6>
                <p>Update your login password regularly to keep your account secure.</p>
                <a href="{{ route('password.edit') }}">Change password <i class="fa-solid fa-arrow-right"></i></a>
            </div>
            <div class="side-card">
                <h6><i class="fa-solid fa-circle-info me-1" style="color:var(--accent)"></i> Note</h6>
                <p>Department and designation are managed by HR/admin. Contact them if these need to change.</p>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const pick = document.getElementById('quickPhotoInput');
    const hidden = document.getElementById('quickPhotoHidden');
    const submit = document.getElementById('quickPhotoSubmit');
    const preview = document.getElementById('profilePreview');

    pick?.addEventListener('change', function () {
        const file = this.files?.[0];
        if (!file || !hidden) return;
        const dt = new DataTransfer();
        dt.items.add(file);
        hidden.files = dt.files;
        if (submit) submit.disabled = false;
        if (preview) preview.src = URL.createObjectURL(file);
    });

    document.getElementById('focusSaveBtn')?.addEventListener('click', function () {
        document.getElementById('profilePanel')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
        document.getElementById('profileForm')?.requestSubmit();
    });
})();
</script>
@endpush
