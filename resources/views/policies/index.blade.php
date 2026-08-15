@extends('layouts.app')

@section('title', 'Company Policies')
@section('heading', 'Company Policies')

@section('page_actions')
@if($isAdmin)
<a href="#" class="btn add-btn" data-bs-toggle="modal" data-bs-target="#add_policy">
    <i class="la la-plus-circle"></i> Add Policy
</a>
@endif
@endsection

@push('styles')
<style>
    .pol-wrap { --ink:#0f2744; --muted:#6b7c93; --line:#e8eef5; --soft:#f4f7fb; --accent:#ff9b44; }
    .pol-wrap .pol-panel {
        background:#fff; border:1px solid var(--line); border-radius:18px; overflow:hidden;
    }
    .pol-wrap .pol-panel-head {
        display:flex; align-items:center; justify-content:space-between; gap:1rem; flex-wrap:wrap;
        padding:1rem 1.15rem; border-bottom:1px solid var(--line);
        background:linear-gradient(180deg,#fff,#fafbfd);
    }
    .pol-wrap .pol-panel-head h5 { margin:0; font-weight:750; color:var(--ink); font-size:1.05rem; }
    .pol-wrap .pol-panel-body { padding:1rem 1.15rem; }
    .pol-wrap .pol-metric {
        border:1px solid var(--line); border-radius:14px; background:#fff; padding:1rem 1.1rem; height:100%;
        position:relative; overflow:hidden;
    }
    .pol-wrap .pol-metric::before {
        content:""; position:absolute; left:0; top:0; bottom:0; width:4px; background:var(--accent);
    }
    .pol-wrap .pol-metric .k {
        font-size:.7rem; text-transform:uppercase; letter-spacing:.04em; color:var(--muted); font-weight:700;
    }
    .pol-wrap .pol-metric .v { font-size:1.55rem; font-weight:800; color:var(--ink); margin:0; line-height:1.1; }
    .pol-wrap .pol-chip {
        display:inline-flex; align-items:center; gap:.35rem; padding:.28rem .65rem; border-radius:999px;
        background:#fff7ed; color:#c2410c; border:1px solid #ffd7b0; font-size:.75rem; font-weight:650;
    }
    .pol-wrap .pol-file {
        color:var(--ink); font-weight:600; text-decoration:none;
    }
    .pol-wrap .pol-file:hover { color:var(--accent); }
    .pol-wrap .table > :not(caption) > * > * { vertical-align:middle; }
    .pol-wrap .form-label { font-size:.8rem; font-weight:600; color:var(--muted); }
</style>
@endpush

@section('content')
@php $totalPolicies = method_exists($policies, 'total') ? $policies->total() : $policies->count(); @endphp
<div class="pol-wrap">
    <div class="row g-3 mb-3">
        <div class="col-md-4">
            <div class="pol-metric">
                <div class="k">Total policies</div>
                <p class="v">{{ $totalPolicies }}</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="pol-metric">
                <div class="k">Policy types</div>
                <p class="v">{{ $types->count() }}</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="pol-metric">
                <div class="k">Access</div>
                <p class="v" style="font-size:1.05rem;padding-top:.35rem;">{{ $isAdmin ? 'Admin · full CRUD' : 'View & download' }}</p>
            </div>
        </div>
    </div>

    <div class="pol-panel mb-3">
        <div class="pol-panel-head">
            <h5><i class="fa-solid fa-magnifying-glass me-1" style="color:var(--accent)"></i> Search policies</h5>
        </div>
        <div class="pol-panel-body">
            <form method="GET" action="{{ route('policies.index') }}" class="row g-2 align-items-end">
                <div class="col-md-9">
                    <label class="form-label">Name / type / file</label>
                    <input type="text" name="q" value="{{ $q }}" class="form-control" placeholder="Search company policies...">
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button class="btn add-btn flex-fill">Search</button>
                    @if($q !== '')
                        <a href="{{ route('policies.index') }}" class="btn btn-outline-secondary">Clear</a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <div class="pol-panel">
        <div class="pol-panel-head">
            <h5>All policies</h5>
            <span class="small text-muted">{{ $totalPolicies }} record(s)</span>
        </div>
        <div class="pol-panel-body">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                    <tr>
                        <th style="width:56px;">#</th>
                        <th>Policy name</th>
                        <th>Type</th>
                        <th>Updated</th>
                        <th>File</th>
                        <th class="text-end">Action</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($policies as $index => $policy)
                        <tr>
                            <td>{{ $policies->firstItem() + $index }}</td>
                            <td class="fw-semibold" style="color:var(--ink)">{{ $policy->policy_name }}</td>
                            <td>
                                @if($policy->policy_type)
                                    <span class="pol-chip">{{ $policy->policy_type }}</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>{{ $policy->updated_on?->format('d M Y') ?? '—' }}</td>
                            <td>
                                @if($policy->hasFile())
                                    <a class="pol-file" href="{{ route('policies.download', $policy) }}">
                                        <i class="fa-solid fa-file-arrow-down me-1"></i>{{ \Illuminate\Support\Str::limit($policy->fileName(), 28) }}
                                    </a>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <div class="d-inline-flex gap-1">
                                    @if($policy->hasFile())
                                        <a href="{{ route('policies.download', $policy) }}" class="btn btn-sm btn-outline-secondary" title="Download">
                                            <i class="fa-solid fa-download"></i>
                                        </a>
                                    @endif
                                    @if($isAdmin)
                                        <button type="button"
                                                class="btn btn-sm btn-outline-primary edit-policy-btn"
                                                title="Edit"
                                                data-bs-toggle="modal"
                                                data-bs-target="#edit_policy"
                                                data-id="{{ $policy->id }}"
                                                data-name="{{ $policy->policy_name }}"
                                                data-type="{{ $policy->policy_type }}"
                                                data-updated="{{ optional($policy->updated_on)->format('Y-m-d') }}"
                                                data-file="{{ $policy->fileName() }}"
                                                data-url="{{ route('policies.update', $policy) }}">
                                            <i class="fa-solid fa-pen"></i>
                                        </button>
                                        <form method="POST" action="{{ route('policies.destroy', $policy) }}" class="d-inline"
                                              onsubmit="return confirm('Delete this policy?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                                                <i class="fa-regular fa-trash-can"></i>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">No policies found.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $policies->links() }}</div>
        </div>
    </div>
