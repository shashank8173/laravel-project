@extends('layouts.app')

@section('title', 'Assign Roles')
@section('heading', 'User Roles')

@section('page_actions')
<button type="button" class="btn btn-notice" data-bs-toggle="modal" data-bs-target="#rolesNoticeModal">
    <i class="fa-solid fa-circle-info me-1"></i> Notice
</button>
@endsection

@push('styles')
<style>
    .rl-wrap { --ink:#0f2744; --muted:#6b7c93; --line:#e8eef5; --soft:#f4f7fb; --accent:#ff9b44; }
    .rl-wrap .rl-panel { background:#fff; border:1px solid var(--line); border-radius:18px; overflow:hidden; }
    .rl-wrap .rl-panel-head {
        display:flex; justify-content:space-between; align-items:center; gap:1rem; flex-wrap:wrap;
        padding:1rem 1.15rem; border-bottom:1px solid var(--line);
        background:linear-gradient(180deg,#fff,#fafbfd);
    }
    .rl-wrap .rl-panel-head h5 { margin:0; font-weight:750; color:var(--ink); }
    .rl-wrap .rl-panel-body { padding:1.15rem; }
    .rl-wrap .rl-metric {
        border:1px solid var(--line); border-radius:14px; background:#fff; padding:1rem 1.05rem; height:100%;
        position:relative; overflow:hidden; text-decoration:none; display:block; color:inherit;
        transition: border-color .15s ease, transform .15s ease;
    }
    .rl-wrap .rl-metric:hover { border-color:#ffd0a8; transform:translateY(-1px); }
    .rl-wrap .rl-metric.is-active { border-color:var(--accent); box-shadow:0 0 0 2px rgba(255,155,68,.15); }
    .rl-wrap .rl-metric::before { content:""; position:absolute; left:0; top:0; bottom:0; width:4px; background:var(--accent); }
    .rl-wrap .rl-metric.is-blue::before { background:#2563eb; }
    .rl-wrap .rl-metric.is-green::before { background:#16a34a; }
    .rl-wrap .rl-metric.is-purple::before { background:#7c3aed; }
    .rl-wrap .rl-metric .k { font-size:.68rem; text-transform:uppercase; letter-spacing:.04em; color:var(--muted); font-weight:700; }
    .rl-wrap .rl-metric .v { font-size:1.35rem; font-weight:800; color:var(--ink); margin:.15rem 0 0; line-height:1.1; }
    .rl-wrap .form-label { font-size:.8rem; font-weight:600; color:var(--muted); }
    .rl-wrap .table > :not(caption) > * > * { vertical-align:middle; }
    .rl-wrap .name { font-weight:750; color:var(--ink); margin:0; }
    .rl-wrap .sub { font-size:.78rem; color:var(--muted); }
    .rl-wrap .person { display:flex; align-items:center; gap:.75rem; }
    .rl-wrap .avatar {
        width:40px; height:40px; border-radius:50%; object-fit:cover; flex-shrink:0;
        background:#fff7ed; border:1px solid #ffe0c2;
    }
    .rl-wrap .avatar-fallback {
        width:40px; height:40px; border-radius:50%; display:none; align-items:center; justify-content:center;
        background:#fff7ed; color:#9a3412; font-size:.78rem; font-weight:800; flex-shrink:0;
    }
    .rl-wrap .rl-pill {
        display:inline-flex; padding:.28rem .65rem; border-radius:999px; font-size:.72rem; font-weight:700; text-transform:capitalize;
    }
    .rl-wrap .role-user { background:#eef2ff; color:#4338ca; }
    .rl-wrap .role-admin { background:#dcfce7; color:#15803d; }
    .rl-wrap .role-super { background:#fef3c7; color:#b45309; }
    .btn-notice {
        border:1px solid #f59e0b; color:#92400e; background:#fffbeb; font-weight:700; border-radius:999px;
        padding:.35rem .9rem; font-size:.8rem;
    }
    .btn-notice:hover { background:#fef3c7; color:#78350f; border-color:#d97706; }
</style>
@endpush

@section('content')
@php
    $roleClass = fn ($r) => match (strtolower(trim((string) $r))) {
        'admin' => 'role-admin',
        'super admin' => 'role-super',
        default => 'role-user',
    };
    $initials = function (?string $name): string {
        $name = trim((string) $name);
        if ($name === '') {
            return '?';
        }
        $parts = preg_split('/\s+/', $name) ?: [];
        $a = strtoupper(substr($parts[0] ?? '', 0, 1));
        $b = strtoupper(substr($parts[1] ?? '', 0, 1));

        return $a.($b ?: '');
    };
@endphp
<div class="rl-wrap">
    <div class="row g-3 mb-3">
        <div class="col-6 col-lg-3">
            <a href="{{ route('roles.index', array_filter(['q' => $q ?: null])) }}"
               class="rl-metric {{ ! $role ? 'is-active' : '' }}">
                <div class="k">All users</div>
                <p class="v">{{ $stats['all'] }}</p>
            </a>
        </div>
        <div class="col-6 col-lg-3">
            <a href="{{ route('roles.index', array_filter(['q' => $q ?: null, 'role' => 'user'])) }}"
               class="rl-metric is-blue {{ $role === 'user' ? 'is-active' : '' }}">
                <div class="k">User</div>
                <p class="v">{{ $stats['user'] }}</p>
            </a>
        </div>
        <div class="col-6 col-lg-3">
            <a href="{{ route('roles.index', array_filter(['q' => $q ?: null, 'role' => 'admin'])) }}"
               class="rl-metric is-green {{ $role === 'admin' ? 'is-active' : '' }}">
                <div class="k">Admin</div>
                <p class="v">{{ $stats['admin'] }}</p>
            </a>
        </div>
        <div class="col-6 col-lg-3">
            <a href="{{ route('roles.index', array_filter(['q' => $q ?: null, 'role' => 'super admin'])) }}"
               class="rl-metric is-purple {{ $role === 'super admin' ? 'is-active' : '' }}">
                <div class="k">Super admin</div>
                <p class="v">{{ $stats['super admin'] }}</p>
            </a>
        </div>
    </div>

    <div class="rl-panel mb-3">
        <div class="rl-panel-head">
            <h5><i class="fa-solid fa-filter me-1" style="color:var(--accent)"></i> Filters</h5>
            @if($q !== '' || $role)
                <a href="{{ route('roles.index') }}" class="btn btn-sm btn-outline-secondary">Clear</a>
            @endif
        </div>
        <div class="rl-panel-body">
            <form method="GET" action="{{ route('roles.index') }}" class="row g-2 align-items-end">
                <div class="col-md-5">
                    <label class="form-label">Search</label>
                    <input type="search" name="q" value="{{ $q }}" class="form-control" placeholder="Name, email, or mobile">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Role</label>
                    <select name="role" class="form-select">
                        <option value="">All roles</option>
                        @foreach($roles as $r)
                            <option value="{{ $r }}" @selected($role === $r)>{{ ucwords($r) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <button class="btn add-btn me-1">Apply</button>
                    <a href="{{ route('roles.index') }}" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="rl-panel">
        <div class="rl-panel-head">
            <h5><i class="fa-solid fa-user-shield me-1" style="color:var(--accent)"></i> Role assignment</h5>
            <span class="small text-muted">{{ $employees->total() }} employee(s)</span>
        </div>
        <div class="table-responsive p-2">
            <table class="table align-middle mb-0">
                <thead>
                <tr>
                    <th>Employee</th>
                    <th>Contact</th>
                    <th>Department</th>
                    <th>Current role</th>
                    <th class="text-end">Update role</th>
                </tr>
                </thead>
                <tbody>
                @forelse($employees as $employee)
                    <tr>
                        <td>
                            <div class="person">
                                <img src="{{ $employee->profile_image_url }}" alt="" class="avatar"
                                     onerror="this.style.display='none'; this.nextElementSibling.style.display='inline-flex';">
                                <span class="avatar-fallback">{{ $initials($employee->full_name) }}</span>
                                <div>
                                    <p class="name">{{ $employee->full_name }}</p>
                                    <div class="sub">ID #{{ $employee->id }} · {{ $employee->designation?->name ?: '—' }}</div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div>{{ $employee->office_email ?: ($employee->email ?: '—') }}</div>
                            <div class="sub">{{ $employee->mobile1 ?: '' }}</div>
                        </td>
                        <td>{{ $employee->department?->name ?: '—' }}</td>
                        <td>
                            <span class="rl-pill {{ $roleClass($employee->role) }}">{{ $employee->role ?: 'user' }}</span>
                        </td>
                        <td class="text-end">
                            <form method="POST" action="{{ route('roles.update', $employee) }}" class="d-inline-flex gap-2 align-items-center justify-content-end">
                                @csrf @method('PUT')
                                <select name="role" class="form-select form-select-sm" style="width:140px">
                                    @foreach($roles as $r)
                                        <option value="{{ $r }}" @selected(strtolower(trim((string) $employee->role)) === $r)>{{ ucwords($r) }}</option>
                                    @endforeach
                                </select>
                                <button class="btn btn-sm add-btn">Save</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">No employees found.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-3 border-top">{{ $employees->links() }}</div>
    </div>
</div>

<div class="modal fade" id="rolesNoticeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border:0;border-radius:16px;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold">Roles notice</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="color:#334155;font-size:.92rem;">
                <p>Assign access levels for active employees.</p>
                <ul class="mb-0 ps-3">
                    <li class="mb-2"><strong>User</strong> — standard employee access</li>
                    <li class="mb-2"><strong>Admin</strong> — HR / management modules</li>
                    <li class="mb-2"><strong>Super admin</strong> — developer tools (roles, email settings, logs)</li>
                    <li>The last admin cannot be demoted to user.</li>
                </ul>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn add-btn" data-bs-dismiss="modal">Got it</button>
            </div>
        </div>
    </div>
</div>
@endsection
