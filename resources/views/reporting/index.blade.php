@extends('layouts.app')

@section('title', 'Reporting Managers')
@section('heading', 'Reporting Managers')

@section('page_actions')
<button type="button" class="btn add-btn" data-bs-toggle="modal" data-bs-target="#assignModal">
    <i class="fa-solid fa-plus"></i> Assign manager
</button>
@endsection

@push('styles')
<style>
    .rm-wrap { --ink:#0f2744; --muted:#6b7c93; --line:#e8eef5; --soft:#f4f7fb; --accent:#ff9b44; }
    .rm-wrap .rm-panel { background:#fff; border:1px solid var(--line); border-radius:18px; overflow:hidden; }
    .rm-wrap .rm-panel-head {
        display:flex; justify-content:space-between; align-items:center; gap:1rem; flex-wrap:wrap;
        padding:1rem 1.15rem; border-bottom:1px solid var(--line);
        background:linear-gradient(180deg,#fff,#fafbfd);
    }
    .rm-wrap .rm-panel-head h5 { margin:0; font-weight:750; color:var(--ink); }
    .rm-wrap .rm-panel-body { padding:1.15rem; }
    .rm-wrap .rm-metric {
        border:1px solid var(--line); border-radius:14px; background:#fff; padding:1rem 1.05rem; height:100%;
        position:relative; overflow:hidden;
    }
    .rm-wrap .rm-metric::before { content:""; position:absolute; left:0; top:0; bottom:0; width:4px; background:var(--accent); }
    .rm-wrap .rm-metric.is-blue::before { background:#2563eb; }
    .rm-wrap .rm-metric.is-green::before { background:#16a34a; }
    .rm-wrap .rm-metric.is-purple::before { background:#7c3aed; }
    .rm-wrap .rm-metric.is-amber::before { background:#f59e0b; }
    .rm-wrap .rm-metric .k { font-size:.68rem; text-transform:uppercase; letter-spacing:.04em; color:var(--muted); font-weight:700; }
    .rm-wrap .rm-metric .v { font-size:1.35rem; font-weight:800; color:var(--ink); margin:.15rem 0 0; line-height:1.1; }
    .rm-wrap .form-label { font-size:.8rem; font-weight:600; color:var(--muted); }
    .rm-wrap .table > :not(caption) > * > * { vertical-align:middle; }
    .rm-wrap .name { font-weight:750; color:var(--ink); margin:0; }
    .rm-wrap .sub { font-size:.78rem; color:var(--muted); }
    .rm-wrap .rm-pill {
        display:inline-flex; padding:.28rem .65rem; border-radius:999px; font-size:.72rem; font-weight:700;
    }
    .rm-wrap .type-primary { background:#dbeafe; color:#1d4ed8; }
    .rm-wrap .type-secondary { background:#fef3c7; color:#b45309; }
    .rm-wrap .avatar {
        width:34px; height:34px; border-radius:50%; display:inline-flex; align-items:center; justify-content:center;
        background:#fff7ed; color:#9a3412; font-size:.75rem; font-weight:800; flex-shrink:0;
    }
    .rm-wrap .person { display:flex; align-items:center; gap:.65rem; }
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
<div class="rm-wrap">
    <div class="row g-3 mb-3">
        <div class="col-6 col-lg">
            <div class="rm-metric">
                <div class="k">Assignments</div>
                <p class="v">{{ $stats['assignments'] }}</p>
            </div>
        </div>
        <div class="col-6 col-lg">
            <div class="rm-metric is-blue">
                <div class="k">Employees covered</div>
                <p class="v">{{ $stats['employees'] }}</p>
            </div>
        </div>
        <div class="col-6 col-lg">
            <div class="rm-metric is-purple">
                <div class="k">Managers</div>
                <p class="v">{{ $stats['managers'] }}</p>
            </div>
        </div>
        <div class="col-6 col-lg">
            <div class="rm-metric is-green">
                <div class="k">Primary</div>
                <p class="v">{{ $stats['primary'] }}</p>
            </div>
        </div>
        <div class="col-6 col-lg">
            <div class="rm-metric is-amber">
                <div class="k">Secondary</div>
                <p class="v">{{ $stats['secondary'] }}</p>
            </div>
        </div>
    </div>

    <div class="rm-panel mb-3">
        <div class="rm-panel-head">
            <h5><i class="fa-solid fa-filter me-1" style="color:var(--accent)"></i> Filters</h5>
            @if($q !== '' || $type)
                <a href="{{ route('reporting.index') }}" class="btn btn-sm btn-outline-secondary">Clear</a>
            @endif
        </div>
        <div class="rm-panel-body">
            <form method="GET" action="{{ route('reporting.index') }}" class="row g-2 align-items-end">
                <div class="col-md-5">
                    <label class="form-label">Search</label>
                    <input type="search" name="q" value="{{ $q }}" class="form-control" placeholder="Employee or manager name">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Type</label>
                    <select name="type" class="form-select">
                        <option value="">All</option>
                        <option value="Primary" @selected($type === 'Primary')>Primary</option>
                        <option value="Secondary" @selected($type === 'Secondary')>Secondary</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <button class="btn add-btn me-1">Apply</button>
                    <a href="{{ route('reporting.index') }}" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="rm-panel">
        <div class="rm-panel-head">
            <h5><i class="fa-solid fa-sitemap me-1" style="color:var(--accent)"></i> Assignments</h5>
            <span class="small text-muted">{{ $rows->total() }} record(s)</span>
        </div>
        <div class="table-responsive p-2">
            <table class="table align-middle mb-0">
                <thead>
                <tr>
                    <th>Employee</th>
                    <th>Reporting manager</th>
                    <th>Type</th>
                    <th>Assigned on</th>
                    <th class="text-end">Actions</th>
                </tr>
                </thead>
                <tbody>
                @forelse($rows as $row)
                    @php
                        $empName = $row->employee?->full_name ?: '—';
                        $mgrName = $row->manager?->full_name ?: '—';
                        $isPrimary = strcasecmp((string) $row->reporting_manager_type, 'Primary') === 0;
                    @endphp
                    <tr>
                        <td>
                            <div class="person">
                                <span class="avatar">{{ $initials($empName) }}</span>
                                <div>
                                    <p class="name">{{ $empName }}</p>
                                    <div class="sub">ID #{{ $row->employee_id }}</div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="person">
                                <span class="avatar" style="background:#eff6ff;color:#1d4ed8;">{{ $initials($mgrName) }}</span>
                                <div>
                                    <p class="name">{{ $mgrName }}</p>
                                    <div class="sub">ID #{{ $row->reporting_manager_id }}</div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="rm-pill {{ $isPrimary ? 'type-primary' : 'type-secondary' }}">
                                {{ $row->reporting_manager_type ?: '—' }}
                            </span>
                        </td>
                        <td>{{ optional($row->date)->format('d M Y') ?: '—' }}</td>
                        <td class="text-end">
                            <form method="POST" action="{{ route('reporting.destroy', $row) }}" class="d-inline"
                                  onsubmit="return confirm('Remove this reporting assignment?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger">Remove</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">No reporting assignments found.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-3 border-top">{{ $rows->links() }}</div>
    </div>
</div>

<div class="modal fade" id="assignModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border:0;border-radius:16px;">
            <form method="POST" action="{{ route('reporting.store') }}">
                @csrf
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold">Assign reporting manager</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body rm-wrap">
                    <div class="mb-3">
                        <label class="form-label">Employee</label>
                        <x-employee-select
                            name="employee_id"
                            :employees="$employees"
                            :selected="old('employee_id')"
                            :required="true"
                        />
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Reporting manager</label>
                        <x-employee-select
                            name="reporting_manager_id"
                            :employees="$employees"
                            :selected="old('reporting_manager_id')"
                            :required="true"
                            placeholder="Search manager by name, department or designation…"
                        />
                    </div>
                    <div class="mb-1">
                        <label class="form-label">Type</label>
                        <select name="reporting_manager_type" class="form-select" required>
                            <option value="Primary" @selected(old('reporting_manager_type', 'Primary') === 'Primary')>Primary</option>
                            <option value="Secondary" @selected(old('reporting_manager_type') === 'Secondary')>Secondary</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button class="btn add-btn">Assign</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@if($errors->any())
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const modal = new bootstrap.Modal(document.getElementById('assignModal'));
    modal.show();
});
</script>
@endpush
@endif
