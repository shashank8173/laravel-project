@extends('layouts.app')

@section('title', 'Office Timing')
@section('heading', 'Office Timing')

@section('page_actions')
<button type="button" class="btn btn-notice" data-bs-toggle="modal" data-bs-target="#officeTimingNoticeModal">
    <i class="fa-solid fa-circle-info me-1"></i> Notice
</button>
@endsection

@push('styles')
<style>
    .otm-wrap { --ink:#0f2744; --muted:#6b7c93; --line:#e8eef5; --soft:#f4f7fb; --accent:#ff9b44; }
    .otm-wrap .otm-panel { background:#fff; border:1px solid var(--line); border-radius:18px; overflow:hidden; margin-bottom:1rem; }
    .otm-wrap .otm-panel-head {
        display:flex; justify-content:space-between; align-items:center; gap:1rem; flex-wrap:wrap;
        padding:1rem 1.15rem; border-bottom:1px solid var(--line);
        background:linear-gradient(180deg,#fff,#fafbfd);
    }
    .otm-wrap .otm-panel-head h5 { margin:0; font-weight:750; color:var(--ink); font-size:1rem; }
    .otm-wrap .otm-panel-head .sub { font-size:.78rem; color:var(--muted); display:block; margin-top:.15rem; }
    .otm-wrap .otm-panel-body { padding:1.15rem; }
    .otm-wrap .otm-panel-foot {
        padding:.85rem 1.15rem; border-top:1px solid var(--line); background:#fafbfd;
        display:flex; justify-content:flex-end;
    }
    .otm-wrap .form-label { font-size:.8rem; font-weight:600; color:var(--muted); }
    .otm-wrap .help { font-size:.75rem; color:var(--muted); margin-top:.25rem; }
    .otm-wrap .otm-metric {
        border:1px solid var(--line); border-radius:14px; background:#fff; padding:1rem 1.1rem; height:100%;
        position:relative; overflow:hidden;
    }
    .otm-wrap .otm-metric::before { content:""; position:absolute; left:0; top:0; bottom:0; width:4px; background:var(--accent); }
    .otm-wrap .otm-metric.is-blue::before { background:#2563eb; }
    .otm-wrap .otm-metric.is-green::before { background:#16a34a; }
    .otm-wrap .otm-metric.is-amber::before { background:#f59e0b; }
    .otm-wrap .otm-metric .k { font-size:.7rem; text-transform:uppercase; letter-spacing:.04em; color:var(--muted); font-weight:700; }
    .otm-wrap .otm-metric .v { font-size:1.25rem; font-weight:800; color:var(--ink); margin:.2rem 0 0; line-height:1.15; }
    .otm-wrap .btn-save {
        background:var(--accent); border-color:var(--accent); color:#fff; font-weight:700;
        border-radius:10px; padding:.45rem 1rem;
    }
    .otm-wrap .btn-save:hover { filter:brightness(.96); color:#fff; }
    .btn-notice {
        border:1px solid #f59e0b; color:#92400e; background:#fffbeb; font-weight:700; border-radius:999px;
        padding:.35rem .9rem; font-size:.8rem;
    }
    .otm-wrap .otm-notice-body p { margin:0 0 .75rem; color:var(--ink); font-size:.92rem; }
    .otm-wrap .otm-notice-body ul { margin:0; padding-left:1.1rem; color:#4b5c73; font-size:.9rem; }
    .otm-wrap .otm-notice-body li { margin-bottom:.4rem; }
    .otm-wrap .sat-option {
        border:1px solid var(--line); border-radius:12px; padding:.85rem 1rem; background:var(--soft);
    }
</style>
@endpush

@section('content')
@php
    $fmt = function ($time) {
        if (! $time) {
            return '—';
        }
        try {
            return \Carbon\Carbon::parse($time)->format('h:i A');
        } catch (\Throwable $e) {
            return (string) $time;
        }
    };
    $satLabels = [
        'all-on' => 'All Saturdays On',
        '1st-3rd-on' => '1st & 3rd On',
        'all-off' => 'All Saturdays Off',
    ];
@endphp
<div class="otm-wrap">
    <div class="row g-3 mb-3">
        <div class="col-6 col-lg-3">
            <div class="otm-metric">
                <div class="k">Login</div>
                <p class="v">{{ $fmt($timing->login_time) }}</p>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="otm-metric is-blue">
                <div class="k">Logout</div>
                <p class="v">{{ $fmt($timing->logout_time) }}</p>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="otm-metric is-amber">
                <div class="k">Relaxation</div>
                <p class="v">{{ $fmt($timing->relaxation_time) }}</p>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="otm-metric is-green">
                <div class="k">Saturday</div>
                <p class="v" style="font-size:1rem;padding-top:.15rem;">{{ $satLabels[$timing->saturday_option] ?? ($timing->saturday_option ?: '—') }}</p>
            </div>
        </div>
    </div>

    <form method="POST" action="{{ route('settings.office-timing.save') }}">
        @csrf

        <div class="otm-panel">
            <div class="otm-panel-head">
                <div>
                    <h5><i class="fa-solid fa-clock me-1" style="color:var(--accent)"></i> Work hours</h5>
                    <span class="sub">Standard day start / end and grace period</span>
                </div>
            </div>
            <div class="otm-panel-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Login time</label>
                        <input type="time" name="login_time" class="form-control" value="{{ old('login_time', $timing->login_time) }}" required step="1">
                        <div class="help">Expected office start time</div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Logout time</label>
                        <input type="time" name="logout_time" class="form-control" value="{{ old('logout_time', $timing->logout_time) }}" required step="1">
                        <div class="help">Used for overtime after this time</div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Relaxation time</label>
                        <input type="time" name="relaxation_time" class="form-control" value="{{ old('relaxation_time', $timing->relaxation_time) }}" step="1">
                        <div class="help">Grace window before late marking</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="otm-panel">
            <div class="otm-panel-head">
                <div>
                    <h5><i class="fa-solid fa-indian-rupee-sign me-1" style="color:var(--accent)"></i> Late fines</h5>
                    <span class="sub">Penalty rules for late arrival</span>
                </div>
            </div>
            <div class="otm-panel-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Normal fine (₹)</label>
                        <input type="number" min="0" name="normal_fine" class="form-control" value="{{ old('normal_fine', $timing->normal_fine) }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Extra fine time</label>
                        <input type="time" name="extra_fine_time" class="form-control" value="{{ old('extra_fine_time', $timing->extra_fine_time) }}" step="1">
                        <div class="help">After this, extra fine applies</div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Extra fine (₹)</label>
                        <input type="number" min="0" name="extra_fine" class="form-control" value="{{ old('extra_fine', $timing->extra_fine) }}">
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <div class="sat-option w-100 small text-muted">
                            Normal fine applies after relaxation. Extra fine after the extra fine time.
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="otm-panel">
            <div class="otm-panel-head">
                <div>
                    <h5><i class="fa-solid fa-calendar-day me-1" style="color:var(--accent)"></i> Half day &amp; Saturday</h5>
                    <span class="sub">Half-day cutoffs and weekend policy</span>
                </div>
            </div>
            <div class="otm-panel-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Half day time</label>
                        <input type="time" name="half_day_time" class="form-control" value="{{ old('half_day_time', $timing->half_day_time) }}" step="1">
                        <div class="help">Morning half-day threshold</div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Evening half time</label>
                        <input type="time" name="evening_half_time" class="form-control" value="{{ old('evening_half_time', $timing->evening_half_time) }}" step="1">
                        <div class="help">Evening half-day threshold</div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Saturday option</label>
                        <select name="saturday_option" class="form-select" required>
                            @foreach($satLabels as $value => $label)
                                <option value="{{ $value }}" @selected(old('saturday_option', $timing->saturday_option) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <div class="help">Controls working Saturdays</div>
                    </div>
                </div>
            </div>
            <div class="otm-panel-foot">
                <button class="btn btn-save"><i class="fa-solid fa-floppy-disk me-1"></i> Save timing</button>
            </div>
        </div>
    </form>
</div>

<div class="modal fade" id="officeTimingNoticeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content" style="border:0;border-radius:16px;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold">
                    <i class="fa-solid fa-circle-info me-1" style="color:#f59e0b;"></i> Notice — Office Timing
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body otm-wrap otm-notice-body">
                <p><strong>What this page controls</strong></p>
                <p>These settings drive attendance marking, late fines, half-day logic, overtime, and reminder schedules across HRM.</p>
                <ul>
                    <li><strong>Login / Logout:</strong> Standard office hours. Overtime starts after logout time.</li>
                    <li><strong>Relaxation:</strong> Grace period before an employee is treated as late.</li>
                    <li><strong>Normal / Extra fine:</strong> Penalty amounts when late rules are crossed.</li>
                    <li><strong>Half day times:</strong> Cutoffs used to mark morning / evening half days.</li>
                    <li><strong>Saturday option:</strong> Whether Saturdays are working days (all, 1st &amp; 3rd, or off).</li>
                </ul>
                <p class="mb-0" style="font-size:.85rem;color:#6b7c93;">
                    Tip: After changing logout time, overtime reports will use the new end time.
                </p>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection
