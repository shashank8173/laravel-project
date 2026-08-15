@extends('layouts.app')

@section('title', 'Company Details')
@section('heading', 'Company Details')

@section('page_actions')
@if($isAdmin)
    @if(! $company)
        <a href="{{ route('companies.index', ['add' => 1]) }}" class="btn add-btn">
            <i class="fa-solid fa-plus me-1"></i> Add Company
        </a>
    @elseif(! $editing)
        <a href="{{ route('companies.index', ['edit' => 1]) }}" class="btn add-btn">
            <i class="fa-solid fa-pen me-1"></i> Edit Company
        </a>
    @else
        <a href="{{ route('companies.index') }}" class="btn btn-outline-secondary">Cancel</a>
    @endif
@endif
@endsection

@push('styles')
<style>
    .co-wrap { --ink:#0f2744; --muted:#6b7c93; --line:#e8eef5; --soft:#f4f7fb; --accent:#ff9b44; }

    .co-wrap .co-hero {
        border:1px solid var(--line); border-radius:18px; overflow:hidden; margin-bottom:1rem;
        background:
            radial-gradient(900px 220px at 0% 0%, rgba(255,155,68,.18), transparent 55%),
            linear-gradient(135deg, #0f2744 0%, #16375f 55%, #1b466f 100%);
        color:#fff; padding:1.15rem 1.3rem;
        display:flex; justify-content:space-between; align-items:center; gap:1rem; flex-wrap:wrap;
    }
    .co-wrap .co-hero h2 { margin:0; font-size:1.25rem; font-weight:800; letter-spacing:-.02em; }
    .co-wrap .co-hero p { margin:.3rem 0 0; opacity:.85; font-size:.9rem; }
    .co-wrap .co-hero .chip {
        background:rgba(255,255,255,.12); border:1px solid rgba(255,255,255,.18);
        border-radius:12px; padding:.55rem .85rem; font-size:.82rem; font-weight:650;
    }

    .co-wrap .co-metric {
        border:1px solid var(--line); border-radius:16px; background:#fff;
        padding:1rem 1.05rem; height:100%; position:relative; overflow:hidden;
    }
    .co-wrap .co-metric::before {
        content:""; position:absolute; left:0; top:0; bottom:0; width:4px; background:var(--accent);
    }
    .co-wrap .co-metric.is-blue::before { background:#2563eb; }
    .co-wrap .co-metric.is-green::before { background:#16a34a; }
    .co-wrap .co-metric.is-amber::before { background:#f59e0b; }
    .co-wrap .co-metric.is-purple::before { background:#7c3aed; }
    .co-wrap .co-metric .top { display:flex; justify-content:space-between; align-items:flex-start; gap:.75rem; }
    .co-wrap .co-metric .ico {
        width:36px; height:36px; border-radius:11px; display:inline-flex; align-items:center; justify-content:center;
        background:#fff7ed; color:#c2410c; flex:0 0 auto;
    }
    .co-wrap .co-metric.is-blue .ico { background:#eff6ff; color:#1d4ed8; }
    .co-wrap .co-metric.is-green .ico { background:#f0fdf4; color:#15803d; }
    .co-wrap .co-metric.is-amber .ico { background:#fffbeb; color:#b45309; }
    .co-wrap .co-metric.is-purple .ico { background:#f5f3ff; color:#6d28d9; }
    .co-wrap .co-metric .k {
        font-size:.68rem; text-transform:uppercase; letter-spacing:.04em; color:var(--muted); font-weight:700; margin:0;
    }
    .co-wrap .co-metric .v {
        font-size:1.25rem; font-weight:800; color:var(--ink); margin:.3rem 0 0; line-height:1.15;
        word-break:break-word;
    }
    .co-wrap .co-metric .hint { font-size:.75rem; color:var(--muted); margin:.25rem 0 0; }

    .co-wrap .co-panel {
        background:#fff; border:1px solid var(--line); border-radius:18px; overflow:hidden; margin-bottom:1rem;
    }
    .co-wrap .co-panel-head {
        display:flex; align-items:center; justify-content:space-between; gap:1rem; flex-wrap:wrap;
        padding:1rem 1.15rem; border-bottom:1px solid var(--line);
        background:linear-gradient(180deg,#fff,#fafbfd);
    }
    .co-wrap .co-panel-head h5 { margin:0; font-weight:750; color:var(--ink); font-size:1rem; }
    .co-wrap .co-panel-head .sub { display:block; font-size:.78rem; color:var(--muted); margin-top:.15rem; }
    .co-wrap .co-panel-body { padding:1.15rem; }

    .co-wrap .co-cover {
        position:relative; height:200px; background:
            radial-gradient(700px 180px at 10% 20%, rgba(255,155,68,.25), transparent 60%),
            linear-gradient(135deg, #0f2744 0%, #1b466f 100%);
        background-size:cover; background-position:center;
    }
    .co-wrap .co-cover::after {
        content:""; position:absolute; inset:0;
        background:linear-gradient(180deg, transparent 40%, rgba(15,39,68,.55) 100%);
    }
    .co-wrap .co-profile {
        display:flex; gap:1rem; align-items:flex-end; padding:0 1.25rem 1.15rem;
        margin-top:-48px; position:relative; z-index:1; flex-wrap:wrap;
    }
    .co-wrap .co-logo {
        width:92px; height:92px; border-radius:20px; background:#fff; border:4px solid #fff;
        box-shadow:0 10px 24px rgba(15,39,68,.14); object-fit:contain; padding:8px; flex-shrink:0;
    }
    .co-wrap .co-logo.placeholder {
        display:flex; align-items:center; justify-content:center; color:var(--ink); font-weight:800; font-size:1.35rem;
        background:linear-gradient(145deg,#fff7ed,#fff);
    }
    .co-wrap .co-title { flex:1 1 200px; min-width:0; padding-bottom:.2rem; }
    .co-wrap .co-title h3 {
        margin:0; color:#fff; font-weight:800; font-size:1.45rem; letter-spacing:-.02em;
        text-shadow:0 2px 12px rgba(0,0,0,.35);
    }
    .co-wrap .co-title .sub { color:rgba(255,255,255,.88); font-size:.9rem; margin-top:.2rem; }
    .co-wrap .co-title-meta { display:flex; flex-wrap:wrap; gap:.4rem; margin-top:.55rem; }

    .co-wrap .co-chip {
        display:inline-flex; align-items:center; gap:.35rem; padding:.28rem .7rem; border-radius:999px;
        font-size:.74rem; font-weight:700; background:#fff7ed; color:#c2410c; border:1px solid #ffd7b0;
    }
    .co-wrap .co-chip.ok { background:#ecfdf5; color:#047857; border-color:#a7f3d0; }
    .co-wrap .co-chip.muted { background:#f1f5f9; color:#475569; border-color:#e2e8f0; }
    .co-wrap .co-chip.on-dark {
        background:rgba(255,255,255,.16); color:#fff; border-color:rgba(255,255,255,.22);
    }

    .co-wrap .co-grid {
        display:grid; grid-template-columns:repeat(2, minmax(0,1fr)); gap:.95rem 1.35rem;
    }
    @media (max-width:767px){
        .co-wrap .co-grid { grid-template-columns:1fr; }
        .co-wrap .co-cover { height:160px; }
        .co-wrap .co-title h3 { font-size:1.2rem; }
    }
    .co-wrap .co-item {
        display:flex; gap:.75rem; align-items:flex-start;
        padding:.85rem .95rem; border:1px solid var(--line); border-radius:14px; background:#fafbfd;
    }
    .co-wrap .co-item .ico {
        width:36px; height:36px; border-radius:11px; flex-shrink:0;
        display:inline-flex; align-items:center; justify-content:center;
        background:#fff7ed; color:#c2410c;
    }
    .co-wrap .co-item .k {
        font-size:.68rem; text-transform:uppercase; letter-spacing:.04em; color:var(--muted); font-weight:700; margin:0 0 .15rem;
    }
    .co-wrap .co-item .v {
        color:var(--ink); font-weight:650; margin:0; word-break:break-word; font-size:.92rem; line-height:1.35;
    }
    .co-wrap .co-item .v a { color:#0b5cab; text-decoration:none; font-weight:700; }
    .co-wrap .co-item .v a:hover { text-decoration:underline; }

    .co-wrap .co-desc {
        color:var(--ink); white-space:pre-line; margin:0; line-height:1.6; font-size:.94rem;
    }
    .co-wrap .co-map iframe {
        width:100% !important; height:260px !important; border:0; border-radius:14px;
    }

    .co-wrap .social-row { display:flex; flex-wrap:wrap; gap:.5rem; }
    .co-wrap .social-btn {
        display:inline-flex; align-items:center; gap:.45rem; padding:.45rem .85rem;
        border-radius:11px; border:1px solid var(--line); background:#fff; color:var(--ink);
        text-decoration:none; font-size:.82rem; font-weight:700;
        transition: border-color .15s ease, background .15s ease, transform .15s ease;
    }
    .co-wrap .social-btn:hover {
        border-color:#ffd0a8; background:#fffaf5; color:var(--ink); transform:translateY(-1px);
    }
    .co-wrap .social-btn i { color:var(--accent); }

    .co-wrap .form-label { font-size:.8rem; font-weight:650; color:var(--muted); margin-bottom:.3rem; }
    .co-wrap .form-control, .co-wrap .form-select {
        border-radius:11px; border-color:var(--line); padding:.55rem .75rem; color:var(--ink);
    }
    .co-wrap .form-control:focus, .co-wrap .form-select:focus {
        border-color:#ffd0a8; box-shadow:0 0 0 .2rem rgba(255,155,68,.15);
    }
    .co-wrap .form-section {
        border:1px solid var(--line); border-radius:14px; padding:1rem 1.05rem; background:#fafbfd; margin-bottom:1rem;
    }
    .co-wrap .form-section h6 {
        margin:0 0 .85rem; font-size:.82rem; font-weight:750; color:var(--ink);
        display:flex; align-items:center; gap:.4rem;
    }
    .co-wrap .form-section h6 i { color:var(--accent); }
    .co-wrap .preview-box {
        border:1px dashed #d5deea; border-radius:12px; background:#fff;
        padding:.75rem; min-height:96px; display:flex; align-items:center; justify-content:center;
    }
    .co-wrap .preview-box img { max-height:88px; max-width:100%; object-fit:contain; border-radius:8px; }
    .co-wrap .btn-submit {
        background:linear-gradient(145deg,#ff9b44,#f07a1a); border:0; color:#fff;
        font-weight:750; border-radius:12px; padding:.65rem 1.15rem;
        box-shadow:0 8px 18px rgba(255,155,68,.28);
    }
    .co-wrap .btn-submit:hover { filter:brightness(1.03); color:#fff; }

    .co-wrap .co-empty {
        text-align:center; padding:3.25rem 1.25rem;
    }
    .co-wrap .co-empty .ico {
        width:72px; height:72px; margin:0 auto .9rem; border-radius:18px;
        display:grid; place-items:center; font-size:1.75rem;
        background:linear-gradient(145deg,#fff7ed,#ffe8d1); color:#c2410c;
    }
    .co-wrap .co-empty h5 { font-weight:800; color:var(--ink); margin-bottom:.35rem; }
    .co-wrap .co-empty p { color:var(--muted); margin-bottom:1.1rem; max-width:380px; margin-left:auto; margin-right:auto; }
</style>
@endpush

@section('content')
@php
    $c = $company;
    $val = fn ($key, $default = '') => old($key, $c->{$key} ?? $default);
    $hasSocial = $c && ($c->linkedin || $c->facebook || $c->twitter || $c->website);
@endphp
<div class="co-wrap">

@if($editing && $isAdmin)
    <div class="co-hero">
        <div>
            <h2>{{ $c ? 'Edit company profile' : 'Add company profile' }}</h2>
            <p>One company record for the organization — keep contact and branding up to date.</p>
        </div>
        <div class="chip">
            <i class="fa-solid fa-building me-1"></i>
            {{ $c ? 'Updating existing' : 'Creating new' }}
        </div>
    </div>

    <div class="co-panel">
        <div class="co-panel-head">
            <div>
                <h5><i class="fa-solid fa-pen-to-square me-1" style="color:var(--accent)"></i> Company details</h5>
                <span class="sub">Required fields are marked with *</span>
            </div>
        </div>
        <div class="co-panel-body">
            <form method="POST"
                  action="{{ $c ? route('companies.update', $c) : route('companies.store') }}"
                  enctype="multipart/form-data">
                @csrf
                @if($c) @method('PUT') @endif

                <div class="form-section">
                    <h6><i class="fa-solid fa-id-card"></i> Basic info</h6>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Company name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" required value="{{ $val('name') }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control" required value="{{ $val('email') }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Status <span class="text-danger">*</span></label>
                            <select name="status" class="form-select" required>
                                <option value="active" @selected($val('status', 'active') === 'active')>Active</option>
                                <option value="inactive" @selected($val('status') === 'inactive')>Inactive</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Industry</label>
                            <input type="text" name="industry" class="form-control" value="{{ $val('industry') }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Tax ID</label>
                            <input type="text" name="tax_id" class="form-control" value="{{ $val('tax_id') }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Parent company</label>
                            <input type="text" name="parent_company" class="form-control" value="{{ $val('parent_company') }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control js-rich-editor" rows="4"
                                      placeholder="Click to write company description…">{{ $val('description') }}</textarea>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h6><i class="fa-solid fa-address-book"></i> Contact & address</h6>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Mobile 1</label>
                            <input type="text" name="mobile1" class="form-control" value="{{ $val('mobile1') }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Mobile 2</label>
                            <input type="text" name="mobile2" class="form-control" value="{{ $val('mobile2') }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Additional contact</label>
                            <input type="text" name="additional_contact" class="form-control" value="{{ $val('additional_contact') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Address line 1</label>
                            <input type="text" name="address1" class="form-control" value="{{ $val('address1') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Address line 2</label>
                            <input type="text" name="address2" class="form-control" value="{{ $val('address2') }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Website</label>
                            <input type="url" name="website" class="form-control" value="{{ $val('website') }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Operating hours</label>
                            <input type="text" name="operating_hours" class="form-control" value="{{ $val('operating_hours') }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Latitude</label>
                            <input type="text" name="latitude" class="form-control" value="{{ $val('latitude') }}" placeholder="28.6139,77.2090">
                            <div class="form-text">For punch geofence use <code>lat,lng</code>. Map embed stays in Longitude.</div>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h6><i class="fa-solid fa-chart-line"></i> Business & social</h6>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Founded year</label>
                            <input type="number" name="founded_year" class="form-control" value="{{ $val('founded_year') }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Employee count</label>
                            <input type="number" name="employee_count" class="form-control" value="{{ $val('employee_count') }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">LinkedIn</label>
                            <input type="url" name="linkedin" class="form-control" value="{{ $val('linkedin') }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Facebook</label>
                            <input type="url" name="facebook" class="form-control" value="{{ $val('facebook') }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Twitter</label>
                            <input type="url" name="twitter" class="form-control" value="{{ $val('twitter') }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Google Maps embed code</label>
                            <textarea name="longitude" class="form-control" rows="3" placeholder="<iframe ...></iframe>">{{ $val('longitude') }}</textarea>
                        </div>
                    </div>
                </div>

                <div class="form-section mb-0">
                    <h6><i class="fa-solid fa-image"></i> Branding</h6>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Logo</label>
                            <input type="file" name="logo" class="form-control" accept="image/*">
                            <input type="text" name="logo_alt_text" class="form-control mt-2" value="{{ $val('logo_alt_text') }}" placeholder="Logo alt text">
                            <div class="preview-box mt-2">
                                @if($c?->logoUrl())
                                    <img src="{{ $c->logoUrl() }}" alt="{{ $c->logo_alt_text ?: 'Logo' }}">
                                @else
                                    <span class="text-muted small">No logo uploaded</span>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Banner</label>
                            <input type="file" name="banner" class="form-control" accept="image/*">
                            <input type="text" name="banner_alt_text" class="form-control mt-2" value="{{ $val('banner_alt_text') }}" placeholder="Banner alt text">
                            <div class="preview-box mt-2">
                                @if($c?->bannerUrl())
                                    <img src="{{ $c->bannerUrl() }}" alt="{{ $c->banner_alt_text ?: 'Banner' }}">
                                @else
                                    <span class="text-muted small">No banner uploaded</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-4 d-flex flex-wrap gap-2">
                    <button type="submit" class="btn btn-submit">
                        <i class="fa-solid fa-floppy-disk me-1"></i>
                        {{ $c ? 'Save changes' : 'Add company' }}
                    </button>
                    <a href="{{ route('companies.index') }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>

@elseif($c)
    <div class="co-hero">
        <div>
            <h2>Company profile</h2>
            <p>Organization branding, contact details, and location in one place.</p>
        </div>
        <div class="d-flex flex-wrap gap-2 align-items-center">
            <span class="chip">
                <i class="fa-solid fa-{{ $c->status === 'active' ? 'circle-check' : 'circle-xmark' }} me-1"></i>
                {{ ucfirst($c->status ?: 'Unknown') }}
            </span>
            @if($isAdmin)
                <a href="{{ route('companies.index', ['edit' => 1]) }}" class="btn add-btn btn-sm">
                    <i class="fa-solid fa-pen me-1"></i> Edit
                </a>
            @endif
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-6 col-lg-3">
            <div class="co-metric is-green">
                <div class="top">
                    <div>
                        <p class="k">Status</p>
                        <p class="v" style="font-size:1.1rem;">{{ ucfirst($c->status ?: '—') }}</p>
                    </div>
                    <span class="ico"><i class="fa-solid fa-shield-halved"></i></span>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="co-metric is-blue">
                <div class="top">
                    <div>
                        <p class="k">Employees</p>
                        <p class="v">{{ $c->employee_count ?: '—' }}</p>
                    </div>
                    <span class="ico"><i class="fa-solid fa-users"></i></span>
                </div>
                <div class="hint">Headcount on record</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="co-metric is-amber">
                <div class="top">
                    <div>
                        <p class="k">Founded</p>
                        <p class="v">{{ $c->founded_year ?: '—' }}</p>
                    </div>
                    <span class="ico"><i class="fa-solid fa-flag"></i></span>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="co-metric is-purple">
                <div class="top">
                    <div>
                        <p class="k">Industry</p>
                        <p class="v" style="font-size:1rem;">{{ $c->industry ?: '—' }}</p>
                    </div>
                    <span class="ico"><i class="fa-solid fa-briefcase"></i></span>
                </div>
            </div>
        </div>
    </div>

    <div class="co-panel">
        <div class="co-cover" @if($c->bannerUrl()) style="background-image:url('{{ $c->bannerUrl() }}')" @endif></div>
        <div class="co-profile">
            @if($c->logoUrl())
                <img src="{{ $c->logoUrl() }}" alt="{{ $c->logo_alt_text ?: $c->name }}" class="co-logo">
            @else
                <div class="co-logo placeholder">{{ strtoupper(substr($c->name, 0, 2)) }}</div>
            @endif
            <div class="co-title">
                <h3>{{ $c->name }}</h3>
                <div class="sub">{{ $c->industry ?: 'Company profile' }}@if($c->operating_hours) · {{ $c->operating_hours }}@endif</div>
                <div class="co-title-meta">
                    <span class="co-chip on-dark {{ $c->status === 'active' ? '' : '' }}">
                        <i class="fa-solid fa-circle" style="font-size:.45rem;"></i>
                        {{ ucfirst($c->status ?: '—') }}
                    </span>
                    @if($c->tax_id)
                        <span class="co-chip on-dark">Tax · {{ $c->tax_id }}</span>
                    @endif
                    @if($c->parent_company)
                        <span class="co-chip on-dark">Parent · {{ $c->parent_company }}</span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-7">
            <div class="co-panel">
                <div class="co-panel-head">
                    <div>
                        <h5><i class="fa-solid fa-address-card me-1" style="color:var(--accent)"></i> Contact & address</h5>
                        <span class="sub">How people reach the company</span>
                    </div>
                </div>
                <div class="co-panel-body">
                    <div class="co-grid">
                        <div class="co-item">
                            <span class="ico"><i class="fa-solid fa-envelope"></i></span>
                            <div>
                                <p class="k">Email</p>
                                <p class="v">
                                    @if($c->email)
                                        <a href="mailto:{{ $c->email }}">{{ $c->email }}</a>
                                    @else — @endif
                                </p>
                            </div>
                        </div>
                        <div class="co-item">
                            <span class="ico"><i class="fa-solid fa-globe"></i></span>
                            <div>
                                <p class="k">Website</p>
                                <p class="v">
                                    @if($c->website)
                                        <a href="{{ $c->website }}" target="_blank" rel="noopener">{{ $c->website }}</a>
                                    @else — @endif
                                </p>
                            </div>
                        </div>
                        <div class="co-item">
                            <span class="ico"><i class="fa-solid fa-phone"></i></span>
                            <div>
                                <p class="k">Mobile 1</p>
                                <p class="v">{{ $c->mobile1 ?: '—' }}</p>
                            </div>
                        </div>
                        <div class="co-item">
                            <span class="ico"><i class="fa-solid fa-phone-flip"></i></span>
                            <div>
                                <p class="k">Mobile 2</p>
                                <p class="v">{{ $c->mobile2 ?: '—' }}</p>
                            </div>
                        </div>
                        <div class="co-item">
                            <span class="ico"><i class="fa-solid fa-location-dot"></i></span>
                            <div>
                                <p class="k">Address 1</p>
                                <p class="v">{{ $c->address1 ?: '—' }}</p>
                            </div>
                        </div>
                        <div class="co-item">
                            <span class="ico"><i class="fa-solid fa-map"></i></span>
                            <div>
                                <p class="k">Address 2</p>
                                <p class="v">{{ $c->address2 ?: '—' }}</p>
                            </div>
                        </div>
                        <div class="co-item">
                            <span class="ico"><i class="fa-solid fa-user-plus"></i></span>
                            <div>
                                <p class="k">Additional contact</p>
                                <p class="v">{{ $c->additional_contact ?: '—' }}</p>
                            </div>
                        </div>
                        <div class="co-item">
                            <span class="ico"><i class="fa-solid fa-compass"></i></span>
                            <div>
                                <p class="k">Latitude</p>
                                <p class="v">{{ $c->latitude ?: '—' }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="co-panel">
                <div class="co-panel-head">
                    <div>
                        <h5><i class="fa-solid fa-align-left me-1" style="color:var(--accent)"></i> About</h5>
                        <span class="sub">Company description</span>
                    </div>
                </div>
                <div class="co-panel-body">
                    <div class="co-desc hrm-rich-content">{!! $c->description ?: '<em>No description added yet.</em>' !!}</div>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="co-panel">
                <div class="co-panel-head">
                    <div>
                        <h5><i class="fa-solid fa-building me-1" style="color:var(--accent)"></i> Business info</h5>
                        <span class="sub">Structure and operations</span>
                    </div>
                </div>
                <div class="co-panel-body">
                    <div class="co-grid" style="grid-template-columns:1fr;">
                        <div class="co-item">
                            <span class="ico"><i class="fa-solid fa-industry"></i></span>
                            <div>
                                <p class="k">Industry</p>
                                <p class="v">{{ $c->industry ?: '—' }}</p>
                            </div>
                        </div>
                        <div class="co-item">
                            <span class="ico"><i class="fa-solid fa-file-invoice"></i></span>
                            <div>
                                <p class="k">Tax ID</p>
                                <p class="v">{{ $c->tax_id ?: '—' }}</p>
                            </div>
                        </div>
                        <div class="co-item">
                            <span class="ico"><i class="fa-solid fa-sitemap"></i></span>
                            <div>
                                <p class="k">Parent company</p>
                                <p class="v">{{ $c->parent_company ?: '—' }}</p>
                            </div>
                        </div>
                        <div class="co-item">
                            <span class="ico"><i class="fa-solid fa-clock"></i></span>
                            <div>
                                <p class="k">Operating hours</p>
                                <p class="v">{{ $c->operating_hours ?: '—' }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            @if($hasSocial)
            <div class="co-panel">
                <div class="co-panel-head">
                    <div>
                        <h5><i class="fa-solid fa-share-nodes me-1" style="color:var(--accent)"></i> Links</h5>
                        <span class="sub">Website & social profiles</span>
                    </div>
                </div>
                <div class="co-panel-body">
                    <div class="social-row">
                        @if($c->website)
                            <a class="social-btn" href="{{ $c->website }}" target="_blank" rel="noopener">
                                <i class="fa-solid fa-globe"></i> Website
                            </a>
                        @endif
                        @if($c->linkedin)
                            <a class="social-btn" href="{{ $c->linkedin }}" target="_blank" rel="noopener">
                                <i class="fa-brands fa-linkedin"></i> LinkedIn
                            </a>
                        @endif
                        @if($c->facebook)
                            <a class="social-btn" href="{{ $c->facebook }}" target="_blank" rel="noopener">
                                <i class="fa-brands fa-facebook"></i> Facebook
                            </a>
                        @endif
                        @if($c->twitter)
                            <a class="social-btn" href="{{ $c->twitter }}" target="_blank" rel="noopener">
                                <i class="fa-brands fa-x-twitter"></i> Twitter
                            </a>
                        @endif
                    </div>
                </div>
            </div>
            @endif

            <div class="co-panel">
                <div class="co-panel-head">
                    <div>
                        <h5><i class="fa-solid fa-map-location-dot me-1" style="color:var(--accent)"></i> Location map</h5>
                        <span class="sub">Embedded Google Map</span>
                    </div>
                </div>
                <div class="co-panel-body co-map">
                    @if($c->longitude)
                        {!! $c->longitude !!}
                    @else
                        <div class="text-center text-muted py-4">
                            <i class="fa-solid fa-map mb-2 d-block" style="font-size:1.5rem;opacity:.4;"></i>
                            No map embed available
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

@else
    <div class="co-hero">
        <div>
            <h2>Company profile</h2>
            <p>Set up your organization details once — branding, contacts, and location.</p>
        </div>
        <div class="chip"><i class="fa-solid fa-building me-1"></i> Not configured</div>
    </div>

    <div class="co-panel">
        <div class="co-empty">
            <div class="ico"><i class="fa-solid fa-building"></i></div>
            <h5>No company details yet</h5>
            <p>Add your company profile once. After that, only edit will be available.</p>
            @if($isAdmin)
                <a href="{{ route('companies.index', ['add' => 1]) }}" class="btn add-btn">
                    <i class="fa-solid fa-plus me-1"></i> Add Company
                </a>
            @endif
        </div>
    </div>
@endif
</div>
@endsection
