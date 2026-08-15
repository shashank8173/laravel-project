@extends('layouts.app')

@section('title', 'Admin Logs')
@section('heading', 'Admin Login / Logout Logs')

@push('styles')
<style>
    .al-wrap { --ink:#0f2744; --muted:#6b7c93; --line:#e8eef5; --soft:#f4f7fb; --accent:#ff9b44; }
    .al-wrap .al-panel { background:#fff; border:1px solid var(--line); border-radius:18px; overflow:hidden; }
    .al-wrap .al-panel-head {
        display:flex; justify-content:space-between; align-items:center; gap:1rem; flex-wrap:wrap;
        padding:1rem 1.15rem; border-bottom:1px solid var(--line);
        background:linear-gradient(180deg,#fff,#fafbfd);
    }
    .al-wrap .al-panel-head h5 { margin:0; font-weight:750; color:var(--ink); }
    .al-wrap .al-panel-body { padding:1.15rem; }
    .al-wrap .al-metric {
        border:1px solid var(--line); border-radius:14px; background:#fff; padding:1rem 1.1rem; height:100%;
        position:relative; overflow:hidden;
    }
    .al-wrap .al-metric::before { content:""; position:absolute; left:0; top:0; bottom:0; width:4px; background:var(--accent); }
    .al-wrap .al-metric.is-login::before { background:#16a34a; }
    .al-wrap .al-metric.is-logout::before { background:#64748b; }
    .al-wrap .al-metric.is-filter::before { background:#2563eb; }
    .al-wrap .al-metric .k { font-size:.7rem; text-transform:uppercase; letter-spacing:.04em; color:var(--muted); font-weight:700; }
    .al-wrap .al-metric .v { font-size:1.45rem; font-weight:800; color:var(--ink); margin:0; line-height:1.1; }
    .al-wrap .form-label { font-size:.8rem; font-weight:600; color:var(--muted); }
    .al-wrap .table > :not(caption) > * > * { vertical-align:middle; }
    .al-wrap .al-pill {
        display:inline-flex; align-items:center; gap:.3rem; padding:.28rem .65rem; border-radius:999px;
        font-size:.72rem; font-weight:700; text-transform:capitalize;
    }
    .al-wrap .al-pill.login { background:#dcfce7; color:#15803d; }
    .al-wrap .al-pill.logout { background:#e2e8f0; color:#475569; }
    .al-wrap .al-pill.mail-ok { background:#dcfce7; color:#15803d; }
    .al-wrap .al-pill.mail-bad { background:#fee2e2; color:#b91c1c; }
    .al-wrap .al-pill.mail-na { background:#f1f5f9; color:#64748b; }
    .al-wrap .email { font-weight:650; color:var(--ink); }
    .al-wrap .sub { font-size:.78rem; color:var(--muted); }
    .al-wrap .ua { max-width:220px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
</style>
@endpush

@section('content')
@php
    $hasFilters = ($action || $start || $end || $q !== '');
@endphp
<div class="al-wrap">
    <div class="row g-3 mb-3">
        <div class="col-6 col-lg-3">
            <div class="al-metric">
                <div class="k">Total logs</div>
                <p class="v">{{ number_format($counts['all']) }}</p>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="al-metric is-login">
                <div class="k">Logins</div>
                <p class="v">{{ number_format($counts['login']) }}</p>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="al-metric is-logout">
                <div class="k">Logouts</div>
                <p class="v">{{ number_format($counts['logout']) }}</p>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="al-metric is-filter">
                <div class="k">Filtered</div>
                <p class="v">{{ number_format($counts['filtered']) }}</p>
            </div>
        </div>
    </div>

    <div class="al-panel mb-3">
        <div class="al-panel-head">
            <h5><i class="fa-solid fa-filter me-1" style="color:var(--accent)"></i> Filters</h5>
            @if($hasFilters)
                <a href="{{ route('logs.admin') }}" class="btn btn-sm btn-outline-secondary">Clear all</a>
            @endif
        </div>
        <div class="al-panel-body">
            <form method="GET" action="{{ route('logs.admin') }}" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Search</label>
                    <input type="search" name="q" value="{{ $q }}" class="form-control" placeholder="Email, admin ID, IP">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Action</label>
                    <select name="action" class="form-select">
                        <option value="">All</option>
                        <option value="login" @selected($action === 'login')>Login</option>
                        <option value="logout" @selected($action === 'logout')>Logout</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Start date</label>
                    <input type="date" name="start" value="{{ $start }}" class="form-control">
                </div>
                <div class="col-md-2">
                    <label class="form-label">End date</label>
                    <input type="date" name="end" value="{{ $end }}" class="form-control">
                </div>
                <div class="col-md-3">
                    <button class="btn add-btn me-1">Apply</button>
                    <a href="{{ route('logs.admin') }}" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="al-panel">
        <div class="al-panel-head">
            <h5><i class="fa-solid fa-clock-rotate-left me-1" style="color:var(--accent)"></i> Activity history</h5>
            <span class="small text-muted">{{ $logs->total() }} record(s)</span>
        </div>
        <div class="table-responsive p-2">
            <table class="table align-middle mb-0">
                <thead>
                <tr>
                    <th>#</th>
                    <th>Admin</th>
                    <th>Action</th>
                    <th>Timestamp</th>
                    <th>IP address</th>
                    <th>Browser / device</th>
                    <th>Email status</th>
                </tr>
                </thead>
                <tbody>
                @forelse($logs as $log)
                    @php
                        $act = strtolower((string) $log->action);
                        $mail = strtolower(trim((string) ($log->email_status ?? '')));
                        $mailClass = match (true) {
                            in_array($mail, ['success', 'sent', 'ok'], true) => 'mail-ok',
                            in_array($mail, ['failed', 'fail', 'error', 'danger'], true) => 'mail-bad',
                            default => 'mail-na',
                        };
                        $mailLabel = $mail !== '' ? $log->email_status : 'n/a';
                    @endphp
                    <tr>
                        <td class="text-muted">{{ $log->id }}</td>
                        <td>
                            <div class="email">{{ $log->email ?: '—' }}</div>
                            <div class="sub">Admin ID · {{ $log->admin_id }}</div>
                        </td>
                        <td>
                            <span class="al-pill {{ $act === 'login' ? 'login' : 'logout' }}">
                                <i class="fa-solid {{ $act === 'login' ? 'fa-right-to-bracket' : 'fa-right-from-bracket' }}"></i>
                                {{ $log->action }}
                            </span>
                        </td>
                        <td>
                            <div>{{ optional($log->timestamp)->format('d M Y') }}</div>
                            <div class="sub">{{ optional($log->timestamp)->format('h:i A') }}</div>
                        </td>
                        <td><code>{{ $log->ip_address ?: '—' }}</code></td>
                        <td>
                            <div class="ua sub" title="{{ $log->browser_info }}">
                                {{ $log->browser_info ? \Illuminate\Support\Str::limit($log->browser_info, 70) : '—' }}
                            </div>
                        </td>
                        <td><span class="al-pill {{ $mailClass }}">{{ $mailLabel }}</span></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">No admin logs found.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-3 border-top">{{ $logs->links() }}</div>
    </div>
</div>
@endsection
