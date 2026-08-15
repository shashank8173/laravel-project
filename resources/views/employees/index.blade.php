@extends('layouts.app')

@section('title', 'Employees')
@section('heading', 'Employees')

@section('page_actions')
<a href="{{ route('employees.archived') }}" class="btn btn-outline-secondary me-1">
    <i class="fa-solid fa-box-archive me-1"></i> Former
</a>
<a href="{{ route('employees.create') }}" class="btn add-btn">
    <i class="fa-solid fa-plus"></i> Add Employee
</a>
@endsection

@push('styles')
<style>
    .emp-wrap { --ink:#0f2744; --muted:#6b7c93; --line:#e8eef5; --soft:#f4f7fb; --accent:#ff9b44; }
    .emp-wrap .emp-panel { background:#fff; border:1px solid var(--line); border-radius:18px; overflow:hidden; }
    .emp-wrap .emp-panel-head {
        display:flex; justify-content:space-between; align-items:center; gap:1rem; flex-wrap:wrap;
        padding:1rem 1.15rem; border-bottom:1px solid var(--line);
        background:linear-gradient(180deg,#fff,#fafbfd);
    }
    .emp-wrap .emp-panel-head h5 { margin:0; font-weight:750; color:var(--ink); }
    .emp-wrap .emp-panel-body { padding:1.15rem; }
    .emp-wrap .emp-metric {
        border:1px solid var(--line); border-radius:14px; background:#fff; padding:1rem 1.05rem; height:100%;
        position:relative; overflow:hidden;
    }
    .emp-wrap .emp-metric::before { content:""; position:absolute; left:0; top:0; bottom:0; width:4px; background:var(--accent); }
    .emp-wrap .emp-metric.is-green::before { background:#16a34a; }
    .emp-wrap .emp-metric.is-amber::before { background:#f59e0b; }
    .emp-wrap .emp-metric.is-blue::before { background:#2563eb; }
    .emp-wrap .emp-metric .k { font-size:.68rem; text-transform:uppercase; letter-spacing:.04em; color:var(--muted); font-weight:700; }
    .emp-wrap .emp-metric .v { font-size:1.35rem; font-weight:800; color:var(--ink); margin:.15rem 0 0; line-height:1.1; }
    .emp-wrap .form-label { font-size:.8rem; font-weight:600; color:var(--muted); }
    .emp-wrap .table > :not(caption) > * > * { vertical-align:middle; }
    .emp-wrap .name { font-weight:750; color:var(--ink); margin:0; }
    .emp-wrap .name a { color:inherit; text-decoration:none; }
    .emp-wrap .name a:hover { color:#9a3412; }
    .emp-wrap .sub { font-size:.78rem; color:var(--muted); }
    .emp-wrap .person { display:flex; align-items:center; gap:.75rem; }
    .emp-wrap .avatar {
        width:42px; height:42px; border-radius:50%; object-fit:cover; flex-shrink:0;
        background:#fff7ed; border:1px solid #ffe0c2;
    }
    .emp-wrap .avatar-fallback {
        width:42px; height:42px; border-radius:50%; display:none; align-items:center; justify-content:center;
        background:#fff7ed; color:#9a3412; font-size:.78rem; font-weight:800; flex-shrink:0;
    }
    .emp-wrap .emp-pill {
        display:inline-flex; padding:.28rem .65rem; border-radius:999px; font-size:.72rem; font-weight:700; text-transform:capitalize;
    }
    .emp-wrap .role-user { background:#eef2ff; color:#4338ca; }
    .emp-wrap .role-admin { background:#dcfce7; color:#15803d; }
    .emp-wrap .role-super { background:#fef3c7; color:#b45309; }
    .emp-wrap .dept-pill {
        display:inline-flex; padding:.22rem .5rem; border-radius:999px; font-size:.7rem; font-weight:700;
        background:#eff6ff; color:#1d4ed8;
    }
    .emp-wrap .id-chip {
        display:inline-flex; padding:.22rem .5rem; border-radius:8px; background:var(--soft);
        color:var(--ink); font-size:.78rem; font-weight:700; font-variant-numeric:tabular-nums;
    }
    .emp-wrap .st-dot {
        width:8px; height:8px; border-radius:50%; display:inline-block; margin-right:.35rem;
        background:#16a34a;
    }
    .emp-wrap .st-dot.is-off { background:#94a3b8; }
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
<div class="emp-wrap">
    <div class="row g-3 mb-3">
        <div class="col-6 col-lg-3">
            <div class="emp-metric">
                <div class="k">Total employees</div>
                <p class="v">{{ $stats['total'] }}</p>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="emp-metric is-green">
                <div class="k">Active</div>
                <p class="v">{{ $stats['active'] }}</p>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="emp-metric is-amber">
                <div class="k">Inactive</div>
                <p class="v">{{ $stats['inactive'] }}</p>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="emp-metric is-blue">
                <div class="k">Admins</div>
                <p class="v">{{ $stats['admins'] }}</p>
            </div>
        </div>
    </div>

    <div class="emp-panel mb-3">
        <div class="emp-panel-head">
            <h5><i class="fa-solid fa-filter me-1" style="color:var(--accent)"></i> Filters</h5>
            @if($q !== '' || $designationId || $departmentId || $role)
                <a href="{{ route('employees.index') }}" class="btn btn-sm btn-outline-secondary">Clear</a>
            @endif
        </div>
        <div class="emp-panel-body">
            <form method="GET" action="{{ route('employees.index') }}" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Search</label>
                    <input type="search" name="q" value="{{ $q }}" class="form-control" placeholder="Name, email, emp id, mobile">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Department</label>
                    <select name="department_id" class="form-select">
                        <option value="">All</option>
                        @foreach($departments as $dept)
                            <option value="{{ $dept->id }}" @selected((int) $departmentId === (int) $dept->id)>{{ $dept->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Designation</label>
                    <select name="designation_id" class="form-select">
                        <option value="">All</option>
                        @foreach($designations as $d)
                            <option value="{{ $d->id }}" @selected((int) $designationId === (int) $d->id)>{{ $d->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Role</label>
                    <select name="role" class="form-select">
                        <option value="">All</option>
                        @foreach(['user', 'admin', 'super admin'] as $r)
                            <option value="{{ $r }}" @selected($role === $r)>{{ ucwords($r) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <button class="btn add-btn me-1">Apply</button>
                    <a href="{{ route('employees.index') }}" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="emp-panel">
        <div class="emp-panel-head">
            <h5><i class="fa-solid fa-users me-1" style="color:var(--accent)"></i> Employee directory</h5>
            <span class="small text-muted">{{ $employees->total() }} record(s)</span>
        </div>
        <div class="table-responsive p-2">
            <table class="table align-middle mb-0">
                <thead>
                <tr>
                    <th>Employee</th>
                    <th>ID</th>
                    <th>Contact</th>
                    <th>Department</th>
                    <th>Role</th>
                    <th class="text-end">Actions</th>
                </tr>
                </thead>
                <tbody>
                @forelse($employees as $employee)
                    @php
                        $active = (int) $employee->status === 1;
                        $email = $employee->office_email ?: $employee->email;
                    @endphp
                    <tr>
                        <td>
                            <div class="person">
                                <img src="{{ $employee->profile_image_url }}" alt="" class="avatar"
                                     onerror="this.style.display='none'; this.nextElementSibling.style.display='inline-flex';">
                                <span class="avatar-fallback">{{ $initials($employee->full_name) }}</span>
                                <div>
                                    <p class="name">
                                        <a href="{{ route('employees.show', $employee) }}">{{ $employee->full_name }}</a>
                                    </p>
                                    <div class="sub">
                                        <span class="st-dot {{ $active ? '' : 'is-off' }}"></span>
                                        {{ $active ? 'Active' : 'Inactive' }}
                                        · {{ $employee->designation?->name ?: '—' }}
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td><span class="id-chip">{{ $employee->emp_id ?: '#'.$employee->id }}</span></td>
                        <td>
                            <div>{{ $email ?: '—' }}</div>
                            <div class="sub">{{ $employee->mobile1 ?: '' }}</div>
                        </td>
                        <td>
                            @if($employee->department?->name)
                                <span class="dept-pill">{{ $employee->department->name }}</span>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td>
                            <span class="emp-pill {{ $roleClass($employee->role) }}">{{ $employee->role ?: 'user' }}</span>
                        </td>
                        <td class="text-end text-nowrap">
                            <a href="{{ route('employees.show', $employee) }}" class="btn btn-sm btn-outline-primary">View</a>
                            <a href="{{ route('employees.edit', $employee) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">No employees found.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-3 border-top">{{ $employees->links() }}</div>
    </div>
</div>
@endsection
