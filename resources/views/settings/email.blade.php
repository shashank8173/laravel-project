@extends('layouts.app')

@section('title', 'Email Settings')
@section('heading', 'Email Configuration')

@push('styles')
<style>
    .em-wrap { --ink:#0f2744; --muted:#6b7c93; --line:#e8eef5; --soft:#f4f7fb; --accent:#ff9b44; }
    .em-wrap .em-panel { background:#fff; border:1px solid var(--line); border-radius:18px; overflow:hidden; margin-bottom:1rem; scroll-margin-top:90px; }
    .em-wrap .em-panel-head {
        display:flex; justify-content:space-between; align-items:center; gap:1rem; flex-wrap:wrap;
        padding:1rem 1.15rem; border-bottom:1px solid var(--line);
        background:linear-gradient(180deg,#fff,#fafbfd);
    }
    .em-wrap .em-panel-head h5 { margin:0; font-weight:750; color:var(--ink); font-size:1rem; }
    .em-wrap .em-panel-head .sub { font-size:.8rem; color:var(--muted); display:block; margin-top:.15rem; }
    .em-wrap .em-panel-body { padding:1.15rem; }
    .em-wrap .em-panel-foot {
        padding:.85rem 1.15rem; border-top:1px solid var(--line); background:#fafbfd;
        display:flex; justify-content:flex-end;
    }
    .em-wrap .form-label { font-size:.8rem; font-weight:600; color:var(--muted); }
    .em-wrap .help { font-size:.75rem; color:var(--muted); margin-top:.25rem; }
    .em-wrap .chip {
        display:inline-flex; align-items:center; gap:.35rem; padding:.3rem .7rem; border-radius:999px;
        background:#fff7ed; color:#9a3412; border:1px solid #fed7aa; font-size:.75rem; font-weight:700; margin:.2rem;
    }
    .em-wrap .chip i { color:var(--accent); }
    .em-wrap textarea.form-control { min-height:78px; }
    .em-wrap .btn-save-section {
        background:var(--accent); border-color:var(--accent); color:#fff; font-weight:700;
        border-radius:10px; padding:.4rem .9rem; font-size:.85rem;
    }
    .em-wrap .btn-save-section:hover { filter:brightness(.95); color:#fff; }
    .em-wrap .btn-notice {
        border:1px solid #f59e0b; color:#92400e; background:#fffbeb; font-weight:700; border-radius:999px;
        padding:.35rem .9rem; font-size:.8rem;
    }
    .em-wrap .btn-notice:hover { background:#fef3c7; color:#78350f; border-color:#d97706; }
    .em-wrap .em-notice-body p { margin:0 0 .75rem; color:var(--ink); font-size:.92rem; }
    .em-wrap .em-notice-body ul { margin:0; padding-left:1.1rem; color:#4b5c73; font-size:.9rem; }
    .em-wrap .em-notice-body li { margin-bottom:.4rem; }
</style>
@endpush

@section('content')
@php
    $val = fn (string $key) => old('configs.'.$key, $configs[$key] ?? '');

    $notices = [
        'smtp' => [
            'title' => 'SMTP configuration',
            'what' => 'SMTP settings tell the system which mail server to use for sending emails.',
            'points' => [
                '<strong>Host / Port / Encryption:</strong> Connection details of your company mail server.',
                '<strong>Username / Password:</strong> Mailbox credentials used to authenticate before sending.',
                'All HRM emails (notifications, documents, reminders, tickets) go through this SMTP setup.',
            ],
            'tip' => 'Use a dedicated company mailbox for SMTP authentication.',
        ],
        'from' => [
            'title' => 'Default sender (From)',
            'what' => 'This is the default “From” identity shown on system / notification emails.',
            'points' => [
                '<strong>Default From Email:</strong> Appears as the sender for general HRM notifications.',
                '<strong>Default From Name:</strong> Display name seen by recipients (e.g. HRM Notifications).',
                'Used when a mail is not specifically marked as an official document email.',
            ],
            'tip' => 'Prefer a noreply or notifications address for day-to-day alerts.',
        ],
        'docs' => [
            'title' => 'Official From emails — send documents',
            'what' => 'Official From addresses used when sending documents such as salary slips and expense PDFs.',
            'points' => [
                '<strong>Documents From Email / Name:</strong> Primary official sender for document emails.',
                '<strong>Additional Official From Emails:</strong> Extra company emails allowed as From for docs (comma-separated).',
                'Salary slip and other document mails prefer this Documents From identity.',
            ],
            'tip' => 'Use an official HR / accounts mailbox so documents look trusted.',
        ],
        'resignation' => [
            'title' => 'Resignation notifications',
            'what' => 'Controls who receives emails when an employee submits or updates a resignation.',
            'points' => [
                '<strong>Recipients:</strong> Main To addresses for resignation alerts.',
                '<strong>CC:</strong> Extra people copied on resignation emails.',
                'Use comma-separated emails for multiple people.',
            ],
            'tip' => 'Usually HR + reporting manager / leadership mailboxes.',
        ],
        'harassment' => [
            'title' => 'Harassment / complaints',
            'what' => 'Controls who receives POSH / harassment complaint notification emails.',
            'points' => [
                '<strong>Recipients:</strong> To addresses for complaint alerts.',
                'Keep this limited to authorized ICC / HR contacts only.',
                'Use comma-separated emails for multiple recipients.',
            ],
            'tip' => 'Do not add unrelated team inboxes here.',
        ],
        'support' => [
            'title' => 'Support tickets',
            'what' => 'Controls who receives support ticket emails.',
            'points' => [
                '<strong>Primary recipient:</strong> Main To address for new tickets, status updates, and comments.',
                '<strong>CC Emails:</strong> Extra addresses copied on those emails.',
                'If Primary is empty, Documents / Default From email is used as fallback.',
            ],
            'tip' => 'Keep the list short to avoid noise.',
        ],
        'onboarding' => [
            'title' => 'Onboarding',
            'what' => 'Controls who receives emails about employee onboarding progress and steps.',
            'points' => [
                '<strong>Recipients:</strong> Main To addresses for onboarding updates.',
                '<strong>CC:</strong> Extra people copied on onboarding emails.',
                'Use comma-separated emails for multiple people.',
            ],
            'tip' => 'Typically HR ops + IT / admin for asset and access setup.',
        ],
        'notice' => [
            'title' => 'Notice period',
            'what' => 'Controls who receives emails about notice-period steps and clearance updates.',
            'points' => [
                '<strong>Recipients:</strong> Main To addresses for notice-period notifications.',
                '<strong>CC:</strong> Extra people copied on these emails.',
                'Use comma-separated emails for multiple people.',
            ],
            'tip' => 'Usually HR + finance / admin for exit clearance.',
        ],
        'reminders' => [
            'title' => 'Reminders',
            'what' => 'This is the primary email address that receives daily attendance reminder emails (HR / admin inbox).',
            'points' => [
                '<strong>Evening reminder:</strong> Finds employees still clocked in (no punch-out). Mail is sent <strong>To</strong> this address, and those employees are added in <strong>CC</strong>.',
                '<strong>Morning reminder:</strong> The same address is used as the primary <strong>To</strong> for the morning login reminder.',
            ],
            'tip' => 'Use an HR mailbox so reminders are always monitored.',
        ],
        'test' => [
            'title' => 'Send test email',
            'what' => 'Sends a one-time test mail so you can verify SMTP and From settings.',
            'points' => [
                '<strong>Recipient:</strong> Where the test mail should be delivered.',
                '<strong>Send as — Default From:</strong> Uses default notification From identity.',
                '<strong>Send as — Documents / official From:</strong> Uses the documents From identity.',
            ],
            'tip' => 'Send a test after changing SMTP or From settings.',
        ],
    ];
@endphp
<div class="em-wrap">

    {{-- SMTP --}}
    <form method="POST" action="{{ route('settings.email.update') }}" id="section-smtp" class="em-panel">
        @csrf @method('PUT')
        <input type="hidden" name="section" value="smtp">
        <div class="em-panel-head">
            <div>
                <h5><i class="fa-solid fa-server me-1" style="color:var(--accent)"></i> SMTP configuration</h5>
                <span class="sub">Server used to send all mail</span>
            </div>
            <button type="button" class="btn btn-notice" data-bs-toggle="modal" data-bs-target="#notice-smtp">
                <i class="fa-solid fa-circle-info me-1"></i> Notice
            </button>
        </div>
        <div class="em-panel-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">{{ $fields['SMTP_HOST']['label'] }}</label>
                    <input type="text" name="configs[SMTP_HOST]" class="form-control" value="{{ $val('SMTP_HOST') }}" placeholder="mail.example.com">
                    <div class="help">{{ $fields['SMTP_HOST']['help'] }}</div>
                </div>
                <div class="col-md-3">
                    <label class="form-label">{{ $fields['SMTP_PORT']['label'] }}</label>
                    <input type="number" name="configs[SMTP_PORT]" class="form-control" value="{{ $val('SMTP_PORT') }}" placeholder="587">
                </div>
                <div class="col-md-3">
                    <label class="form-label">{{ $fields['SMTP_SECURE']['label'] }}</label>
                    <select name="configs[SMTP_SECURE]" class="form-select">
                        @foreach(['STARTTLS','TLS','SSL'] as $opt)
                            <option value="{{ $opt }}" @selected(strtoupper((string)$val('SMTP_SECURE')) === $opt)>{{ $opt }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">{{ $fields['SMTP_USERNAME']['label'] }}</label>
                    <input type="email" name="configs[SMTP_USERNAME]" class="form-control" value="{{ $val('SMTP_USERNAME') }}" placeholder="sender@example.com" autocomplete="off">
                </div>
                <div class="col-md-6">
                    <label class="form-label">{{ $fields['SMTP_PASSWORD']['label'] }}</label>
                    <input type="password" name="configs[SMTP_PASSWORD]" class="form-control" value="{{ $val('SMTP_PASSWORD') }}" autocomplete="new-password">
                    <div class="help">{{ $fields['SMTP_PASSWORD']['help'] }}</div>
                </div>
            </div>
        </div>
        <div class="em-panel-foot">
            <button type="submit" class="btn btn-save-section"><i class="fa-solid fa-floppy-disk me-1"></i> Save SMTP</button>
        </div>
    </form>

    {{-- Default From --}}
    <form method="POST" action="{{ route('settings.email.update') }}" id="section-from" class="em-panel">
        @csrf @method('PUT')
        <input type="hidden" name="section" value="from">
        <div class="em-panel-head">
            <div>
                <h5><i class="fa-solid fa-user me-1" style="color:var(--accent)"></i> Default sender (From)</h5>
                <span class="sub">Used for system / notification emails</span>
            </div>
            <button type="button" class="btn btn-notice" data-bs-toggle="modal" data-bs-target="#notice-from">
                <i class="fa-solid fa-circle-info me-1"></i> Notice
            </button>
        </div>
        <div class="em-panel-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">{{ $fields['FROM_EMAIL']['label'] }}</label>
                    <input type="email" name="configs[FROM_EMAIL]" class="form-control" value="{{ $val('FROM_EMAIL') }}" placeholder="noreply@example.com">
                    <div class="help">{{ $fields['FROM_EMAIL']['help'] }}</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">{{ $fields['FROM_NAME']['label'] }}</label>
                    <input type="text" name="configs[FROM_NAME]" class="form-control" value="{{ $val('FROM_NAME') }}" placeholder="HRM Notifications">
                    <div class="help">{{ $fields['FROM_NAME']['help'] }}</div>
                </div>
            </div>
        </div>
        <div class="em-panel-foot">
            <button type="submit" class="btn btn-save-section"><i class="fa-solid fa-floppy-disk me-1"></i> Save From</button>
        </div>
    </form>

    {{-- Docs From --}}
    <form method="POST" action="{{ route('settings.email.update') }}" id="section-docs" class="em-panel">
        @csrf @method('PUT')
        <input type="hidden" name="section" value="docs">
        <div class="em-panel-head">
            <div>
                <h5><i class="fa-solid fa-file-circle-check me-1" style="color:var(--accent)"></i> Official From emails — send documents</h5>
                <span class="sub">Salary slips, expense PDFs and other official docs</span>
            </div>
            <button type="button" class="btn btn-notice" data-bs-toggle="modal" data-bs-target="#notice-docs">
                <i class="fa-solid fa-circle-info me-1"></i> Notice
            </button>
        </div>
        <div class="em-panel-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">{{ $fields['DOCS_FROM_EMAIL']['label'] }}</label>
                    <input type="email" name="configs[DOCS_FROM_EMAIL]" class="form-control" value="{{ $val('DOCS_FROM_EMAIL') }}" placeholder="hr@example.com">
                    <div class="help">{{ $fields['DOCS_FROM_EMAIL']['help'] }}</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">{{ $fields['DOCS_FROM_NAME']['label'] }}</label>
                    <input type="text" name="configs[DOCS_FROM_NAME]" class="form-control" value="{{ $val('DOCS_FROM_NAME') }}" placeholder="HR Team">
                    <div class="help">{{ $fields['DOCS_FROM_NAME']['help'] }}</div>
                </div>
                <div class="col-12">
                    <label class="form-label">{{ $fields['OFFICIAL_FROM_EMAILS']['label'] }}</label>
                    <textarea name="configs[OFFICIAL_FROM_EMAILS]" class="form-control" placeholder="hr2@example.com, accounts@example.com">{{ $val('OFFICIAL_FROM_EMAILS') }}</textarea>
                    <div class="help">{{ $fields['OFFICIAL_FROM_EMAILS']['help'] }}</div>
                </div>
                @if(count($officialFrom))
                    <div class="col-12">
                        <div class="help mb-1">Currently allowed official From addresses:</div>
                        @foreach($officialFrom as $email)
                            <span class="chip"><i class="fa-solid fa-envelope"></i> {{ $email }}</span>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
        <div class="em-panel-foot">
            <button type="submit" class="btn btn-save-section"><i class="fa-solid fa-floppy-disk me-1"></i> Save documents From</button>
        </div>
    </form>

    {{-- Resignation --}}
    <form method="POST" action="{{ route('settings.email.update') }}" id="section-resignation" class="em-panel">
        @csrf @method('PUT')
        <input type="hidden" name="section" value="resignation">
        <div class="em-panel-head">
            <div>
                <h5><i class="fa-solid fa-clipboard-user me-1" style="color:var(--accent)"></i> Resignation notifications</h5>
            </div>
            <button type="button" class="btn btn-notice" data-bs-toggle="modal" data-bs-target="#notice-resignation">
                <i class="fa-solid fa-circle-info me-1"></i> Notice
            </button>
        </div>
        <div class="em-panel-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">{{ $fields['RESIGNATION_RECIPIENTS']['label'] }}</label>
                    <textarea name="configs[RESIGNATION_RECIPIENTS]" class="form-control">{{ $val('RESIGNATION_RECIPIENTS') }}</textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label">{{ $fields['RESIGNATION_CC']['label'] }}</label>
                    <textarea name="configs[RESIGNATION_CC]" class="form-control">{{ $val('RESIGNATION_CC') }}</textarea>
                </div>
            </div>
        </div>
        <div class="em-panel-foot">
            <button type="submit" class="btn btn-save-section"><i class="fa-solid fa-floppy-disk me-1"></i> Save resignation</button>
        </div>
    </form>

    {{-- Harassment --}}
    <form method="POST" action="{{ route('settings.email.update') }}" id="section-harassment" class="em-panel">
        @csrf @method('PUT')
        <input type="hidden" name="section" value="harassment">
        <div class="em-panel-head">
            <div>
                <h5><i class="fa-solid fa-shield-halved me-1" style="color:var(--accent)"></i> Harassment / complaints</h5>
            </div>
            <button type="button" class="btn btn-notice" data-bs-toggle="modal" data-bs-target="#notice-harassment">
                <i class="fa-solid fa-circle-info me-1"></i> Notice
            </button>
        </div>
        <div class="em-panel-body">
            <label class="form-label">{{ $fields['HARASSMENT_RECIPIENTS']['label'] }}</label>
            <textarea name="configs[HARASSMENT_RECIPIENTS]" class="form-control">{{ $val('HARASSMENT_RECIPIENTS') }}</textarea>
        </div>
        <div class="em-panel-foot">
            <button type="submit" class="btn btn-save-section"><i class="fa-solid fa-floppy-disk me-1"></i> Save harassment</button>
        </div>
    </form>

    {{-- Support --}}
    <form method="POST" action="{{ route('settings.email.update') }}" id="section-support" class="em-panel">
        @csrf @method('PUT')
        <input type="hidden" name="section" value="support">
        <div class="em-panel-head">
            <div>
                <h5><i class="fa-solid fa-ticket me-1" style="color:var(--accent)"></i> Support tickets</h5>
            </div>
            <button type="button" class="btn btn-notice" data-bs-toggle="modal" data-bs-target="#notice-support">
                <i class="fa-solid fa-circle-info me-1"></i> Notice
            </button>
        </div>
        <div class="em-panel-body">
            <div class="mb-3">
                <label class="form-label">{{ $fields['SUPPORT_TO']['label'] }}</label>
                <input type="email" name="configs[SUPPORT_TO]" class="form-control" value="{{ $val('SUPPORT_TO') }}" placeholder="support@example.com">
                <div class="help">{{ $fields['SUPPORT_TO']['help'] }}</div>
            </div>
            <label class="form-label">{{ $fields['SUPPORT_CC']['label'] }}</label>
            <textarea name="configs[SUPPORT_CC]" class="form-control">{{ $val('SUPPORT_CC') }}</textarea>
        </div>
        <div class="em-panel-foot">
            <button type="submit" class="btn btn-save-section"><i class="fa-solid fa-floppy-disk me-1"></i> Save support</button>
        </div>
    </form>

    {{-- Onboarding --}}
    <form method="POST" action="{{ route('settings.email.update') }}" id="section-onboarding" class="em-panel">
        @csrf @method('PUT')
        <input type="hidden" name="section" value="onboarding">
        <div class="em-panel-head">
            <div>
                <h5><i class="fa-solid fa-rocket me-1" style="color:var(--accent)"></i> Onboarding</h5>
            </div>
            <button type="button" class="btn btn-notice" data-bs-toggle="modal" data-bs-target="#notice-onboarding">
                <i class="fa-solid fa-circle-info me-1"></i> Notice
            </button>
        </div>
        <div class="em-panel-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">{{ $fields['ONBOARDING_RECIPIENTS']['label'] }}</label>
                    <textarea name="configs[ONBOARDING_RECIPIENTS]" class="form-control">{{ $val('ONBOARDING_RECIPIENTS') }}</textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label">{{ $fields['ONBOARDING_CC']['label'] }}</label>
                    <textarea name="configs[ONBOARDING_CC]" class="form-control">{{ $val('ONBOARDING_CC') }}</textarea>
                </div>
            </div>
        </div>
        <div class="em-panel-foot">
            <button type="submit" class="btn btn-save-section"><i class="fa-solid fa-floppy-disk me-1"></i> Save onboarding</button>
        </div>
    </form>

    {{-- Notice period --}}
    <form method="POST" action="{{ route('settings.email.update') }}" id="section-notice" class="em-panel">
        @csrf @method('PUT')
        <input type="hidden" name="section" value="notice">
        <div class="em-panel-head">
            <div>
                <h5><i class="fa-solid fa-bullhorn me-1" style="color:var(--accent)"></i> Notice period</h5>
            </div>
            <button type="button" class="btn btn-notice" data-bs-toggle="modal" data-bs-target="#notice-notice">
                <i class="fa-solid fa-circle-info me-1"></i> Notice
            </button>
        </div>
        <div class="em-panel-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">{{ $fields['NOTICE_PERIOD_RECIPIENTS']['label'] }}</label>
                    <textarea name="configs[NOTICE_PERIOD_RECIPIENTS]" class="form-control">{{ $val('NOTICE_PERIOD_RECIPIENTS') }}</textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label">{{ $fields['NOTICE_PERIOD_CC']['label'] }}</label>
                    <textarea name="configs[NOTICE_PERIOD_CC]" class="form-control">{{ $val('NOTICE_PERIOD_CC') }}</textarea>
                </div>
            </div>
        </div>
        <div class="em-panel-foot">
            <button type="submit" class="btn btn-save-section"><i class="fa-solid fa-floppy-disk me-1"></i> Save notice period</button>
        </div>
    </form>

    {{-- Reminders --}}
    <form method="POST" action="{{ route('settings.email.update') }}" id="section-reminders" class="em-panel">
        @csrf @method('PUT')
        <input type="hidden" name="section" value="reminders">
        <div class="em-panel-head">
            <div>
                <h5><i class="fa-solid fa-clock me-1" style="color:var(--accent)"></i> Reminders</h5>
            </div>
            <button type="button" class="btn btn-notice" data-bs-toggle="modal" data-bs-target="#notice-reminders">
                <i class="fa-solid fa-circle-info me-1"></i> Notice
            </button>
        </div>
        <div class="em-panel-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">{{ $fields['EVENING_REMINDER_EMAIL']['label'] }}</label>
                    <input type="email" name="configs[EVENING_REMINDER_EMAIL]" class="form-control" value="{{ $val('EVENING_REMINDER_EMAIL') }}">
                    <div class="help">{{ $fields['EVENING_REMINDER_EMAIL']['help'] }}</div>
                </div>
            </div>
        </div>
        <div class="em-panel-foot">
            <button type="submit" class="btn btn-save-section"><i class="fa-solid fa-floppy-disk me-1"></i> Save reminders</button>
        </div>
    </form>

    {{-- Test --}}
    <div class="em-panel" id="section-test">
        <div class="em-panel-head">
            <div>
                <h5><i class="fa-solid fa-paper-plane me-1" style="color:var(--accent)"></i> Send test email</h5>
            </div>
            <button type="button" class="btn btn-notice" data-bs-toggle="modal" data-bs-target="#notice-test">
                <i class="fa-solid fa-circle-info me-1"></i> Notice
            </button>
        </div>
        <div class="em-panel-body">
            <form method="POST" action="{{ route('settings.email.test') }}" class="row g-2 align-items-end">
                @csrf
                <div class="col-md-5">
                    <label class="form-label">Recipient</label>
                    <input type="email" name="recipient" class="form-control" required placeholder="you@example.com">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Send as</label>
                    <select name="from_mode" class="form-select">
                        <option value="default">Default From (notifications)</option>
                        <option value="docs">Documents / official From</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <button class="btn btn-outline-primary w-100">Send test</button>
                </div>
            </form>
        </div>
    </div>
</div>

@foreach($notices as $id => $notice)
<div class="modal fade" id="notice-{{ $id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content" style="border:0;border-radius:16px;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold">
                    <i class="fa-solid fa-circle-info me-1" style="color:#f59e0b;"></i> Notice — {{ $notice['title'] }}
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body em-wrap em-notice-body">
                <p><strong>What is this?</strong></p>
                <p>{{ $notice['what'] }}</p>
                <p><strong>How it is used:</strong></p>
                <ul>
                    @foreach($notice['points'] as $point)
                        <li>{!! $point !!}</li>
                    @endforeach
                </ul>
                @if(!empty($notice['tip']))
                    <p class="mb-0" style="font-size:.85rem;color:#6b7c93;">Tip: {{ $notice['tip'] }}</p>
                @endif
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endforeach
@endsection