</div>

@if($isAdmin)
<div class="modal fade" id="add_policy" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <form method="POST" action="{{ route('policies.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Add Policy</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Policy name <span class="text-danger">*</span></label>
                            <input type="text" name="policy_name" class="form-control" required value="{{ old('policy_name') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Policy type <span class="text-danger">*</span></label>
                            <input type="text" name="policy_type" class="form-control" list="policy_types" required value="{{ old('policy_type') }}" placeholder="e.g. Office Environment">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Updated on</label>
                            <input type="date" name="updated_on" class="form-control" value="{{ old('updated_on', now()->toDateString()) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">File <span class="text-danger">*</span></label>
                            <input type="file" name="file" class="form-control" required>
                            <div class="form-text">PDF, DOC, DOCX, PPT, images — max 12MB</div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button class="btn add-btn">Save Policy</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="edit_policy" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <form method="POST" id="edit_policy_form" action="#" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title">Edit Policy</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Policy name <span class="text-danger">*</span></label>
                            <input type="text" name="policy_name" id="edit_policy_name" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Policy type <span class="text-danger">*</span></label>
                            <input type="text" name="policy_type" id="edit_policy_type" class="form-control" list="policy_types" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Updated on</label>
                            <input type="date" name="updated_on" id="edit_policy_updated" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Replace file (optional)</label>
                            <input type="file" name="file" class="form-control">
                            <div class="form-text">Current: <span id="edit_policy_file">—</span></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button class="btn add-btn">Update Policy</button>
                </div>
            </form>
        </div>
    </div>
</div>

<datalist id="policy_types">
    @foreach($types as $type)
        <option value="{{ $type }}"></option>
    @endforeach
</datalist>
@endif
@endsection

@if($isAdmin)
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.edit-policy-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.getElementById('edit_policy_form').action = this.dataset.url;
            document.getElementById('edit_policy_name').value = this.dataset.name || '';
            document.getElementById('edit_policy_type').value = this.dataset.type || '';
            document.getElementById('edit_policy_updated').value = this.dataset.updated || '';
            document.getElementById('edit_policy_file').textContent = this.dataset.file || '—';
        });
    });
});
</script>
@endpush
@endif
