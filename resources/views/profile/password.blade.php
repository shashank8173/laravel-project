@extends('layouts.app')

@section('title', 'Change Password')
@section('heading', 'Change Password')

@section('page_actions')
<button type="button" class="btn btn-notice" data-bs-toggle="modal" data-bs-target="#passwordNoticeModal">
    <i class="fa-solid fa-circle-info me-1"></i> Notice
</button>
@endsection

@push('styles')
<style>
    .cp-wrap { --ink:#0f2744; --muted:#6b7c93; --line:#e8eef5; --soft:#f4f7fb; --accent:#ff9b44; }
    .cp-wrap .cp-panel {
        background:#fff; border:1px solid var(--line); border-radius:18px; overflow:hidden; height:100%;
    }
    .cp-wrap .cp-panel-head {
        display:flex; justify-content:space-between; align-items:flex-start; gap:1rem; flex-wrap:wrap;
        padding:1rem 1.15rem; border-bottom:1px solid var(--line);
        background:linear-gradient(180deg,#fff,#fafbfd);
    }
    .cp-wrap .cp-panel-head h5 { margin:0; font-weight:750; color:var(--ink); font-size:1rem; }
    .cp-wrap .cp-panel-head .sub { font-size:.78rem; color:var(--muted); display:block; margin-top:.15rem; }
    .cp-wrap .cp-panel-body { padding:1.15rem; }
    .cp-wrap .cp-panel-foot {
        padding:.85rem 1.15rem; border-top:1px solid var(--line); background:#fafbfd;
        display:flex; justify-content:flex-end; gap:.5rem; flex-wrap:wrap;
    }
    .cp-wrap .form-label { font-size:.8rem; font-weight:600; color:var(--muted); }
    .cp-wrap .help { font-size:.75rem; color:var(--muted); margin-top:.25rem; }
    .cp-wrap .cp-metric {
        border:1px solid var(--line); border-radius:14px; background:#fff; padding:1rem 1.05rem; height:100%;
        position:relative; overflow:hidden;
    }
    .cp-wrap .cp-metric::before { content:""; position:absolute; left:0; top:0; bottom:0; width:4px; background:var(--accent); }
    .cp-wrap .cp-metric.is-blue::before { background:#2563eb; }
    .cp-wrap .cp-metric.is-green::before { background:#16a34a; }
    .cp-wrap .cp-metric .k { font-size:.68rem; text-transform:uppercase; letter-spacing:.04em; color:var(--muted); font-weight:700; }
    .cp-wrap .cp-metric .v { font-size:1rem; font-weight:750; color:var(--ink); margin:.25rem 0 0; line-height:1.25; }
    .cp-wrap .cp-tip {
        display:flex; gap:.75rem; padding:.85rem 0; border-bottom:1px solid var(--line);
    }
    .cp-wrap .cp-tip:last-child { border-bottom:0; padding-bottom:0; }
    .cp-wrap .cp-tip-icon {
        width:34px; height:34px; border-radius:10px; display:inline-flex; align-items:center; justify-content:center;
        background:#fff7ed; color:#9a3412; flex-shrink:0;
    }
    .cp-wrap .cp-tip .t { font-weight:700; color:var(--ink); font-size:.9rem; margin:0 0 .15rem; }
    .cp-wrap .cp-tip .d { font-size:.78rem; color:var(--muted); margin:0; }
    .cp-wrap .input-group .btn-eye {
        border:1px solid var(--bs-border-color); background:#fff; color:var(--muted);
    }
    .cp-wrap .input-group .btn-eye:hover { color:var(--ink); background:var(--soft); }
    .cp-wrap .btn-save {
        background:var(--accent); border-color:var(--accent); color:#fff; font-weight:700;
        border-radius:10px; padding:.45rem 1.1rem;
    }
    .cp-wrap .btn-save:hover { filter:brightness(.96); color:#fff; }
    .btn-notice {
        border:1px solid #f59e0b; color:#92400e; background:#fffbeb; font-weight:700; border-radius:999px;
        padding:.35rem .9rem; font-size:.8rem;
    }
    .btn-notice:hover { background:#fef3c7; color:#78350f; border-color:#d97706; }
</style>
@endpush

@section('content')
@php($user = auth()->user())
<div class="cp-wrap">
    <div class="row g-3 mb-3">
        <div class="col-md-4">
            <div class="cp-metric">
                <div class="k">Account</div>
                <p class="v">{{ $user?->full_name ?: '—' }}</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="cp-metric is-blue">
                <div class="k">Login email / ID</div>
                <p class="v">{{ $user?->office_email ?: ($user?->email ?: 'Employee #'.$user?->id) }}</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="cp-metric is-green">
                <div class="k">Minimum length</div>
                <p class="v">4 characters</p>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-7">
            <form method="POST" action="{{ route('password.update') }}" class="cp-panel" id="changePasswordForm" autocomplete="off">
                @csrf
                @method('PUT')
                <div class="cp-panel-head">
                    <div>
                        <h5><i class="fa-solid fa-key me-1" style="color:var(--accent)"></i> Update password</h5>
                        <span class="sub">Enter your current password, then set a new one.</span>
                    </div>
                </div>
                <div class="cp-panel-body">
                    @if(session('success'))
                        <div class="alert alert-success py-2">{{ session('success') }}</div>
                    @endif

                    <div class="mb-3">
                        <label class="form-label">Current password</label>
                        <div class="input-group">
                            <input type="password" name="old_password" id="oldPassword" class="form-control @error('old_password') is-invalid @enderror" required>
                            <button type="button" class="btn btn-eye" data-toggle-pass="#oldPassword" aria-label="Show password">
                                <i class="fa-solid fa-eye"></i>
                            </button>
                        </div>
                        @error('old_password')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">New password</label>
                        <div class="input-group">
                            <input type="password" name="new_password" id="newPassword" class="form-control @error('new_password') is-invalid @enderror" minlength="4" required>
                            <button type="button" class="btn btn-eye" data-toggle-pass="#newPassword" aria-label="Show password">
                                <i class="fa-solid fa-eye"></i>
                            </button>
                        </div>
                        <div class="help">Use at least 4 characters. Prefer a mix of letters and numbers.</div>
                        @error('new_password')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-1">
                        <label class="form-label">Confirm new password</label>
                        <div class="input-group">
                            <input type="password" name="new_password_confirmation" id="confirmPassword" class="form-control" minlength="4" required>
                            <button type="button" class="btn btn-eye" data-toggle-pass="#confirmPassword" aria-label="Show password">
                                <i class="fa-solid fa-eye"></i>
                            </button>
                        </div>
                        <div class="help" id="matchHint"></div>
                    </div>
                </div>
                <div class="cp-panel-foot">
                    <a href="{{ route('profile.show') }}" class="btn btn-outline-secondary">Back to profile</a>
                    <button class="btn btn-save">Update password</button>
                </div>
            </form>
        </div>

        <div class="col-lg-5">
            <div class="cp-panel">
                <div class="cp-panel-head">
                    <div>
                        <h5><i class="fa-solid fa-shield-halved me-1" style="color:var(--accent)"></i> Security tips</h5>
                        <span class="sub">Keep your account safe.</span>
                    </div>
                </div>
                <div class="cp-panel-body">
                    <div class="cp-tip">
                        <span class="cp-tip-icon"><i class="fa-solid fa-lock"></i></span>
                        <div>
                            <p class="t">Don’t reuse passwords</p>
                            <p class="d">Avoid using the same password on other sites or tools.</p>
                        </div>
                    </div>
                    <div class="cp-tip">
                        <span class="cp-tip-icon"><i class="fa-solid fa-user-secret"></i></span>
                        <div>
                            <p class="t">Keep it private</p>
                            <p class="d">Never share your HRM login with anyone, including teammates.</p>
                        </div>
                    </div>
                    <div class="cp-tip">
                        <span class="cp-tip-icon"><i class="fa-solid fa-rotate"></i></span>
                        <div>
                            <p class="t">Change regularly</p>
                            <p class="d">Update your password if you suspect someone else used your account.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="passwordNoticeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border:0;border-radius:16px;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold">Password notice</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="color:#334155;font-size:.92rem;">
                <p>Use this page to update your HRM login password.</p>
                <ul class="mb-0 ps-3">
                    <li class="mb-2">Current password is required to confirm it’s you.</li>
                    <li class="mb-2">New password must be at least 4 characters and must match confirmation.</li>
                    <li>After updating, use the new password on your next login.</li>
                </ul>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn add-btn" data-bs-dismiss="modal">Got it</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-toggle-pass]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const input = document.querySelector(btn.getAttribute('data-toggle-pass'));
            if (!input) return;
            const show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            btn.innerHTML = show ? '<i class="fa-solid fa-eye-slash"></i>' : '<i class="fa-solid fa-eye"></i>';
        });
    });

    const newPass = document.getElementById('newPassword');
    const confirmPass = document.getElementById('confirmPassword');
    const hint = document.getElementById('matchHint');

    function checkMatch() {
        if (!confirmPass.value) {
            hint.textContent = '';
            hint.className = 'help';
            return;
        }
        if (newPass.value === confirmPass.value) {
            hint.textContent = 'Passwords match.';
            hint.className = 'help text-success';
        } else {
            hint.textContent = 'Passwords do not match yet.';
            hint.className = 'help text-danger';
        }
    }

    newPass.addEventListener('input', checkMatch);
    confirmPass.addEventListener('input', checkMatch);
});
</script>
@endpush
