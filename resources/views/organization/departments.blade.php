@extends('layouts.app')

@section('title', 'Departments')
@section('heading', 'Departments')

@section('page_actions')
<a href="{{ route('designations.index') }}" class="btn btn-outline-secondary me-2">Designations</a>
<a href="#" class="btn add-btn" data-bs-toggle="modal" data-bs-target="#add_department">
    <i class="la la-plus-circle"></i> Add Department
</a>
@endsection

@push('styles')
<style>
    .org-wrap { --ink:#0f2744; --muted:#6b7c93; --line:#e8eef5; --soft:#f4f7fb; --accent:#ff9b44; }
    .org-wrap .org-panel {
        background:#fff; border:1px solid var(--line); border-radius:18px; overflow:hidden;
    }
    .org-wrap .org-panel-head {
        display:flex; align-items:center; justify-content:space-between; gap:1rem; flex-wrap:wrap;
        padding:1rem 1.15rem; border-bottom:1px solid var(--line);
        background:linear-gradient(180deg,#fff,#fafbfd);
    }
    .org-wrap .org-panel-head h5 { margin:0; font-weight:750; color:var(--ink); font-size:1.05rem; }
    .org-wrap .org-panel-body { padding:1rem 1.15rem; }
    .org-wrap .org-metric {
        border:1px solid var(--line); border-radius:14px; background:#fff; padding:1rem 1.1rem; height:100%;
        position:relative; overflow:hidden;
    }
    .org-wrap .org-metric::before {
        content:""; position:absolute; left:0; top:0; bottom:0; width:4px; background:var(--accent);
    }
    .org-wrap .org-metric .k {
        font-size:.7rem; text-transform:uppercase; letter-spacing:.04em; color:var(--muted); font-weight:700;
    }
    .org-wrap .org-metric .v { font-size:1.55rem; font-weight:800; color:var(--ink); margin:0; line-height:1.1; }
    .org-wrap .org-chip {
        display:inline-flex; padding:.28rem .65rem; border-radius:999px;
        background:#fff7ed; color:#c2410c; border:1px solid #ffd7b0; font-size:.75rem; font-weight:650;
    }
    .org-wrap .table > :not(caption) > * > * { vertical-align:middle; }
    .org-wrap .form-label { font-size:.8rem; font-weight:600; color:var(--muted); }
</style>
@endpush

@section('content')
@php $total = method_exists($departments, 'total') ? $departments->total() : $departments->count(); @endphp
<div class="org-wrap">
    <div class="row g-3 mb-3">
        <div class="col-md-4">
            <div class="org-metric">
                <div class="k">Total departments</div>
                <p class="v">{{ $total }}</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="org-metric">
                <div class="k">Showing</div>
                <p class="v">{{ $departments->count() }}</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="org-metric">
                <div class="k">Quick action</div>
                <p class="v" style="font-size:1rem;padding-top:.4rem;">Add via popup</p>
            </div>
        </div>
    </div>

    <div class="org-panel mb-3">
        <div class="org-panel-head">
            <h5><i class="fa-solid fa-magnifying-glass me-1" style="color:var(--accent)"></i> Search departments</h5>
        </div>
        <div class="org-panel-body">
            <form method="GET" action="{{ route('departments.index') }}" class="row g-2 align-items-end">
                <div class="col-md-9">
                    <label class="form-label">Name / code</label>
                    <input type="text" name="q" value="{{ $q }}" class="form-control" placeholder="Search departments...">
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button class="btn add-btn flex-fill">Search</button>
                    @if($q !== '')
                        <a href="{{ route('departments.index') }}" class="btn btn-outline-secondary">Clear</a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <div class="org-panel">
        <div class="org-panel-head">
            <h5>All departments</h5>
            <span class="small text-muted">{{ $total }} record(s)</span>
        </div>
        <div class="org-panel-body">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                    <tr>
                        <th style="width:56px;">#</th>
                        <th>Department name</th>
                        <th>Code</th>
                        <th class="text-end">Action</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($departments as $index => $department)
                        <tr>
                            <td>{{ $departments->firstItem() + $index }}</td>
                            <td class="fw-semibold" style="color:var(--ink)">{{ $department->name }}</td>
                            <td><span class="org-chip">{{ $department->code }}</span></td>
                            <td class="text-end">
                                <div class="d-inline-flex gap-1">
                                    <button type="button"
                                            class="btn btn-sm btn-outline-primary edit-dept-btn"
                                            title="Edit"
                                            data-bs-toggle="modal"
                                            data-bs-target="#edit_department"
                                            data-name="{{ $department->name }}"
                                            data-code="{{ $department->code }}"
                                            data-url="{{ route('departments.update', $department) }}">
                                        <i class="fa-solid fa-pen"></i>
                                    </button>
                                    <form method="POST" action="{{ route('departments.destroy', $department) }}" class="d-inline"
                                          onsubmit="return confirm('Delete this department?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                                            <i class="fa-regular fa-trash-can"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted py-4">No departments found.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $departments->links() }}</div>
        </div>
    </div>
</div>

{{-- Add Modal --}}
<div class="modal fade" id="add_department" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="{{ route('departments.store') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Add Department</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Department name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" required value="{{ old('name') }}" placeholder="e.g. Human Resources">
                    </div>
                    <div class="mb-1">
                        <label class="form-label">Code <span class="text-danger">*</span></label>
                        <input type="text" name="code" class="form-control" required value="{{ old('code') }}" placeholder="e.g. HR">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button class="btn add-btn">Save Department</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Edit Modal --}}
<div class="modal fade" id="edit_department" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" id="edit_department_form" action="#">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title">Edit Department</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Department name <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="edit_dept_name" class="form-control" required>
                    </div>
                    <div class="mb-1">
                        <label class="form-label">Code <span class="text-danger">*</span></label>
                        <input type="text" name="code" id="edit_dept_code" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button class="btn add-btn">Update Department</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.edit-dept-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.getElementById('edit_department_form').action = this.dataset.url;
            document.getElementById('edit_dept_name').value = this.dataset.name || '';
            document.getElementById('edit_dept_code').value = this.dataset.code || '';
        });
    });
});
</script>
@endpush
