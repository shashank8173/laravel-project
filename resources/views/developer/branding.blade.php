@extends('layouts.app')

@section('title', 'App Branding')
@section('heading', 'App Branding')

@section('page_actions')
<a href="{{ route('developer.cron') }}" class="btn btn-outline-secondary me-1">
    <i class="fa-solid fa-clock me-1"></i> Cron Jobs
</a>
@endsection

@push('styles')
<style>
    .br-wrap {
        --ink:#0f2744; --muted:#6b7c93; --line:#e8eef5; --soft:#f4f7fb; --accent:#ff9b44;
        --card:#fff; --panel-head:linear-gradient(180deg,#fff,#fafbfd); --input-bg:#fff;
        --prio-bg:#fffaf5; --prio-border:#ffd7b0;
        color:var(--ink);
    }
    .br-wrap .br-hero {
        border:1px solid var(--line); border-radius:18px; overflow:hidden; margin-bottom:1rem;
        background:
            radial-gradient(900px 220px at 0% 0%, rgba(255,155,68,.18), transparent 55%),
            var(--hero-grad, linear-gradient(135deg, #0f2744 0%, #16375f 55%, #1b466f 100%));
        color:#fff; padding:1.15rem 1.3rem;
        display:flex; justify-content:space-between; align-items:center; gap:1rem; flex-wrap:wrap;
    }
    .br-wrap .br-hero h2 { margin:0; font-size:1.25rem; font-weight:800; color:#fff; }
    .br-wrap .br-hero p { margin:.3rem 0 0; opacity:.85; font-size:.9rem; color:#fff; }
    .br-wrap .br-hero .chip {
        background:rgba(255,255,255,.12); border:1px solid rgba(255,255,255,.18);
        border-radius:12px; padding:.55rem .85rem; font-size:.82rem; font-weight:650; color:#fff;
    }
    .br-wrap .br-panel {
        background:var(--card); border:1px solid var(--line); border-radius:18px; overflow:hidden; margin-bottom:1rem;
        color:var(--ink);
    }
    .br-wrap .br-panel-head {
        display:flex; justify-content:space-between; align-items:center; gap:1rem; flex-wrap:wrap;
        padding:1rem 1.15rem; border-bottom:1px solid var(--line);
        background:var(--panel-head);
    }
    .br-wrap .br-panel-head h5 { margin:0; font-weight:750; color:var(--ink); font-size:1rem; }
    .br-wrap .br-panel-head .sub { display:block; font-size:.78rem; color:var(--muted); margin-top:.15rem; }
    .br-wrap .br-panel-body { padding:1.15rem; background:var(--card); color:var(--ink); }
    .br-wrap .form-label { font-size:.8rem; font-weight:650; color:var(--muted); margin-bottom:.3rem; }
    .br-wrap .form-control {
        border-radius:11px; border-color:var(--line); padding:.55rem .75rem; color:var(--ink); background:var(--input-bg);
    }
    .br-wrap .form-control:focus {
        border-color:#ffd0a8; box-shadow:0 0 0 .2rem rgba(255,155,68,.15);
        background:var(--input-bg); color:var(--ink);
    }
    .br-wrap .form-check-label { color:var(--ink); }
    .br-wrap .preview {
        border:1px dashed var(--line); border-radius:14px; background:var(--soft);
        min-height:110px; display:flex; align-items:center; justify-content:center; padding:1rem;
    }
    .br-wrap .preview.is-dark {
        background:linear-gradient(135deg,#0f2744,#1b466f);
    }
    .br-wrap .preview img { max-height:72px; max-width:100%; object-fit:contain; }
    .br-wrap .preview.icon img { max-height:48px; max-width:48px; }
    .br-wrap .hint { font-size:.78rem; color:var(--muted); margin-top:.35rem; }
    .br-wrap .hint code, .br-wrap .prio code {
        color:#c2410c; background:rgba(255,155,68,.12); padding:.1rem .35rem; border-radius:6px;
    }
    .br-wrap .btn-submit {
        background:linear-gradient(145deg,#ff9b44,#f07a1a); border:0; color:#fff;
        font-weight:750; border-radius:12px; padding:.65rem 1.15rem;
        box-shadow:0 8px 18px rgba(255,155,68,.28);
    }
    .br-wrap .btn-submit:hover { filter:brightness(1.03); color:#fff; }
    .br-wrap .prio {
        border:1px solid var(--prio-border); border-radius:12px; background:var(--prio-bg);
        padding:.85rem 1rem; font-size:.86rem; color:var(--ink); margin-bottom:1rem;
    }

    html[data-theme="dark"] .br-wrap {
        --ink:#e8eef8; --muted:#a8b6cc; --line:#243044; --soft:#1a2232; --card:#141b27;
        --panel-head:linear-gradient(180deg,#171e2c,#141b27); --input-bg:#0f1520;
        --prio-bg:rgba(255,155,68,.12); --prio-border:rgba(255,155,68,.35);
    }
    html[data-theme="dark-blue"] .br-wrap {
        --ink:#eaf2ff; --muted:#9db4d4; --line:#1a3358; --soft:#102240; --card:#0c1a31;
        --panel-head:linear-gradient(180deg,#0e1f3c,#0c1a31); --input-bg:#081528;
        --prio-bg:rgba(255,155,68,.12); --prio-border:rgba(255,155,68,.35);
    }
    html[data-theme="light"] .br-wrap {
        --ink:#0f2744; --muted:#6b7c93; --line:#e8eef5; --soft:#f4f7fb; --card:#ffffff;
        --panel-head:linear-gradient(180deg,#fff,#fafbfd); --input-bg:#ffffff;
        --prio-bg:#fffaf5; --prio-border:#ffd7b0;
    }
    html[data-theme="dark"] .br-wrap .hint code,
    html[data-theme="dark"] .br-wrap .prio code,
    html[data-theme="dark-blue"] .br-wrap .hint code,
    html[data-theme="dark-blue"] .br-wrap .prio code {
        color:#fdba74; background:rgba(255,155,68,.16);
    }
</style>
@endpush

@section('content')
<div class="br-wrap">
    <div class="br-hero">
        <div>
            <h2>Logo &amp; favicon</h2>
            <p>Set app logo and browser icon via upload or URL. Used in sidebar, topbar, login, and favicon.</p>
        </div>
        <div class="chip"><i class="fa-solid fa-palette me-1"></i> Developer</div>
    </div>

    <div class="prio">
        <strong>Priority:</strong> If both URL and upload are set, <strong>URL wins</strong>.
        Clear the URL field to use the uploaded file. Leave both empty to use the default <code>assets/img/logo2.png</code>.
    </div>

    <form method="POST" action="{{ route('developer.branding.update') }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="row g-3">
            <div class="col-lg-6">
                <div class="br-panel">
                    <div class="br-panel-head">
                        <div>
                            <h5><i class="fa-solid fa-image me-1" style="color:var(--accent)"></i> App logo</h5>
                            <span class="sub">Sidebar, topbar, login page</span>
                        </div>
                    </div>
                    <div class="br-panel-body">
                        <div class="preview is-dark mb-3">
                            <img src="{{ $branding->logoUrl() }}" alt="Logo preview" onerror="this.style.display='none'">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Logo URL</label>
                            <input type="text" name="logo_url" class="form-control"
                                   value="{{ old('logo_url', $branding->logo_url) }}"
                                   placeholder="https://example.com/logo.png or assets/img/logo2.png">
                            <div class="hint">Full URL or path under public/</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Or upload logo</label>
                            <input type="file" name="logo" class="form-control" accept="image/*">
                            @if($branding->logo_path)
                                <div class="form-check mt-2">
                                    <input class="form-check-input" type="checkbox" name="clear_logo_file" value="1" id="clearLogo">
                                    <label class="form-check-label" for="clearLogo">Remove uploaded logo file</label>
                                </div>
                            @endif
                        </div>
                        <div class="hint">Current resolved: <code>{{ $branding->logoUrl() }}</code></div>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="br-panel">
                    <div class="br-panel-head">
                        <div>
                            <h5><i class="fa-solid fa-bookmark me-1" style="color:var(--accent)"></i> App icon (favicon)</h5>
                            <span class="sub">Browser tab icon</span>
                        </div>
                    </div>
                    <div class="br-panel-body">
                        <div class="preview icon mb-3">
                            <img src="{{ $branding->iconUrl() }}" alt="Icon preview" onerror="this.style.display='none'">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Icon URL</label>
                            <input type="text" name="icon_url" class="form-control"
                                   value="{{ old('icon_url', $branding->icon_url) }}"
                                   placeholder="https://example.com/favicon.png">
                            <div class="hint">Full URL or path under public/</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Or upload icon</label>
                            <input type="file" name="icon" class="form-control" accept="image/*,.ico">
                            @if($branding->icon_path)
                                <div class="form-check mt-2">
                                    <input class="form-check-input" type="checkbox" name="clear_icon_file" value="1" id="clearIcon">
                                    <label class="form-check-label" for="clearIcon">Remove uploaded icon file</label>
                                </div>
                            @endif
                        </div>
                        <div class="hint">Current resolved: <code>{{ $branding->iconUrl() }}</code></div>
                    </div>
                </div>
            </div>
        </div>

        <button type="submit" class="btn btn-submit">
            <i class="fa-solid fa-floppy-disk me-1"></i> Save branding
        </button>
    </form>
</div>
@endsection
