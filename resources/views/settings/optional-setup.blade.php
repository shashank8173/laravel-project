@extends('layouts.app')

@section('title', 'Optional Setup')
@section('heading', 'Optional Setup')

@section('page_actions')
<a href="{{ route('companies.index', ['edit' => 1]) }}" class="btn btn-outline-secondary btn-sm">
    <i class="fa-solid fa-building me-1"></i> Companies
</a>
<a href="{{ route('settings.email') }}" class="btn btn-outline-secondary btn-sm">
    <i class="fa-solid fa-envelope me-1"></i> Email Settings
</a>
@endsection

@push('styles')
<style>
    .ops-wrap { --ink:#0f2744; --muted:#6b7c93; --line:#e8eef5; --soft:#f4f7fb; --accent:#ff9b44; }
    .ops-wrap .ops-hero {
        border:1px solid var(--line); border-radius:18px; overflow:hidden; margin-bottom:1rem;
        background:
            radial-gradient(900px 220px at 0% 0%, rgba(255,155,68,.18), transparent 55%),
            linear-gradient(135deg, #0f2744 0%, #16375f 55%, #1b466f 100%);
        color:#fff; padding:1.25rem 1.35rem;
        display:flex; justify-content:space-between; align-items:center; gap:1rem; flex-wrap:wrap;
    }
    .ops-wrap .ops-hero h2 { margin:0; font-size:1.25rem; font-weight:800; }
    .ops-wrap .ops-hero p { margin:.35rem 0 0; opacity:.88; font-size:.9rem; max-width:560px; }
    .ops-wrap .ops-progress {
        min-width:150px; background:rgba(255,255,255,.12); border:1px solid rgba(255,255,255,.18);
        border-radius:12px; padding:.75rem .9rem; text-align:center;
    }
    .ops-wrap .ops-progress .n { font-size:1.35rem; font-weight:800; line-height:1; }
    .ops-wrap .ops-progress .l { font-size:.72rem; opacity:.85; margin-top:.25rem; }
    .ops-wrap .ops-bar {
        height:8px; border-radius:999px; background:rgba(255,255,255,.18); overflow:hidden; margin-top:.55rem;
    }
    .ops-wrap .ops-bar > span { display:block; height:100%; background:var(--accent); border-radius:999px; }
    .ops-wrap .ops-metric {
        border:1px solid var(--line); border-radius:14px; background:#fff; padding:1rem 1.1rem; height:100%;
        position:relative; overflow:hidden;
    }
    .ops-wrap .ops-metric::before { content:""; position:absolute; left:0; top:0; bottom:0; width:4px; background:var(--accent); }
    .ops-wrap .ops-metric.is-ok::before { background:#16a34a; }
    .ops-wrap .ops-metric.is-pending::before { background:#f59e0b; }
    .ops-wrap .ops-metric .k { font-size:.68rem; text-transform:uppercase; letter-spacing:.04em; color:var(--muted); font-weight:700; }
    .ops-wrap .ops-metric .v { font-size:1.2rem; font-weight:800; color:var(--ink); margin:.2rem 0 0; }
    .ops-wrap .ops-panel {
        background:#fff; border:1px solid var(--line); border-radius:18px; overflow:hidden; margin-bottom:1rem;
        scroll-margin-top:90px;
    }
    .ops-wrap .ops-panel-head {
        display:flex; justify-content:space-between; align-items:flex-start; gap:1rem; flex-wrap:wrap;
        padding:1rem 1.15rem; border-bottom:1px solid var(--line);
        background:linear-gradient(180deg,#fff,#fafbfd);
    }
    .ops-wrap .ops-panel-head h5 { margin:0; font-weight:750; color:var(--ink); font-size:1rem; }
    .ops-wrap .ops-panel-head .sub { display:block; font-size:.78rem; color:var(--muted); margin-top:.2rem; max-width:640px; }
    .ops-wrap .ops-panel-body { padding:1.15rem; }
    .ops-wrap .ops-panel-foot {
        padding:.85rem 1.15rem; border-top:1px solid var(--line); background:#fafbfd;
        display:flex; justify-content:flex-end; gap:.5rem; flex-wrap:wrap;
    }
    .ops-wrap .form-label { font-size:.8rem; font-weight:600; color:var(--muted); }
    .ops-wrap .help { font-size:.75rem; color:var(--muted); margin-top:.25rem; }
    .ops-wrap .pill {
        display:inline-flex; align-items:center; gap:.35rem; padding:.28rem .7rem; border-radius:999px;
        font-size:.72rem; font-weight:700;
    }
    .ops-wrap .pill-ok { background:#dcfce7; color:#15803d; }
    .ops-wrap .pill-pending { background:#fef3c7; color:#b45309; }
    .ops-wrap .btn-save {
        background:var(--accent); border-color:var(--accent); color:#fff; font-weight:700;
        border-radius:10px; padding:.45rem 1rem;
    }
    .ops-wrap .btn-save:hover { filter:brightness(.96); color:#fff; }
    .ops-wrap .check-list { list-style:none; margin:0; padding:0; }
    .ops-wrap .check-list li {
        display:flex; gap:.85rem; align-items:flex-start; padding:.85rem 0; border-bottom:1px solid var(--line);
    }
    .ops-wrap .check-list li:last-child { border-bottom:0; padding-bottom:0; }
    .ops-wrap .check-list .ico {
        width:34px; height:34px; border-radius:10px; display:inline-flex; align-items:center; justify-content:center;
        flex:0 0 auto; font-size:.9rem;
    }
    .ops-wrap .check-list .ico.ok { background:#f0fdf4; color:#15803d; }
    .ops-wrap .check-list .ico.no { background:#fffbeb; color:#b45309; }
    .ops-wrap .check-list .t { font-weight:700; color:var(--ink); margin:0; }
    .ops-wrap .check-list .d { font-size:.82rem; color:var(--muted); margin:.2rem 0 0; }
    .ops-wrap .check-list a { font-size:.78rem; font-weight:700; color:#c2410c; text-decoration:none; }
    .ops-wrap .check-list a:hover { text-decoration:underline; }
</style>
@endpush

@section('content')
<div class="ops-wrap">
    <div class="ops-hero">
        <div>
            <h2>Optional setup</h2>
            <p>Configure office punch location and notification mailboxes. Core HRM works without these — fill them when you want geofence and email alerts.</p>
        </div>
        <div class="ops-progress">
            <div class="n">{{ $done }}/{{ $total }}</div>
            <div class="l">items ready</div>
            <div class="ops-bar"><span style="width:{{ $percent }}%"></span></div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-6 col-lg-3">
            <div class="ops-metric {{ $percent === 100 ? 'is-ok' : 'is-pending' }}">
                <div class="k">Progress</div>
                <p class="v">{{ $percent }}%</p>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="ops-metric is-ok">
                <div class="k">Ready</div>
                <p class="v">{{ $done }}</p>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="ops-metric is-pending">
                <div class="k">Pending</div>
                <p class="v">{{ $total - $done }}</p>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="ops-metric">
                <div class="k">Status</div>
                <p class="v" style="font-size:1rem;margin-top:.45rem;">
                    @if($percent === 100)
                        <span class="pill pill-ok"><i class="fa-solid fa-check"></i> Complete</span>
                    @else
                        <span class="pill pill-pending"><i class="fa-solid fa-clock"></i> Incomplete</span>
                    @endif
                </p>
            </div>
        </div>
    </div>

    <div class="ops-panel">
        <div class="ops-panel-head">
            <div>
                <h5><i class="fa-solid fa-list-check me-1" style="color:var(--accent)"></i> Checklist</h5>
                <span class="sub">Green = ready. Amber = optional but not configured yet.</span>
            </div>
        </div>
        <div class="ops-panel-body">
            <ul class="check-list">
                @foreach($items as $item)
                    <li>
                        <span class="ico {{ $item['ready'] ? 'ok' : 'no' }}">
                            <i class="fa-solid {{ $item['ready'] ? 'fa-circle-check' : 'fa-circle-exclamation' }}"></i>
                        </span>
                        <div class="flex-grow-1">
                            <p class="t">{{ $item['title'] }}</p>
                            <p class="d">{{ $item['detail'] }}</p>
                            <a href="#{{ $item['key'] }}">{{ $item['action'] }} →</a>
                        </div>
                        <span class="pill {{ $item['ready'] ? 'pill-ok' : 'pill-pending' }}">
                            {{ $item['ready'] ? 'Ready' : 'Pending' }}
                        </span>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>

    {{-- Geofence --}}
    <form method="POST" action="{{ route('developer.optional-setup.update') }}" id="geofence" class="ops-panel">
        @csrf
        <input type="hidden" name="section" value="geofence">
        <div class="ops-panel-head">
            <div>
                <h5><i class="fa-solid fa-location-dot me-1" style="color:var(--accent)"></i> Punch geofence</h5>
                <span class="sub">Store office as <code>lat,lng</code>. If empty, employees can punch from anywhere (existing behaviour).</span>
            </div>
            @php $geo = collect($items)->firstWhere('key', 'geofence'); @endphp
            <span class="pill {{ ($geo['ready'] ?? false) ? 'pill-ok' : 'pill-pending' }}">
                {{ ($geo['ready'] ?? false) ? 'Ready' : 'Pending' }}
            </span>
        </div>
        <div class="ops-panel-body">
            @unless($company)
                <div class="alert alert-warning mb-3">
                    No company record found. <a href="{{ route('companies.index', ['edit' => 1]) }}">Add company details</a> first.
                </div>
            @endunless
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Office coordinates (lat,lng)</label>
                    <input type="text" name="latitude" class="form-control @error('latitude') is-invalid @enderror"
                           value="{{ old('latitude', $form['latitude']) }}"
                           placeholder="28.6139000,77.2090000" {{ $company ? '' : 'disabled' }}>
                    @error('latitude')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <div class="help">Example: <code>28.6139,77.2090</code> · Map embed stays on Companies → Longitude.</div>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Radius (meters)</label>
                    <input type="number" name="geofence_radius" min="50" max="50000" class="form-control"
                           value="{{ old('geofence_radius', $form['geofence_radius']) }}" required>
                    <div class="help">Default 500 m</div>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Geofence</label>
                    <select name="geofence_enabled" class="form-select">
                        <option value="1" @selected(old('geofence_enabled', $form['geofence_enabled']) === '1')>Enabled</option>
                        <option value="0" @selected(old('geofence_enabled', $form['geofence_enabled']) === '0')>Disabled</option>
                    </select>
                    <div class="help">Disable to ignore location even if coords are set</div>
                </div>
            </div>
        </div>
        <div class="ops-panel-foot">
            <button type="submit" class="btn btn-save" {{ $company ? '' : 'disabled' }}>
                <i class="fa-solid fa-floppy-disk me-1"></i> Save geofence
            </button>
        </div>
    </form>

    {{-- Support --}}
    <form method="POST" action="{{ route('developer.optional-setup.update') }}" id="support" class="ops-panel">
        @csrf
        <input type="hidden" name="section" value="support">
        <div class="ops-panel-head">
            <div>
                <h5><i class="fa-solid fa-ticket me-1" style="color:var(--accent)"></i> Support ticket emails</h5>
                <span class="sub">Used when employees create tickets, update status, or add comments.</span>
            </div>
            @php $sup = collect($items)->firstWhere('key', 'support'); @endphp
            <span class="pill {{ ($sup['ready'] ?? false) ? 'pill-ok' : 'pill-pending' }}">
                {{ ($sup['ready'] ?? false) ? 'Ready' : 'Pending' }}
            </span>
        </div>
        <div class="ops-panel-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Primary recipient (To)</label>
                    <input type="email" name="configs[SUPPORT_TO]" class="form-control @error('configs.SUPPORT_TO') is-invalid @enderror"
                           value="{{ old('configs.SUPPORT_TO', $form['SUPPORT_TO']) }}" placeholder="support@company.com">
                    @error('configs.SUPPORT_TO')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">CC emails</label>
                    <textarea name="configs[SUPPORT_CC]" class="form-control" rows="2"
                              placeholder="lead@company.com, backup@company.com">{{ old('configs.SUPPORT_CC', $form['SUPPORT_CC']) }}</textarea>
                    <div class="help">Comma-separated</div>
                </div>
            </div>
        </div>
        <div class="ops-panel-foot">
            <button type="submit" class="btn btn-save"><i class="fa-solid fa-floppy-disk me-1"></i> Save support</button>
        </div>
    </form>

    {{-- Harassment --}}
    <form method="POST" action="{{ route('developer.optional-setup.update') }}" id="harassment" class="ops-panel">
        @csrf
        <input type="hidden" name="section" value="harassment">
        <div class="ops-panel-head">
            <div>
                <h5><i class="fa-solid fa-shield-halved me-1" style="color:var(--accent)"></i> Harassment / POSH recipients</h5>
                <span class="sub">Alert only — complaint narrative stays in the admin queue.</span>
            </div>
            @php $har = collect($items)->firstWhere('key', 'harassment'); @endphp
            <span class="pill {{ ($har['ready'] ?? false) ? 'pill-ok' : 'pill-pending' }}">
                {{ ($har['ready'] ?? false) ? 'Ready' : 'Pending' }}
            </span>
        </div>
        <div class="ops-panel-body">
            <label class="form-label">Recipients</label>
            <textarea name="configs[HARASSMENT_RECIPIENTS]" class="form-control" rows="2"
                      placeholder="icc@company.com, hr@company.com">{{ old('configs.HARASSMENT_RECIPIENTS', $form['HARASSMENT_RECIPIENTS']) }}</textarea>
            <div class="help">Comma-separated · keep limited to authorized ICC / HR contacts</div>
        </div>
        <div class="ops-panel-foot">
            <button type="submit" class="btn btn-save"><i class="fa-solid fa-floppy-disk me-1"></i> Save harassment</button>
        </div>
    </form>

    {{-- Onboarding --}}
    <form method="POST" action="{{ route('developer.optional-setup.update') }}" id="onboarding" class="ops-panel">
        @csrf
        <input type="hidden" name="section" value="onboarding">
        <div class="ops-panel-head">
            <div>
                <h5><i class="fa-solid fa-user-check me-1" style="color:var(--accent)"></i> Onboarding recipients</h5>
                <span class="sub">Notified when an onboarding step is marked completed.</span>
            </div>
            @php $onb = collect($items)->firstWhere('key', 'onboarding'); @endphp
            <span class="pill {{ ($onb['ready'] ?? false) ? 'pill-ok' : 'pill-pending' }}">
                {{ ($onb['ready'] ?? false) ? 'Ready' : 'Pending' }}
            </span>
        </div>
        <div class="ops-panel-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Recipients</label>
                    <textarea name="configs[ONBOARDING_RECIPIENTS]" class="form-control" rows="2"
                              placeholder="hr@company.com">{{ old('configs.ONBOARDING_RECIPIENTS', $form['ONBOARDING_RECIPIENTS']) }}</textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label">CC</label>
                    <textarea name="configs[ONBOARDING_CC]" class="form-control" rows="2"
                              placeholder="it@company.com">{{ old('configs.ONBOARDING_CC', $form['ONBOARDING_CC']) }}</textarea>
                </div>
            </div>
        </div>
        <div class="ops-panel-foot">
            <button type="submit" class="btn btn-save"><i class="fa-solid fa-floppy-disk me-1"></i> Save onboarding</button>
        </div>
    </form>
</div>
@endsection
