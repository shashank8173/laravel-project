@extends('layouts.app')

@section('title', 'Attendance')
@section('heading', 'Attendance')

@section('page_actions')
<a href="{{ route('leaves.employee') }}" class="btn btn-outline-secondary me-1">
    <i class="fa-solid fa-calendar-days me-1"></i> My Leaves
</a>
@if(auth()->user()->canManageTeam())
    <a href="{{ route('attendance.all') }}" class="btn btn-outline-secondary">
        <i class="fa-solid fa-users me-1"></i> Team Attendance
    </a>
@endif
@endsection

@push('styles')
<style>
    .att-wrap { --ink:#0f2744; --muted:#6b7c93; --line:#e8eef5; --soft:#f4f7fb; --accent:#ff9b44; --card:#fff; }
    .att-wrap .att-hero {
        border:1px solid var(--line); border-radius:18px; margin-bottom:1rem;
        background:
            radial-gradient(900px 220px at 0% 0%, rgba(255,155,68,.18), transparent 55%),
            linear-gradient(135deg, #0f2744 0%, #16375f 55%, #1b466f 100%);
        color:#fff; padding:1.15rem 1.3rem;
        display:flex; justify-content:space-between; align-items:center; gap:1rem; flex-wrap:wrap;
    }
    .att-wrap .att-hero h2 { margin:0; font-size:1.25rem; font-weight:800; color:#fff; }
    .att-wrap .att-hero p { margin:.3rem 0 0; opacity:.85; font-size:.9rem; color:#fff; }
    .att-wrap .att-hero .chip {
        background:rgba(255,255,255,.12); border:1px solid rgba(255,255,255,.18);
        border-radius:12px; padding:.55rem .85rem; font-size:.82rem; font-weight:650; color:#fff;
    }

    .att-wrap .att-metric {
        border:1px solid var(--line); border-radius:16px; background:var(--card); padding:1rem 1.05rem;
        position:relative; overflow:hidden; height:100%;
    }
    .att-wrap .att-metric::before { content:""; position:absolute; left:0; top:0; bottom:0; width:4px; background:var(--accent); }
    .att-wrap .att-metric.is-blue::before { background:#2563eb; }
    .att-wrap .att-metric.is-amber::before { background:#f59e0b; }
    .att-wrap .att-metric.is-green::before { background:#16a34a; }
    .att-wrap .att-metric.is-red::before { background:#ef4444; }
    .att-wrap .att-metric .k { font-size:.68rem; text-transform:uppercase; letter-spacing:.04em; color:var(--muted); font-weight:700; margin:0; }
    .att-wrap .att-metric .v { font-size:1.35rem; font-weight:800; color:var(--ink); margin:.25rem 0 0; }
    .att-wrap .att-metric .hint { font-size:.75rem; color:var(--muted); margin-top:.25rem; }

    .att-wrap .att-panel {
        background:var(--card); border:1px solid var(--line); border-radius:18px; overflow:hidden; margin-bottom:1rem;
    }
    .att-wrap .att-panel-head {
        display:flex; justify-content:space-between; align-items:center; gap:1rem; flex-wrap:wrap;
        padding:1rem 1.15rem; border-bottom:1px solid var(--line);
        background:var(--soft);
    }
    .att-wrap .att-panel-head h5 { margin:0; font-weight:750; color:var(--ink); font-size:1rem; }
    .att-wrap .att-panel-head .sub { display:block; font-size:.78rem; color:var(--muted); margin-top:.15rem; }
    .att-wrap .att-panel-body { padding:1.1rem 1.15rem; background:var(--card); }

    .att-wrap .today-card {
        border:1px solid var(--line); border-radius:16px; background:var(--card); padding:1rem 1.1rem;
        display:flex; align-items:center; gap:1rem; margin-bottom:1rem;
    }
    .att-wrap .today-card .ico {
        width:48px; height:48px; border-radius:14px; display:inline-flex; align-items:center; justify-content:center;
        background:#f0fdf4; color:#15803d; font-size:1.2rem; flex-shrink:0;
    }
    .att-wrap .today-card.is-out .ico { background:#fff7ed; color:#c2410c; }
    .att-wrap .today-card.is-none .ico { background:var(--soft); color:var(--muted); }
    .att-wrap .today-card .k { font-size:.72rem; text-transform:uppercase; letter-spacing:.04em; color:var(--muted); font-weight:700; }
    .att-wrap .today-card .v { font-size:1.1rem; font-weight:800; color:var(--ink); margin-top:.15rem; }
    .att-wrap .today-card .h { font-size:.82rem; color:var(--muted); margin-top:.2rem; }

    .att-wrap .loc-box {
        border:1px solid rgba(255,155,68,.35); background:rgba(255,155,68,.08);
        border-radius:14px; padding:.9rem 1rem; margin-bottom:1rem;
    }
    .att-wrap .loc-box .title { font-weight:750; color:var(--ink); font-size:.92rem; }
    .att-wrap .loc-box .msg { font-size:.84rem; color:var(--muted); margin-top:.25rem; }
    .att-wrap .loc-box .coords {
        margin-top:.55rem; font-family:ui-monospace,Consolas,monospace; font-size:.78rem; color:var(--ink);
        word-break:break-all;
    }
    .att-wrap .loc-status {
        display:inline-flex; align-items:center; gap:.35rem; padding:.22rem .6rem; border-radius:999px;
        font-size:.72rem; font-weight:750;
    }
    .att-wrap .loc-status.ok { background:#dcfce7; color:#15803d; }
    .att-wrap .loc-status.wait { background:#fef3c7; color:#b45309; }
    .att-wrap .loc-status.err { background:#fee2e2; color:#b91c1c; }

    .att-wrap .punch-grid {
        display:grid; grid-template-columns:1fr 1fr; gap:.75rem;
    }
    .att-wrap .punch-btn {
        width:100%; border:0; border-radius:14px; padding:.95rem 1rem; font-weight:800; font-size:.98rem;
        display:inline-flex; align-items:center; justify-content:center; gap:.55rem;
    }
    .att-wrap .punch-btn:disabled { opacity:.45; cursor:not-allowed; }
    .att-wrap .punch-in {
        background:linear-gradient(145deg,#16a34a,#15803d); color:#fff;
        box-shadow:0 10px 22px rgba(22,163,74,.28);
    }
    .att-wrap .punch-out {
        background:linear-gradient(145deg,#ef4444,#b91c1c); color:#fff;
        box-shadow:0 10px 22px rgba(239,68,68,.25);
    }
    .att-wrap .btn-ghost {
        border:1px solid var(--line); color:var(--ink); background:var(--card); font-weight:650;
        border-radius:10px; font-size:.8rem; padding:.4rem .75rem;
    }
    .att-wrap .btn-ghost:hover { border-color:#ffd0a8; background:rgba(255,155,68,.1); color:var(--ink); }
    .att-wrap .form-select, .att-wrap .form-control {
        border-radius:11px; border-color:var(--line); background:var(--card); color:var(--ink);
    }
    .att-wrap .form-label { font-size:.8rem; font-weight:650; color:var(--muted); }

    .att-wrap .table > :not(caption) > * > * { vertical-align:middle; }
    .att-wrap .table thead th {
        font-size:.72rem; text-transform:uppercase; letter-spacing:.04em; color:var(--muted);
        font-weight:700; border-bottom-color:var(--line); background:var(--soft); white-space:nowrap;
    }
    .att-wrap .table td { border-color:var(--line); color:var(--ink); font-size:.9rem; background:var(--card); }
    .att-wrap .table tbody tr:last-child td { border-bottom:0; }
    .att-wrap .pill {
        display:inline-flex; align-items:center; padding:.22rem .55rem; border-radius:999px;
        font-size:.72rem; font-weight:700;
    }
    .att-wrap .pill-ok { background:#dcfce7; color:#15803d; }
    .att-wrap .pill-late { background:#fee2e2; color:#b91c1c; }
    .att-wrap .pill-mute { background:var(--soft); color:var(--muted); }
    .att-wrap .map-link { font-size:.78rem; font-weight:700; color:#38bdf8; text-decoration:none; }
    html[data-theme="light"] .att-wrap .map-link { color:#0b5cab; }
    .att-wrap .map-link:hover { text-decoration:underline; }
    .att-wrap .empty { color:var(--muted); text-align:center; padding:1.75rem .5rem; font-size:.9rem; }

    .loc-modal .modal-content { border:0; border-radius:18px; overflow:hidden; background:var(--card,#fff); color:var(--ink,#0f2744); }
    .loc-modal .modal-header {
        background:linear-gradient(135deg,#0f2744,#1b466f); color:#fff; border:0; padding:1.1rem 1.25rem;
    }
    .loc-modal .modal-title { font-weight:800; color:#fff; }
    .loc-modal .modal-body { padding:1.25rem; background:var(--card,#fff); }
    .loc-modal .loc-ico {
        width:64px; height:64px; border-radius:50%; margin:0 auto 1rem;
        display:flex; align-items:center; justify-content:center;
        background:#fff7ed; color:#c2410c; font-size:1.6rem;
    }
    .loc-modal .btn-allow {
        background:linear-gradient(145deg,#ff9b44,#f07a1a); border:0; color:#fff;
        font-weight:750; border-radius:12px; padding:.7rem 1rem; width:100%;
    }
    .loc-modal .btn-deny {
        border:1px solid var(--line,#e8eef5); background:var(--card,#fff); color:var(--ink,#0f2744);
        font-weight:650; border-radius:12px; padding:.65rem 1rem; width:100%;
    }

    html[data-theme="dark"] .att-wrap { --card:#141b27; }
    html[data-theme="dark-blue"] .att-wrap { --card:#0c1a31; }
    html[data-theme="light"] .att-wrap { --card:#ffffff; }

    @media (max-width: 575.98px) {
        .att-wrap .punch-grid { grid-template-columns:1fr; }
    }
</style>
@endpush

@section('content')
@php
    $state = $todayOpen ? 'in' : ($todayLast ? 'out' : 'none');
    $stateLabel = $state === 'in' ? 'Punched in' : ($state === 'out' ? 'Completed for today' : 'Not punched yet');
    $stateHint = $todayOpen
        ? 'In at '.optional($todayOpen->clock_in_time)->format('h:i A')
        : ($todayLast
            ? 'In '.optional($todayLast->clock_in_time)->format('h:i A').' · Out '.optional($todayLast->clock_out_time)->format('h:i A')
            : 'Allow location, then punch in to start your day');
    $canPunchIn = ! $todayOpen;
    $canPunchOut = (bool) $todayOpen;
@endphp

<div class="att-wrap">
    <div class="att-hero">
        <div>
            <h2>My attendance</h2>
            <p>Live location is required before punch in or punch out.</p>
        </div>
        <div class="chip"><i class="fa-regular fa-calendar me-1"></i> {{ now()->format('d M Y · l') }}</div>
    </div>

    <div class="today-card is-{{ $state }}">
        <div class="ico"><i class="fa-solid fa-{{ $state === 'in' ? 'circle-check' : ($state === 'out' ? 'right-from-bracket' : 'clock') }}"></i></div>
        <div class="flex-grow-1">
            <div class="k">Today status</div>
            <div class="v">{{ $stateLabel }}</div>
            <div class="h">{{ $stateHint }}</div>
        </div>
        <button type="button" class="btn btn-ghost" id="openLocModalBtn">
            <i class="fa-solid fa-location-dot me-1"></i> Location
        </button>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-6 col-md-3">
            <div class="att-metric is-blue">
                <p class="k">Days this month</p>
                <p class="v">{{ $stats['days'] ?? 0 }}</p>
                <p class="hint">{{ date('F', mktime(0,0,0,$month,1)) }} {{ $year }}</p>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="att-metric is-green">
                <p class="k">On time</p>
                <p class="v">{{ $stats['ontime'] ?? 0 }}</p>
                <p class="hint">Within office timing</p>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="att-metric is-amber">
                <p class="k">Late</p>
                <p class="v">{{ $stats['late'] ?? 0 }}</p>
                <p class="hint">After login window</p>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="att-metric is-red">
                <p class="k">Open punch</p>
                <p class="v">{{ $stats['open'] ?? 0 }}</p>
                <p class="hint">Missing clock-out</p>
            </div>
        </div>
    </div>

    <div class="att-panel">
        <div class="att-panel-head">
            <div>
                <h5><i class="fa-solid fa-fingerprint me-1" style="color:var(--accent)"></i> Punch with live location</h5>
                <span class="sub">Buttons unlock only after location is allowed</span>
            </div>
        </div>
        <div class="att-panel-body">
            <div class="loc-box">
                <div class="d-flex justify-content-between align-items-start gap-2 flex-wrap">
                    <div class="title"><i class="fa-solid fa-satellite-dish me-1"></i> GPS status</div>
                    <span class="loc-status wait" id="locStatus"><i class="fa-solid fa-spinner fa-spin"></i> Waiting</span>
                </div>
                <div class="msg" id="locMessage">Please allow location permission to enable punch.</div>
                <div class="coords d-none" id="locCoords"></div>
                <div class="mt-2 d-flex gap-2 flex-wrap">
                    <button type="button" class="btn btn-ghost" id="refreshLocBtn">
                        <i class="fa-solid fa-rotate me-1"></i> Refresh location
                    </button>
                    <button type="button" class="btn add-btn" id="askLocBtn">
                        <i class="fa-solid fa-location-crosshairs me-1"></i> Allow location
                    </button>
                </div>
            </div>

            <div class="punch-grid">
                <form method="POST" action="{{ route('attendance.punch-in') }}" id="punchInForm">
                    @csrf
                    <input type="hidden" name="latitude" id="inLat">
                    <input type="hidden" name="longitude" id="inLng">
                    <input type="hidden" name="accuracy" id="inAcc">
                    <button type="submit" class="punch-btn punch-in" id="punchInBtn" disabled>
                        <i class="fa-solid fa-fingerprint"></i>
                        <span>{{ $canPunchIn ? 'Punch In Now' : 'Already punched in' }}</span>
                    </button>
                </form>

                <form method="POST" action="{{ route('attendance.punch-out') }}" id="punchOutForm">
                    @csrf
                    <input type="hidden" name="latitude" id="outLat">
                    <input type="hidden" name="longitude" id="outLng">
                    <input type="hidden" name="accuracy" id="outAcc">
                    <button type="submit" class="punch-btn punch-out" id="punchOutBtn" disabled>
                        <i class="fa-solid fa-right-from-bracket"></i>
                        <span>{{ $canPunchOut ? 'Punch Out Now' : 'Punch out unavailable' }}</span>
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="att-panel">
        <div class="att-panel-head">
            <div>
                <h5><i class="fa-solid fa-filter me-1" style="color:var(--accent)"></i> Filter records</h5>
                <span class="sub">Choose month and year</span>
            </div>
        </div>
        <div class="att-panel-body">
            <form class="row g-2 align-items-end" method="GET" action="{{ route('attendance.mine') }}">
                <div class="col-md-4">
                    <label class="form-label">Month</label>
                    <select name="month" class="form-select">
                        @for($m = 1; $m <= 12; $m++)
                            <option value="{{ $m }}" @selected((int) $month === $m)>{{ date('F', mktime(0, 0, 0, $m, 1)) }}</option>
                        @endfor
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Year</label>
                    <select name="year" class="form-select">
                        @for($y = now()->year - 2; $y <= now()->year + 1; $y++)
                            <option value="{{ $y }}" @selected((int) $year === $y)>{{ $y }}</option>
                        @endfor
                    </select>
                </div>
                <div class="col-md-4">
                    <button class="btn add-btn w-100"><i class="fa-solid fa-magnifying-glass me-1"></i> Apply</button>
                </div>
            </form>
        </div>
    </div>

    <div class="att-panel">
        <div class="att-panel-head">
            <div>
                <h5><i class="fa-solid fa-list me-1" style="color:var(--accent)"></i> Attendance records</h5>
                <span class="sub">{{ $records->total() }} record(s) with location</span>
            </div>
        </div>
        <div class="att-panel-body p-0">
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th class="ps-3">Clock in</th>
                            <th>Clock out</th>
                            <th>Status</th>
                            <th>Working</th>
                            <th class="pe-3">Location</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($records as $record)
                        @php
                            $late = strtolower((string) ($record->late_status ?? ''));
                            $pill = $late === 'late' ? 'pill-late' : ($late === 'on time' ? 'pill-ok' : 'pill-mute');
                            $inLat = $record->clock_in_latitude;
                            $inLng = $record->clock_in_longitude;
                            $outLat = $record->clock_out_latitude;
                            $outLng = $record->clock_out_longitude;
                        @endphp
                        <tr>
                            <td class="ps-3" style="white-space:nowrap;">{{ optional($record->clock_in_time)->format('d M Y · h:i A') ?? '—' }}</td>
                            <td style="white-space:nowrap;">{{ optional($record->clock_out_time)->format('d M Y · h:i A') ?? '—' }}</td>
                            <td><span class="pill {{ $pill }}">{{ $record->late_status ?? '—' }}</span></td>
                            <td>{{ $record->total_working_time ?? '—' }}</td>
                                    <td class="pe-3">
                                        @if($inLat && $inLng)
                                            <a class="map-link" target="_blank" rel="noopener"
                                               href="https://www.google.com/maps?q={{ $inLat }},{{ $inLng }}"
                                               title="Open punch-in location">Map in</a>
                                        @else
                                            <span class="text-muted small">In —</span>
                                        @endif
                                        <span class="text-muted"> · </span>
                                        @if($outLat && $outLng)
                                            <a class="map-link" target="_blank" rel="noopener"
                                               href="https://www.google.com/maps?q={{ $outLat }},{{ $outLng }}"
                                               title="Open punch-out location">Map out</a>
                                        @else
                                            <span class="text-muted small">Out —</span>
                                        @endif
                                    </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="empty">No attendance records for this period.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            @if($records->hasPages())
                <div class="p-3 border-top">{{ $records->links() }}</div>
            @endif
        </div>
    </div>

    @if($machine)
        <div class="att-panel">
            <div class="att-panel-head">
                <div>
                    <h5><i class="fa-solid fa-table-cells me-1" style="color:var(--accent)"></i> Machine day grid</h5>
                    <span class="sub">{{ $machine->employee_name ?? 'Employee' }} · {{ date('F', mktime(0, 0, 0, $month, 1)) }} {{ $year }}</span>
                </div>
            </div>
            <div class="att-panel-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered table-sm text-center align-middle mb-0">
                        <thead>
                        <tr>
                            @for($d = 1; $d <= 31; $d++)
                                <th>{{ $d }}</th>
                            @endfor
                        </tr>
                        </thead>
                        <tbody>
                        <tr>
                            @for($d = 1; $d <= 31; $d++)
                                <td>{{ $machine->{'date'.$d} ?? '—' }}</td>
                            @endfor
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif
</div>

{{-- Location permission modal --}}
<div class="modal fade loc-modal" id="locationPermissionModal" tabindex="-1" aria-labelledby="locationPermissionLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="locationPermissionLabel">Allow location access</h5>
            </div>
            <div class="modal-body text-center">
                <div class="loc-ico"><i class="fa-solid fa-location-dot"></i></div>
                <h5 class="fw-bold mb-2" style="color:var(--ink,#0f2744);">Enable live location</h5>
                <p class="text-muted mb-3" style="font-size:.92rem;">
                    Live location is required for Punch In / Punch Out.
                    Select <strong>Allow</strong> in the browser popup. Your location will be saved with the punch record.
                </p>
                <div class="d-grid gap-2">
                    <button type="button" class="btn btn-allow" id="modalAllowBtn">
                        <i class="fa-solid fa-check me-1"></i> Allow location
                    </button>
                    <button type="button" class="btn btn-deny" data-bs-dismiss="modal" id="modalLaterBtn">
                        Not now
                    </button>
                </div>
                <p class="small text-muted mt-3 mb-0">If you previously blocked it, enable Location for this site in your browser settings.</p>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const canPunchIn = @json($canPunchIn);
    const canPunchOut = @json($canPunchOut);
    let coords = null;
    let modalShown = false;

    const statusEl = document.getElementById('locStatus');
    const msgEl = document.getElementById('locMessage');
    const coordsEl = document.getElementById('locCoords');
    const punchInBtn = document.getElementById('punchInBtn');
    const punchOutBtn = document.getElementById('punchOutBtn');
    const modalEl = document.getElementById('locationPermissionModal');
    const modal = bootstrap.Modal.getOrCreateInstance(modalEl);

    function setStatus(kind, html, message) {
        statusEl.className = 'loc-status ' + kind;
        statusEl.innerHTML = html;
        msgEl.textContent = message;
    }

    function showPermissionModal() {
        if (modalShown) return;
        modalShown = true;
        modal.show();
    }

    function fillForms(pos) {
        coords = {
            latitude: pos.coords.latitude,
            longitude: pos.coords.longitude,
            accuracy: pos.coords.accuracy || null,
        };
        document.getElementById('inLat').value = coords.latitude;
        document.getElementById('inLng').value = coords.longitude;
        document.getElementById('inAcc').value = coords.accuracy ?? '';
        document.getElementById('outLat').value = coords.latitude;
        document.getElementById('outLng').value = coords.longitude;
        document.getElementById('outAcc').value = coords.accuracy ?? '';

        coordsEl.classList.remove('d-none');
        coordsEl.textContent = 'Lat ' + coords.latitude.toFixed(6)
            + ' · Lng ' + coords.longitude.toFixed(6)
            + (coords.accuracy ? (' · ~' + Math.round(coords.accuracy) + 'm') : '');

        setStatus('ok', '<i class="fa-solid fa-location-dot"></i> Ready', 'Live location captured. Punch buttons are unlocked.');
        punchInBtn.disabled = !canPunchIn;
        punchOutBtn.disabled = !canPunchOut;
        modal.hide();
    }

    function fail(err) {
        coords = null;
        coordsEl.classList.add('d-none');
        punchInBtn.disabled = true;
        punchOutBtn.disabled = true;

        let message = 'Location permission is required to punch in or out.';
        if (err && err.code === 1) {
            message = 'Location blocked. Tap Allow location and choose Allow in the browser popup.';
        } else if (err && err.code === 2) {
            message = 'Location unavailable. Check GPS/network and try again.';
        } else if (err && err.code === 3) {
            message = 'Location request timed out. Please try again.';
        } else if (!navigator.geolocation) {
            message = 'This browser does not support geolocation.';
        }

        setStatus('err', '<i class="fa-solid fa-triangle-exclamation"></i> Required', message);
        showPermissionModal();
    }

    function requestLocation(fromUserClick) {
        if (!navigator.geolocation) {
            fail({ code: 0 });
            return;
        }
        setStatus('wait', '<i class="fa-solid fa-spinner fa-spin"></i> Getting…', 'Waiting for browser location permission…');
        punchInBtn.disabled = true;
        punchOutBtn.disabled = true;

        navigator.geolocation.getCurrentPosition(fillForms, function (err) {
            fail(err);
            if (fromUserClick) showPermissionModal();
        }, {
            enableHighAccuracy: true,
            timeout: 20000,
            maximumAge: 0,
        });
    }

    function guardSubmit(e, form) {
        if (!coords) {
            e.preventDefault();
            showPermissionModal();
            return false;
        }
        const lat = form.querySelector('[name="latitude"]').value;
        const lng = form.querySelector('[name="longitude"]').value;
        if (!lat || !lng) {
            e.preventDefault();
            showPermissionModal();
            return false;
        }
        return true;
    }

    document.getElementById('punchInForm').addEventListener('submit', function (e) { guardSubmit(e, this); });
    document.getElementById('punchOutForm').addEventListener('submit', function (e) { guardSubmit(e, this); });
    document.getElementById('refreshLocBtn').addEventListener('click', function () { requestLocation(true); });
    document.getElementById('askLocBtn').addEventListener('click', function () { requestLocation(true); });
    document.getElementById('openLocModalBtn').addEventListener('click', function () { showPermissionModal(); });
    document.getElementById('modalAllowBtn').addEventListener('click', function () {
        modal.hide();
        requestLocation(true);
    });

    // First visit: ask via modal, then browser permission
    setTimeout(function () { showPermissionModal(); }, 400);
})();
</script>
@endpush
