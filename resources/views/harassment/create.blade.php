@extends('layouts.app')

@section('title', 'Harassment Complaint')
@section('heading', 'Harassment Complaint')

@section('page_actions')
<a href="{{ route('posh.guidelines') }}" class="btn btn-outline-secondary me-1">
    <i class="fa-solid fa-book me-1"></i> POSH guidelines
</a>
<a href="{{ route('posh.committee') }}" class="btn btn-outline-secondary me-1">
    <i class="fa-solid fa-users me-1"></i> ICC
</a>
@if(! empty($isAdmin))
<a href="{{ route('harassment.admin') }}" class="btn btn-outline-secondary me-1">
    <i class="fa-solid fa-clipboard-list me-1"></i> Manage
</a>
@endif
<button type="button" class="btn add-btn" id="focusFormBtn">
    <i class="fa-solid fa-plus me-1"></i> File complaint
</button>
@endsection

@push('styles')
<style>
    .hs-wrap { --ink:#0f2744; --muted:#6b7c93; --line:#e8eef5; --soft:#f4f7fb; --accent:#ff9b44; }

    .hs-wrap .hs-hero {
        border:1px solid var(--line); border-radius:18px; overflow:hidden; margin-bottom:1rem;
        background:
            radial-gradient(900px 220px at 0% 0%, rgba(255,155,68,.18), transparent 55%),
            linear-gradient(135deg, #0f2744 0%, #16375f 55%, #1b466f 100%);
        color:#fff; padding:1.15rem 1.3rem;
        display:flex; justify-content:space-between; align-items:center; gap:1rem; flex-wrap:wrap;
    }
    .hs-wrap .hs-hero h2 { margin:0; font-size:1.25rem; font-weight:800; letter-spacing:-.02em; }
    .hs-wrap .hs-hero p { margin:.3rem 0 0; opacity:.85; font-size:.9rem; max-width:520px; }
    .hs-wrap .hs-hero .chip {
        background:rgba(255,255,255,.12); border:1px solid rgba(255,255,255,.18);
        border-radius:12px; padding:.55rem .85rem; font-size:.82rem; font-weight:650;
    }

    .hs-wrap .hs-metric {
        border:1px solid var(--line); border-radius:16px; background:#fff;
        padding:1rem 1.05rem; height:100%; position:relative; overflow:hidden;
    }
    .hs-wrap .hs-metric::before {
        content:""; position:absolute; left:0; top:0; bottom:0; width:4px; background:var(--accent);
    }
    .hs-wrap .hs-metric.is-blue::before { background:#2563eb; }
    .hs-wrap .hs-metric.is-green::before { background:#16a34a; }
    .hs-wrap .hs-metric.is-amber::before { background:#f59e0b; }
    .hs-wrap .hs-metric .top { display:flex; justify-content:space-between; align-items:flex-start; gap:.75rem; }
    .hs-wrap .hs-metric .ico {
        width:36px; height:36px; border-radius:11px; display:inline-flex; align-items:center; justify-content:center;
        background:#fff7ed; color:#c2410c; flex:0 0 auto;
    }
    .hs-wrap .hs-metric.is-blue .ico { background:#eff6ff; color:#1d4ed8; }
    .hs-wrap .hs-metric.is-green .ico { background:#f0fdf4; color:#15803d; }
    .hs-wrap .hs-metric.is-amber .ico { background:#fffbeb; color:#b45309; }
    .hs-wrap .hs-metric .k {
        font-size:.68rem; text-transform:uppercase; letter-spacing:.04em; color:var(--muted); font-weight:700; margin:0;
    }
    .hs-wrap .hs-metric .v {
        font-size:1.15rem; font-weight:800; color:var(--ink); margin:.3rem 0 0; line-height:1.2;
        word-break:break-word;
    }
    .hs-wrap .hs-metric .hint { font-size:.75rem; color:var(--muted); margin:.25rem 0 0; }

    .hs-wrap .hs-note {
        border:1px solid #ffd7b0; border-radius:16px; background:linear-gradient(180deg,#fffaf5,#fff);
        padding:1rem 1.15rem; margin-bottom:1rem; display:flex; gap:.85rem; align-items:flex-start;
    }
    .hs-wrap .hs-note .ico {
        width:40px; height:40px; border-radius:12px; flex-shrink:0;
        display:grid; place-items:center; background:#fff7ed; color:#c2410c;
    }
    .hs-wrap .hs-note h6 { margin:0 0 .25rem; font-weight:750; color:var(--ink); font-size:.95rem; }
    .hs-wrap .hs-note p { margin:0; color:var(--muted); font-size:.86rem; line-height:1.45; }

    .hs-wrap .hs-panel {
        background:#fff; border:1px solid var(--line); border-radius:18px; overflow:hidden; margin-bottom:1rem;
    }
    .hs-wrap .hs-panel-head {
        display:flex; justify-content:space-between; align-items:center; gap:1rem; flex-wrap:wrap;
        padding:1rem 1.15rem; border-bottom:1px solid var(--line);
        background:linear-gradient(180deg,#fff,#fafbfd);
    }
    .hs-wrap .hs-panel-head h5 { margin:0; font-weight:750; color:var(--ink); font-size:1rem; }
    .hs-wrap .hs-panel-head .sub { display:block; font-size:.78rem; color:var(--muted); margin-top:.15rem; }
    .hs-wrap .hs-panel-body { padding:1.15rem; }

    .hs-wrap .form-label { font-size:.8rem; font-weight:650; color:var(--muted); margin-bottom:.3rem; }
    .hs-wrap .form-control, .hs-wrap .form-select {
        border-radius:11px; border-color:var(--line); padding:.55rem .75rem; color:var(--ink);
    }
    .hs-wrap .form-control:focus, .hs-wrap .form-select:focus {
        border-color:#ffd0a8; box-shadow:0 0 0 .2rem rgba(255,155,68,.15);
    }
    .hs-wrap .form-section {
        border:1px solid var(--line); border-radius:14px; padding:1rem 1.05rem; background:#fafbfd; margin-bottom:1rem;
    }
    .hs-wrap .form-section:last-of-type { margin-bottom:0; }
    .hs-wrap .form-section h6 {
        margin:0 0 .85rem; font-size:.82rem; font-weight:750; color:var(--ink);
        display:flex; align-items:center; gap:.4rem;
    }
    .hs-wrap .form-section h6 i { color:var(--accent); }

    .hs-wrap .profile-row {
        display:flex; gap:1rem; align-items:center; flex-wrap:wrap;
        padding:1rem 1.15rem; border-bottom:1px solid var(--line);
        background:linear-gradient(135deg, rgba(255,155,68,.08), rgba(15,39,68,.03));
    }
    .hs-wrap .avatar {
        width:52px; height:52px; border-radius:14px; flex-shrink:0;
        display:grid; place-items:center; font-weight:800; color:#fff; font-size:1.05rem;
        background:linear-gradient(145deg,#0f2744,#1b466f);
    }
    .hs-wrap .profile-row .name { margin:0; font-weight:800; color:var(--ink); font-size:1.05rem; }
    .hs-wrap .profile-row .meta { margin:.15rem 0 0; color:var(--muted); font-size:.84rem; }
    .hs-wrap .tag {
        display:inline-flex; padding:.22rem .55rem; border-radius:999px; font-size:.72rem; font-weight:700;
        background:#fff7ed; color:#c2410c; border:1px solid #ffd7b0;
    }

    .hs-wrap .declare-box {
        border:1px solid var(--line); border-radius:14px; padding:.9rem 1rem; background:#fff;
        display:flex; gap:.75rem; align-items:flex-start;
    }
    .hs-wrap .declare-box .form-check-input {
        width:1.15rem; height:1.15rem; margin-top:.15rem; border-color:#cbd5e1;
    }
    .hs-wrap .declare-box .form-check-input:checked {
        background-color:var(--accent); border-color:var(--accent);
    }
    .hs-wrap .declare-box label { color:var(--ink); font-size:.9rem; font-weight:600; line-height:1.4; }

    .hs-wrap .btn-submit {
        background:linear-gradient(145deg,#ff9b44,#f07a1a); border:0; color:#fff;
        font-weight:750; border-radius:12px; padding:.7rem 1.25rem;
        box-shadow:0 8px 18px rgba(255,155,68,.28);
    }
    .hs-wrap .btn-submit:hover { filter:brightness(1.03); color:#fff; }
    .hs-wrap .btn-ghost {
        border:1px solid var(--line); color:var(--ink); background:#fff; font-weight:650;
        border-radius:10px; font-size:.85rem; padding:.55rem .9rem; text-decoration:none;
    }
    .hs-wrap .btn-ghost:hover { border-color:#ffd0a8; background:#fffaf5; color:var(--ink); }

    .hs-wrap .side-card {
        border:1px solid var(--line); border-radius:16px; background:#fff; padding:1.05rem 1.1rem; margin-bottom:1rem;
    }
    .hs-wrap .side-card h6 { margin:0 0 .45rem; font-weight:750; color:var(--ink); font-size:.92rem; }
    .hs-wrap .side-card p { margin:0; color:var(--muted); font-size:.84rem; line-height:1.45; }
    .hs-wrap .side-card a {
        display:inline-flex; align-items:center; gap:.35rem; margin-top:.65rem;
        font-size:.82rem; font-weight:700; color:#0b5cab; text-decoration:none;
    }
    .hs-wrap .side-card a:hover { text-decoration:underline; }
</style>
@endpush

@section('content')
@php
    $initials = strtoupper(substr((string) ($user->fname ?? 'U'), 0, 1).substr((string) ($user->lname ?? ''), 0, 1));
@endphp
<div class="hs-wrap">
    <div class="hs-hero">
        <div>
            <h2>File a complaint</h2>
            <p>Submit a confidential harassment complaint under the POSH framework. Your submission is handled with care.</p>
        </div>
        <div class="chip">
            <i class="fa-solid fa-lock me-1"></i> Confidential
        </div>
    </div>

    <div class="hs-note">
        <div class="ico"><i class="fa-solid fa-shield-halved"></i></div>
        <div>
            <h6>Your privacy matters</h6>
            <p>
                Provide accurate details so the Internal Complaints Committee (ICC) can review the matter.
                False statements may have consequences. You can attach supporting evidence (max 5MB).
            </p>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-4">
            <div class="hs-metric">
                <div class="top">
                    <div>
                        <p class="k">Complainant</p>
                        <p class="v">{{ $user->full_name }}</p>
                    </div>
                    <span class="ico"><i class="fa-solid fa-user"></i></span>
                </div>
                <p class="hint">{{ $user->department?->name ?? '—' }} · {{ $user->designation?->name ?? '—' }}</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="hs-metric is-blue">
                <div class="top">
                    <div>
                        <p class="k">Your filings</p>
                        <p class="v">{{ $myCount ?? 0 }}</p>
                    </div>
                    <span class="ico"><i class="fa-solid fa-folder-open"></i></span>
                </div>
                <p class="hint">Previously submitted by you</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="hs-metric is-green">
                <div class="top">
                    <div>
                        <p class="k">Support</p>
                        <p class="v" style="font-size:1rem;">POSH / ICC</p>
                    </div>
                    <span class="ico"><i class="fa-solid fa-handshake"></i></span>
                </div>
                <p class="hint">Guidelines & committee contacts</p>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="hs-panel" id="complaintPanel">
                <div class="profile-row">
                    <div class="avatar">{{ $initials ?: 'U' }}</div>
                    <div class="flex-grow-1">
                        <p class="name">{{ $user->full_name }}</p>
                        <p class="meta">
                            {{ $user->department?->name ?? 'Department —' }}
                            · {{ $user->designation?->name ?? 'Designation —' }}
                        </p>
                    </div>
                    <span class="tag"><i class="fa-solid fa-user-lock me-1"></i> Logged-in identity</span>
                </div>

                <div class="hs-panel-head">
                    <div>
                        <h5><i class="fa-solid fa-file-signature me-1" style="color:var(--accent)"></i> Complaint form</h5>
                        <span class="sub">All required fields must be completed</span>
                    </div>
                </div>

                <div class="hs-panel-body">
                    <form method="POST" action="{{ route('harassment.store') }}" enctype="multipart/form-data" id="harassmentForm">
                        @csrf

                        <div class="form-section">
                            <h6><i class="fa-solid fa-address-card"></i> Your contact</h6>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Contact number / email <span class="text-danger">*</span></label>
                                    <input type="text" name="complainant_contact" class="form-control"
                                           value="{{ old('complainant_contact', $user->mobile1) }}" required>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Incident date <span class="text-danger">*</span></label>
                                    <input type="date" name="incident_date" class="form-control"
                                           value="{{ old('incident_date') }}" required>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Location <span class="text-danger">*</span></label>
                                    <input type="text" name="incident_location" class="form-control"
                                           value="{{ old('incident_location') }}" placeholder="Office / floor / city" required>
                                </div>
                            </div>
                        </div>

                        <div class="form-section">
                            <h6><i class="fa-solid fa-user-slash"></i> Alleged harasser</h6>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Select employee <span class="text-muted">(optional)</span></label>
                                    <x-employee-select
                                        name="alleged_harasser_id"
                                        :employees="$employees"
                                        :selected="old('alleged_harasser_id')"
                                        placeholder="Search employee (optional)…"
                                    />
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Harasser details <span class="text-danger">*</span></label>
                                    <input type="text" name="harasser_details" class="form-control"
                                           value="{{ old('harasser_details') }}"
                                           placeholder="Name, role, or other identifying details" required>
                                </div>
                            </div>
                        </div>

                        <div class="form-section">
                            <h6><i class="fa-solid fa-align-left"></i> Incident details</h6>
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label">What happened <span class="text-danger">*</span></label>
                                    <textarea name="incident_description" class="form-control js-rich-editor" rows="5"
                                              placeholder="Click to describe the incident clearly (date, place, what was said or done)…" required>{{ old('incident_description') }}</textarea>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Witness details <span class="text-muted">(optional)</span></label>
                                    <textarea name="witness_details" class="form-control" rows="2"
                                              placeholder="Names of witnesses, if any">{{ old('witness_details') }}</textarea>
                                </div>
                                <div class="col-md-8">
                                    <label class="form-label">Evidence <span class="text-muted">(optional, max 5MB)</span></label>
                                    <input type="file" name="evidence" class="form-control"
                                           accept=".pdf,.jpg,.jpeg,.png,.webp,.gif,.doc,.docx">
                                </div>
                            </div>
                        </div>

                        <div class="mt-3 mb-3">
                            <div class="declare-box">
                                <input class="form-check-input" type="checkbox" name="declaration" value="1" id="declaration" required @checked(old('declaration'))>
                                <label class="form-check-label" for="declaration">
                                    I declare that the information provided is true to the best of my knowledge.
                                </label>
                            </div>
                        </div>

                        <div class="d-flex flex-wrap gap-2">
                            <button type="submit" class="btn btn-submit">
                                <i class="fa-solid fa-paper-plane me-1"></i> Submit complaint
                            </button>
                            <a href="{{ route('posh.guidelines') }}" class="btn btn-ghost">Read guidelines</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="side-card">
                <h6><i class="fa-solid fa-book me-1" style="color:var(--accent)"></i> POSH guidelines</h6>
                <p>Understand definitions, prohibited conduct, and the complaint process before you file.</p>
                <a href="{{ route('posh.guidelines') }}">Open guidelines <i class="fa-solid fa-arrow-right"></i></a>
            </div>
            <div class="side-card">
                <h6><i class="fa-solid fa-users me-1" style="color:var(--accent)"></i> Internal committee</h6>
                <p>Find ICC members and contacts responsible for receiving and reviewing complaints.</p>
                <a href="{{ route('posh.committee') }}">View committee <i class="fa-solid fa-arrow-right"></i></a>
            </div>
            <div class="side-card">
                <h6><i class="fa-solid fa-circle-info me-1" style="color:var(--accent)"></i> What happens next</h6>
                <p>
                    After submission, authorized reviewers can access your complaint.
                    Keep evidence ready and note any follow-up communications from ICC.
                </p>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    document.getElementById('focusFormBtn')?.addEventListener('click', function () {
        document.getElementById('complaintPanel')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
        document.querySelector('#harassmentForm input[name="complainant_contact"]')?.focus();
    });
})();
</script>
@endpush
