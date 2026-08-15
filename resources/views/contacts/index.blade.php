@extends('layouts.app')

@section('title', 'Contacts')
@section('heading', 'Employee Contacts')

@section('page_actions')
<button type="button" class="btn btn-notice" data-bs-toggle="modal" data-bs-target="#contactsNoticeModal">
    <i class="fa-solid fa-circle-info me-1"></i> Notice
</button>
@endsection

@push('styles')
<style>
    .ct-wrap { --ink:#0f2744; --muted:#6b7c93; --line:#e8eef5; --soft:#f4f7fb; --accent:#ff9b44; }
    .ct-wrap .ct-panel { background:#fff; border:1px solid var(--line); border-radius:18px; overflow:hidden; }
    .ct-wrap .ct-panel-head {
        display:flex; justify-content:space-between; align-items:center; gap:1rem; flex-wrap:wrap;
        padding:1rem 1.15rem; border-bottom:1px solid var(--line);
        background:linear-gradient(180deg,#fff,#fafbfd);
    }
    .ct-wrap .ct-panel-head h5 { margin:0; font-weight:750; color:var(--ink); }
    .ct-wrap .ct-panel-body { padding:1.15rem; }
    .ct-wrap .ct-metric {
        border:1px solid var(--line); border-radius:14px; background:#fff; padding:1rem 1.05rem; height:100%;
        position:relative; overflow:hidden;
    }
    .ct-wrap .ct-metric::before { content:""; position:absolute; left:0; top:0; bottom:0; width:4px; background:var(--accent); }
    .ct-wrap .ct-metric.is-blue::before { background:#2563eb; }
    .ct-wrap .ct-metric.is-green::before { background:#16a34a; }
    .ct-wrap .ct-metric.is-purple::before { background:#7c3aed; }
    .ct-wrap .ct-metric .k { font-size:.68rem; text-transform:uppercase; letter-spacing:.04em; color:var(--muted); font-weight:700; }
    .ct-wrap .ct-metric .v { font-size:1.35rem; font-weight:800; color:var(--ink); margin:.15rem 0 0; line-height:1.1; }
    .ct-wrap .form-label { font-size:.8rem; font-weight:600; color:var(--muted); }
    .ct-wrap .table > :not(caption) > * > * { vertical-align:middle; }
    .ct-wrap .name { font-weight:750; color:var(--ink); margin:0; }
    .ct-wrap .sub { font-size:.78rem; color:var(--muted); }
    .ct-wrap .person { display:flex; align-items:center; gap:.75rem; }
    .ct-wrap .avatar {
        width:40px; height:40px; border-radius:50%; object-fit:cover; flex-shrink:0;
        background:#fff7ed; border:1px solid #ffe0c2;
    }
    .ct-wrap .avatar-fallback {
        width:40px; height:40px; border-radius:50%; display:inline-flex; align-items:center; justify-content:center;
        background:#fff7ed; color:#9a3412; font-size:.78rem; font-weight:800; flex-shrink:0;
    }
    .ct-wrap .chip {
        display:inline-flex; align-items:center; gap:.35rem; padding:.28rem .55rem; border-radius:8px;
        background:var(--soft); color:var(--ink); font-size:.8rem; font-weight:600; text-decoration:none;
    }
    .ct-wrap .chip:hover { background:#fff7ed; color:#9a3412; }
    .ct-wrap .chip i { color:var(--accent); font-size:.75rem; }
    .ct-wrap .dept-pill {
        display:inline-flex; padding:.25rem .55rem; border-radius:999px; font-size:.72rem; font-weight:700;
        background:#eff6ff; color:#1d4ed8;
    }
    .btn-notice {
        border:1px solid #f59e0b; color:#92400e; background:#fffbeb; font-weight:700; border-radius:999px;
        padding:.35rem .9rem; font-size:.8rem;
    }
    .btn-notice:hover { background:#fef3c7; color:#78350f; border-color:#d97706; }
</style>
@endpush

@section('content')
@php
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
<div class="ct-wrap">
    <div class="row g-3 mb-3">
        <div class="col-6 col-lg-3">
            <div class="ct-metric">
                <div class="k">Active employees</div>
                <p class="v">{{ $stats['total'] }}</p>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="ct-metric is-blue">
                <div class="k">With mobile</div>
                <p class="v">{{ $stats['with_mobile'] }}</p>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="ct-metric is-green">
                <div class="k">With email</div>
                <p class="v">{{ $stats['with_email'] }}</p>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="ct-metric is-purple">
                <div class="k">Departments</div>
                <p class="v">{{ $stats['departments'] }}</p>
            </div>
        </div>
    </div>

    <div class="ct-panel mb-3">
        <div class="ct-panel-head">
            <h5><i class="fa-solid fa-filter me-1" style="color:var(--accent)"></i> Filters</h5>
            @if($q !== '' || $departmentId)
                <a href="{{ route('contacts.index') }}" class="btn btn-sm btn-outline-secondary">Clear</a>
            @endif
        </div>
        <div class="ct-panel-body">
            <form method="GET" action="{{ route('contacts.index') }}" class="row g-2 align-items-end">
                <div class="col-md-5">
                    <label class="form-label">Search</label>
                    <input type="search" name="q" value="{{ $q }}" class="form-control" placeholder="Name, email, or mobile">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Department</label>
                    <select name="department_id" class="form-select">
                        <option value="">All departments</option>
                        @foreach($departments as $dept)
                            <option value="{{ $dept->id }}" @selected((int) $departmentId === (int) $dept->id)>{{ $dept->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <button class="btn add-btn me-1">Apply</button>
                    <a href="{{ route('contacts.index') }}" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="ct-panel">
        <div class="ct-panel-head">
            <h5><i class="fa-solid fa-address-book me-1" style="color:var(--accent)"></i> Directory</h5>
            <span class="small text-muted">{{ $contacts->total() }} contact(s)</span>
        </div>
        <div class="table-responsive p-2">
            <table class="table align-middle mb-0">
                <thead>
                <tr>
                    <th>Employee</th>
                    <th>Contact</th>
                    <th>Department</th>
                    <th>Designation</th>
                    <th class="text-end">Quick actions</th>
                </tr>
                </thead>
                <tbody>
                @forelse($contacts as $c)
                    @php
                        $email = $c->office_email ?: $c->email;
                        $mobile = $c->mobile1 ?: $c->mobile2;
                    @endphp
                    <tr>
                        <td>
                            <div class="person">
                                <img src="{{ $c->profile_image_url }}" alt="" class="avatar"
                                     onerror="this.style.display='none'; this.nextElementSibling.style.display='inline-flex';">
                                <span class="avatar-fallback" style="display:none;">{{ $initials($c->full_name) }}</span>
                                <div>
                                    <p class="name">{{ $c->full_name }}</p>
                                    <div class="sub">ID #{{ $c->id }}</div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div>{{ $email ?: '—' }}</div>
                            <div class="sub">{{ $mobile ?: '' }}</div>
                            @if($c->office_email && $c->email && $c->office_email !== $c->email)
                                <div class="sub">Personal: {{ $c->email }}</div>
                            @endif
                        </td>
                        <td>
                            @if($c->department?->name)
                                <span class="dept-pill">{{ $c->department->name }}</span>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td>{{ $c->designation?->name ?: '—' }}</td>
                        <td class="text-end text-nowrap">
                            @if($email)
                                <a class="chip me-1" href="mailto:{{ $email }}" title="Send email">
                                    <i class="fa-solid fa-envelope"></i> Email
                                </a>
                            @endif
                            @if($mobile)
                                <a class="chip" href="tel:{{ preg_replace('/\s+/', '', $mobile) }}" title="Call">
                                    <i class="fa-solid fa-phone"></i> Call
                                </a>
                            @endif
                            @if(! $email && ! $mobile)
                                <span class="sub">—</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">No contacts found.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-3 border-top">{{ $contacts->links() }}</div>
    </div>
</div>

<div class="modal fade" id="contactsNoticeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border:0;border-radius:16px;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold">Contacts notice</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="color:#334155;font-size:.92rem;">
                <p>This directory lists active employees for quick internal contact.</p>
                <ul class="mb-0 ps-3">
                    <li class="mb-2">Search by name, email, or mobile number.</li>
                    <li class="mb-2">Filter by department to narrow the list.</li>
                    <li>Use Email / Call shortcuts when details are available.</li>
                </ul>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn add-btn" data-bs-dismiss="modal">Got it</button>
            </div>
        </div>
    </div>
</div>
@endsection
