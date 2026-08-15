@extends('layouts.app')

@section('title', 'Cron Jobs')
@section('heading', 'Cron / Scheduler Setup')

@push('styles')
<style>
    .cron-wrap { --ink:#0f2744; --muted:#6b7c93; --line:#e8eef5; --soft:#f4f7fb; --accent:#ff9b44; }
    .cron-wrap .cron-panel { background:#fff; border:1px solid var(--line); border-radius:18px; overflow:hidden; margin-bottom:1rem; }
    .cron-wrap .cron-panel-head {
        padding:1rem 1.15rem; border-bottom:1px solid var(--line);
        background:linear-gradient(180deg,#fff,#fafbfd);
    }
    .cron-wrap .cron-panel-head h5 { margin:0; font-weight:750; color:var(--ink); }
    .cron-wrap .cron-panel-head .sub { font-size:.78rem; color:var(--muted); margin-top:.2rem; display:block; }
    .cron-wrap .cron-panel-body { padding:1.15rem; }
    .cron-wrap .cmd {
        background:#0f2744; color:#e8eef5; border-radius:12px; padding:.9rem 1rem;
        font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace; font-size:.82rem;
        word-break:break-all; position:relative;
    }
    .cron-wrap .cmd-actions { margin-top:.55rem; display:flex; gap:.5rem; flex-wrap:wrap; }
    .cron-wrap .btn-copy {
        background:var(--accent); border:0; color:#fff; font-weight:700; border-radius:10px; padding:.4rem .85rem; font-size:.8rem;
    }
    .cron-wrap .step { border-left:3px solid var(--accent); padding-left:.85rem; margin-bottom:.9rem; }
    .cron-wrap .step h6 { margin:0 0 .25rem; color:var(--ink); font-weight:750; }
    .cron-wrap .step p { margin:0; color:var(--muted); font-size:.9rem; }
    .cron-wrap .table td, .cron-wrap .table th { vertical-align:middle; font-size:.9rem; }
    .cron-wrap code { background:var(--soft); padding:.1rem .35rem; border-radius:6px; color:var(--ink); }
</style>
@endpush

@section('content')
<div class="cron-wrap">
    <div class="cron-panel">
        <div class="cron-panel-head">
            <h5><i class="fa-solid fa-clock me-1" style="color:var(--accent)"></i> Why this is needed</h5>
            <span class="sub">Laravel scheduler must run every minute. It then fires birthday/anniversary + attendance reminder commands at the right time.</span>
        </div>
        <div class="cron-panel-body">
            <div class="step">
                <h6>1. Project path</h6>
                <p>{{ $basePath }}</p>
            </div>
            <div class="step">
                <h6>2. PHP binary detected</h6>
                <p><code>{{ $phpBinary }}</code></p>
            </div>
            <div class="step">
                <h6>3. Scheduled jobs already in code</h6>
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead>
                            <tr>
                                <th>Command</th>
                                <th>When</th>
                                <th>Purpose</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><code>hrm:celebrations</code></td>
                                <td>Daily 09:05</td>
                                <td>Birthday + work anniversary + <strong>holiday</strong> cards (official emails)</td>
                            </tr>
                            <tr>
                                <td><code>hrm:morning-reminder</code></td>
                                <td>Weekdays 09:00</td>
                                <td>Morning login reminder</td>
                            </tr>
                            <tr>
                                <td><code>hrm:evening-reminder</code></td>
                                <td>Weekdays 18:30</td>
                                <td>Evening punch-out reminder</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="cron-panel">
        <div class="cron-panel-head">
            <h5><i class="fa-solid fa-vial me-1" style="color:var(--accent)"></i> Test via browser URL</h5>
            <span class="sub">Open or copy-paste these URLs to verify notifications — all mails go to <strong>official (office_email)</strong> addresses</span>
        </div>
        <div class="cron-panel-body">
            <div class="step">
                <h6>1) Preview (no emails — list only)</h6>
                <div class="cmd" id="previewUrl">{{ $previewUrl }}</div>
                <div class="cmd-actions">
                    <button type="button" class="btn-copy" data-copy="previewUrl">Copy URL</button>
                    <a href="{{ $previewUrl }}" class="btn btn-sm btn-outline-secondary" target="_blank" rel="noopener">Open preview</a>
                </div>
            </div>

            <div class="step">
                <h6>2) Send today’s matches (real emails)</h6>
                <div class="cmd" id="sendUrl">{{ $sendUrl }}</div>
                <div class="cmd-actions">
                    <button type="button" class="btn-copy" data-copy="sendUrl">Copy URL</button>
                    <a href="{{ $sendUrl }}" class="btn btn-sm btn-copy"
                       onclick="return confirm('Send today’s birthday/anniversary emails now?')">Open &amp; send</a>
                </div>
            </div>

            <div class="step">
                <h6>3) Force test (any employee — even if today is not their day)</h6>
                <p class="mb-2">If there are no matches today, pick an employee below and send a test mail.</p>
                <form method="GET" action="{{ $forceUrl }}" class="row g-2 align-items-end" onsubmit="return confirm('Send a test celebration email for this employee?')">
                    <div class="col-md-5">
                        <label class="form-label small text-muted mb-1">Employee</label>
                        <x-employee-select
                            name="employee_id"
                            :employees="$employees"
                            :required="true"
                            placeholder="Search employee…"
                        />
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small text-muted mb-1">Type</label>
                        <select name="type" class="form-select">
                            <option value="both">Birthday + Anniversary</option>
                            <option value="birthday">Birthday only</option>
                            <option value="anniversary">Anniversary only</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <div class="form-check mt-4">
                            <input type="hidden" name="notify_team" value="0">
                            <input class="form-check-input" type="checkbox" name="notify_team" value="1" id="notifyTeam" checked>
                            <label class="form-check-label small" for="notifyTeam">Notify team</label>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-copy w-100">Send test</button>
                    </div>
                </form>
                <p class="mt-2 mb-1 small text-muted">Direct URL example:</p>
                <div class="cmd" id="forceUrlExample">{{ $forceUrl }}?employee_id=12&amp;type=birthday&amp;notify_team=1</div>
                <div class="cmd-actions">
                    <button type="button" class="btn-copy" data-copy="forceUrlExample">Copy example URL</button>
                </div>
            </div>
        </div>
    </div>

    <div class="cron-panel">
        <div class="cron-panel-head">
            <h5><i class="fa-solid fa-snowflake me-1" style="color:#0ea5e9"></i> Holidays — config, test &amp; send</h5>
            <span class="sub">Holiday list from <a href="{{ route('holidays.index') }}">/holidays</a> · Cards/images in <a href="{{ route('settings.greetings') }}">Greeting Cards</a> · Auto mail via cron to all <strong>office_email</strong> addresses</span>
        </div>
        <div class="cron-panel-body">
            <div class="step">
                <h6>Manual configuration</h6>
                <p>
                    1. Add/edit holidays on <a href="{{ route('holidays.index') }}">Holidays</a> (date + name)<br>
                    2. Set holiday text and upload card images in <a href="{{ route('settings.greetings') }}">Greeting Cards</a><br>
                    3. Use <code>{holiday}</code> in templates (name fills automatically)<br>
                    4. Set up cron below — emails send automatically on the holiday date
                </p>
            </div>

            <div class="step">
                <h6>1) Preview holidays (no emails)</h6>
                <div class="cmd" id="holidayPreviewUrl">{{ $holidayPreviewUrl }}</div>
                <div class="cmd-actions">
                    <button type="button" class="btn-copy" data-copy="holidayPreviewUrl">Copy URL</button>
                    <a href="{{ $holidayPreviewUrl }}" class="btn btn-sm btn-outline-secondary" target="_blank" rel="noopener">Open preview</a>
                </div>
            </div>

            <div class="step">
                <h6>2) Send today’s holiday matches</h6>
                <div class="cmd" id="holidaySendUrl">{{ $holidaySendUrl }}</div>
                <div class="cmd-actions">
                    <button type="button" class="btn-copy" data-copy="holidaySendUrl">Copy URL</button>
                    <a href="{{ $holidaySendUrl }}" class="btn btn-sm btn-copy"
                       onclick="return confirm('Send today’s holiday cards to all official emails?')">Open &amp; send</a>
                </div>
            </div>

            <div class="step">
                <h6>3) Force test — send any holiday card now</h6>
                <form method="GET" action="{{ $holidayForceUrl }}" class="row g-2 align-items-end" onsubmit="return confirm('Send this holiday card to all official emails?')">
                    <div class="col-md-8">
                        <label class="form-label small text-muted mb-1">Holiday</label>
                        <select name="holiday_id" class="form-select" required>
                            <option value="">Select holiday…</option>
                            @foreach($holidays as $h)
                                <option value="{{ $h->id }}">{{ $h->name }} — {{ $h->date }} ({{ $h->year }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-copy w-100">Send holiday test</button>
                    </div>
                </form>
                <p class="mt-2 mb-1 small text-muted">Direct URL example:</p>
                <div class="cmd" id="holidayForceExample">{{ $holidayForceUrl }}?holiday_id=1</div>
                <div class="cmd-actions">
                    <button type="button" class="btn-copy" data-copy="holidayForceExample">Copy example URL</button>
                </div>
            </div>
        </div>
    </div>

    <div class="cron-panel">
        <div class="cron-panel-head">
            <h5>Linux / cPanel / VPS — copy this cron entry</h5>
            <span class="sub">Crontab: run every minute. Paste into <code>crontab -e</code> or cPanel → Cron Jobs</span>
        </div>
        <div class="cron-panel-body">
            <div class="cmd" id="linuxCron">{{ $linuxCron }}</div>
            <div class="cmd-actions">
                <button type="button" class="btn-copy" data-copy="linuxCron">Copy cron line</button>
            </div>
            <p class="mt-3 mb-0 text-muted small">
                Alternative (same effect):
            </p>
            <div class="cmd mt-2" id="scheduleCmd">{{ $scheduleCmd }}</div>
            <div class="cmd-actions">
                <button type="button" class="btn-copy" data-copy="scheduleCmd">Copy schedule:run</button>
            </div>
        </div>
    </div>

    <div class="cron-panel">
        <div class="cron-panel-head">
            <h5>Windows (XAMPP / local server) — Task Scheduler</h5>
            <span class="sub">Run as Administrator in Command Prompt, or create the task manually</span>
        </div>
        <div class="cron-panel-body">
            <div class="step">
                <h6>Option A — one-line create</h6>
                <div class="cmd" id="windowsTask">{{ $windowsTask }}</div>
                <div class="cmd-actions">
                    <button type="button" class="btn-copy" data-copy="windowsTask">Copy Windows command</button>
                </div>
            </div>
            <div class="step">
                <h6>Option B — Task Scheduler GUI</h6>
                <p>
                    1. Open <strong>Task Scheduler</strong> → Create Basic Task<br>
                    2. Trigger: <strong>Daily</strong> (then edit → repeat every 1 minute for indefinite)<br>
                    3. Action: Start a program<br>
                    Program: <code>{{ $phpBinary }}</code><br>
                    Arguments: <code>{{ $basePath }}\artisan schedule:run</code><br>
                    Start in: <code>{{ $basePath }}</code>
                </p>
            </div>
        </div>
    </div>

    <div class="cron-panel">
        <div class="cron-panel-head">
            <h5>Manual test (copy &amp; run in terminal)</h5>
            <span class="sub">Use these to verify SMTP + cards without waiting for cron</span>
        </div>
        <div class="cron-panel-body">
            <p class="mb-1 small text-muted">List who would get mail today (no send):</p>
            <div class="cmd" id="dryRun">{{ $celebrationCmd }} --dry-run</div>
            <div class="cmd-actions">
                <button type="button" class="btn-copy" data-copy="dryRun">Copy dry-run</button>
            </div>

            <p class="mb-1 mt-3 small text-muted">Send for today:</p>
            <div class="cmd" id="sendToday">{{ $celebrationCmd }}</div>
            <div class="cmd-actions">
                <button type="button" class="btn-copy" data-copy="sendToday">Copy send today</button>
            </div>

            <p class="mb-1 mt-3 small text-muted">Send for a specific date:</p>
            <div class="cmd" id="sendDate">{{ $celebrationCmd }} --date=2026-08-09</div>
            <div class="cmd-actions">
                <button type="button" class="btn-copy" data-copy="sendDate">Copy date example</button>
            </div>
        </div>
    </div>

    <div class="cron-panel">
        <div class="cron-panel-head">
            <h5>Checklist before go-live</h5>
        </div>
        <div class="cron-panel-body">
            <ol class="mb-0" style="color:var(--ink);">
                <li class="mb-2">Configure SMTP under <a href="{{ route('settings.email') }}">Email Settings</a></li>
                <li class="mb-2">Upload birthday, anniversary &amp; <strong>holiday</strong> images under <a href="{{ route('settings.greetings') }}">Greeting Cards</a></li>
                <li class="mb-2">Add holidays under <a href="{{ route('holidays.index') }}">Holidays</a> (auto email on that date)</li>
                <li class="mb-2">Run <code>php artisan storage:link</code> once (so card images open in emails)</li>
                <li class="mb-2">Add the cron / Task Scheduler entry above</li>
                <li>Test with <code>hrm:celebrations --dry-run</code> then a real send</li>
            </ol>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.querySelectorAll('.btn-copy').forEach(function (btn) {
    btn.addEventListener('click', async function () {
        const id = this.getAttribute('data-copy');
        const el = document.getElementById(id);
        if (!el) return;
        await navigator.clipboard.writeText(el.textContent.trim());
        const old = this.textContent;
        this.textContent = 'Copied';
        setTimeout(() => this.textContent = old, 1200);
    });
});
</script>
@endpush
