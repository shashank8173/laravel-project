@extends('layouts.app')

@section('title', 'Candidates')
@section('heading', 'Candidates')

@section('page_actions')
<button type="button" class="btn add-btn" data-bs-toggle="modal" data-bs-target="#candidateModal">
    <i class="fa-solid fa-plus"></i> Add candidate
</button>
@endsection

@push('styles')
<style>
    .cd-wrap { --ink:#0f2744; --muted:#6b7c93; --line:#e8eef5; --soft:#f4f7fb; --accent:#ff9b44; }
    .cd-wrap .cd-panel { background:#fff; border:1px solid var(--line); border-radius:18px; overflow:hidden; }
    .cd-wrap .cd-panel-head {
        display:flex; justify-content:space-between; align-items:center; gap:1rem; flex-wrap:wrap;
        padding:1rem 1.15rem; border-bottom:1px solid var(--line);
        background:linear-gradient(180deg,#fff,#fafbfd);
    }
    .cd-wrap .cd-panel-head h5 { margin:0; font-weight:750; color:var(--ink); }
    .cd-wrap .cd-panel-body { padding:1.15rem; }
    .cd-wrap .cd-metric {
        border:1px solid var(--line); border-radius:14px; background:#fff; padding:1rem 1.05rem; height:100%;
        position:relative; overflow:hidden; text-decoration:none; display:block; color:inherit;
        transition: border-color .15s ease, transform .15s ease;
    }
    .cd-wrap .cd-metric:hover { border-color:#ffd0a8; transform:translateY(-1px); }
    .cd-wrap .cd-metric.is-active { border-color:var(--accent); box-shadow:0 0 0 2px rgba(255,155,68,.15); }
    .cd-wrap .cd-metric::before { content:""; position:absolute; left:0; top:0; bottom:0; width:4px; background:var(--accent); }
    .cd-wrap .cd-metric.is-applied::before { background:#2563eb; }
    .cd-wrap .cd-metric.is-interview::before { background:#7c3aed; }
    .cd-wrap .cd-metric.is-selected::before { background:#16a34a; }
    .cd-wrap .cd-metric.is-hired::before { background:#0f766e; }
    .cd-wrap .cd-metric.is-rejected::before { background:#ef4444; }
    .cd-wrap .cd-metric.is-hold::before { background:#f59e0b; }
    .cd-wrap .cd-metric .k { font-size:.68rem; text-transform:uppercase; letter-spacing:.04em; color:var(--muted); font-weight:700; }
    .cd-wrap .cd-metric .v { font-size:1.35rem; font-weight:800; color:var(--ink); margin:.15rem 0 0; line-height:1.1; }
    .cd-wrap .form-label { font-size:.8rem; font-weight:600; color:var(--muted); }
    .cd-wrap .table > :not(caption) > * > * { vertical-align:middle; }
    .cd-wrap .name { font-weight:750; color:var(--ink); margin:0; }
    .cd-wrap .sub { font-size:.78rem; color:var(--muted); }
    .cd-wrap .cd-pill {
        display:inline-flex; padding:.28rem .65rem; border-radius:999px; font-size:.72rem; font-weight:700; text-transform:capitalize;
    }
    .cd-wrap .st-applied { background:#dbeafe; color:#1d4ed8; }
    .cd-wrap .st-interview { background:#ede9fe; color:#6d28d9; }
    .cd-wrap .st-selected { background:#dcfce7; color:#15803d; }
    .cd-wrap .st-hired { background:#ccfbf1; color:#0f766e; }
    .cd-wrap .st-rejected { background:#fee2e2; color:#b91c1c; }
    .cd-wrap .st-on-hold { background:#fef3c7; color:#b45309; }
</style>
@endpush

@section('content')
@php
    $statusClass = fn ($s) => match ($s) {
        'applied' => 'st-applied',
        'interview' => 'st-interview',
        'selected' => 'st-selected',
        'hired' => 'st-hired',
        'rejected' => 'st-rejected',
        'on-hold' => 'st-on-hold',
        default => 'st-applied',
    };
@endphp
<div class="cd-wrap">
    <div class="row g-3 mb-3">
        <div class="col-6 col-md-4 col-xl">
            <a href="{{ route('candidates.index', array_filter(['q' => $q ?: null])) }}"
               class="cd-metric {{ ! $status ? 'is-active' : '' }}">
                <div class="k">All</div>
                <p class="v">{{ $counts['all'] }}</p>
            </a>
        </div>
        <div class="col-6 col-md-4 col-xl">
            <a href="{{ route('candidates.index', array_filter(['q' => $q ?: null, 'status' => 'applied'])) }}"
               class="cd-metric is-applied {{ $status === 'applied' ? 'is-active' : '' }}">
                <div class="k">Applied</div>
                <p class="v">{{ $counts['applied'] }}</p>
            </a>
        </div>
        <div class="col-6 col-md-4 col-xl">
            <a href="{{ route('candidates.index', array_filter(['q' => $q ?: null, 'status' => 'interview'])) }}"
               class="cd-metric is-interview {{ $status === 'interview' ? 'is-active' : '' }}">
                <div class="k">Interview</div>
                <p class="v">{{ $counts['interview'] }}</p>
            </a>
        </div>
        <div class="col-6 col-md-4 col-xl">
            <a href="{{ route('candidates.index', array_filter(['q' => $q ?: null, 'status' => 'selected'])) }}"
               class="cd-metric is-selected {{ $status === 'selected' ? 'is-active' : '' }}">
                <div class="k">Selected</div>
                <p class="v">{{ $counts['selected'] }}</p>
            </a>
        </div>
        <div class="col-6 col-md-4 col-xl">
            <a href="{{ route('candidates.index', array_filter(['q' => $q ?: null, 'status' => 'hired'])) }}"
               class="cd-metric is-hired {{ $status === 'hired' ? 'is-active' : '' }}">
                <div class="k">Hired</div>
                <p class="v">{{ $counts['hired'] ?? 0 }}</p>
            </a>
        </div>
        <div class="col-6 col-md-4 col-xl">
            <a href="{{ route('candidates.index', array_filter(['q' => $q ?: null, 'status' => 'on-hold'])) }}"
               class="cd-metric is-hold {{ $status === 'on-hold' ? 'is-active' : '' }}">
                <div class="k">On hold</div>
                <p class="v">{{ $counts['on-hold'] }}</p>
            </a>
        </div>
        <div class="col-6 col-md-4 col-xl">
            <a href="{{ route('candidates.index', array_filter(['q' => $q ?: null, 'status' => 'rejected'])) }}"
               class="cd-metric is-rejected {{ $status === 'rejected' ? 'is-active' : '' }}">
                <div class="k">Rejected</div>
                <p class="v">{{ $counts['rejected'] }}</p>
            </a>
        </div>
    </div>

    <div class="cd-panel mb-3">
        <div class="cd-panel-head">
            <h5><i class="fa-solid fa-filter me-1" style="color:var(--accent)"></i> Filters</h5>
            @if($q !== '' || $status)
                <a href="{{ route('candidates.index') }}" class="btn btn-sm btn-outline-secondary">Clear</a>
            @endif
        </div>
        <div class="cd-panel-body">
            <form method="GET" action="{{ route('candidates.index') }}" class="row g-2 align-items-end">
                <div class="col-md-5">
                    <label class="form-label">Search</label>
                    <input type="search" name="q" value="{{ $q }}" class="form-control" placeholder="Name, email, mobile, position">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All</option>
                        @foreach($statuses as $s)
                            <option value="{{ $s }}" @selected($status === $s)>{{ str_replace('-', ' ', $s) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <button class="btn add-btn me-1">Apply</button>
                    <a href="{{ route('candidates.index') }}" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="cd-panel">
        <div class="cd-panel-head">
            <h5><i class="fa-solid fa-user-group me-1" style="color:var(--accent)"></i> Candidate list</h5>
            <span class="small text-muted">{{ $candidates->total() }} record(s)</span>
        </div>
        <div class="table-responsive p-2">
            <table class="table align-middle mb-0">
                <thead>
                <tr>
                    <th>Candidate</th>
                    <th>Contact</th>
                    <th>Position</th>
                    <th>Resume</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
                </thead>
                <tbody>
                @forelse($candidates as $c)
                    <tr>
                        <td>
                            <p class="name">{{ $c->name }}</p>
                            @if($c->notes)
                                <div class="sub">{{ \Illuminate\Support\Str::limit($c->notes, 50) }}</div>
                            @endif
                        </td>
                        <td>
                            <div>{{ $c->email ?: '—' }}</div>
                            <div class="sub">{{ $c->mobile ?: '' }}</div>
                        </td>
                        <td>{{ $c->position ?: '—' }}</td>
                        <td>
                            @if($c->hasResume())
                                <a href="{{ route('candidates.resume', $c) }}" class="btn btn-sm btn-outline-primary" title="{{ $c->resume_name }}">
                                    <i class="fa-solid fa-file-arrow-down me-1"></i> Download
                                </a>
                                <div class="sub mt-1">{{ \Illuminate\Support\Str::limit($c->resume_name, 28) }}</div>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td><span class="cd-pill {{ $statusClass($c->status) }}">{{ str_replace('-', ' ', $c->status) }}</span></td>
                        <td class="text-end text-nowrap">
                            @if(in_array($c->status, ['selected', 'interview'], true))
                                <form method="POST" action="{{ route('candidates.hire', $c) }}" class="d-inline"
                                      onsubmit="return confirm('Create an employee record from this candidate?')">
                                    @csrf
                                    <button class="btn btn-sm btn-success">Hire</button>
                                </form>
                            @endif
                            <button type="button" class="btn btn-sm btn-outline-secondary edit-candidate-btn"
                                data-bs-toggle="modal" data-bs-target="#candidateModal"
                                data-url="{{ route('candidates.update', $c) }}"
                                data-name="{{ $c->name }}"
                                data-email="{{ $c->email }}"
                                data-mobile="{{ $c->mobile }}"
                                data-position="{{ $c->position }}"
                                data-status="{{ $c->status }}"
                                data-notes="{{ $c->notes }}"
                                data-has-resume="{{ $c->hasResume() ? '1' : '0' }}"
                                data-resume-name="{{ $c->resume_name }}"
                                data-resume-url="{{ $c->hasResume() ? route('candidates.resume', $c) : '' }}">
                                Edit
                            </button>
                            <form method="POST" action="{{ route('candidates.destroy', $c) }}" class="d-inline"
                                  onsubmit="return confirm('Delete this candidate?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">No candidates found.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-3 border-top">{{ $candidates->links() }}</div>
    </div>
</div>

<div class="modal fade" id="candidateModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content" style="border:0;border-radius:16px;">
            <form method="POST" id="candidateForm" action="{{ route('candidates.store') }}" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="_method" id="candidateMethod" value="POST">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold" id="candidateModalTitle">Add candidate</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body cd-wrap">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Name</label>
                            <input type="text" name="name" id="candName" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Position</label>
                            <input type="text" name="position" id="candPosition" class="form-control" placeholder="Role applied for">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" id="candEmail" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Mobile</label>
                            <input type="text" name="mobile" id="candMobile" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Status</label>
                            <select name="status" id="candStatus" class="form-select" required>
                                @foreach($statuses as $s)
                                    <option value="{{ $s }}">{{ str_replace('-', ' ', $s) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Resume</label>
                            <input type="file" name="resume" id="candResume" class="form-control" accept=".pdf,.doc,.docx,application/pdf">
                            <div class="form-text">PDF, DOC, DOCX — max 5 MB</div>
                            <div id="candResumeCurrent" class="mt-2 small" style="display:none;">
                                Current: <a href="#" id="candResumeLink" target="_blank"></a>
                                <label class="ms-2 mb-0">
                                    <input type="checkbox" name="remove_resume" id="candRemoveResume" value="1"> Remove
                                </label>
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Notes</label>
                            <textarea name="notes" id="candNotes" class="form-control" rows="3"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button class="btn add-btn" id="candidateSubmitBtn">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('candidateForm');
    const methodInput = document.getElementById('candidateMethod');
    const title = document.getElementById('candidateModalTitle');
    const modalEl = document.getElementById('candidateModal');
    const resumeCurrent = document.getElementById('candResumeCurrent');
    const resumeLink = document.getElementById('candResumeLink');
    const removeResume = document.getElementById('candRemoveResume');

    function resetAddMode() {
        form.action = @json(route('candidates.store'));
        methodInput.value = 'POST';
        title.textContent = 'Add candidate';
        form.reset();
        document.getElementById('candStatus').value = 'applied';
        resumeCurrent.style.display = 'none';
        resumeLink.href = '#';
        resumeLink.textContent = '';
        removeResume.checked = false;
    }

    modalEl.addEventListener('show.bs.modal', function (e) {
        const btn = e.relatedTarget;
        if (!btn || !btn.classList.contains('edit-candidate-btn')) {
            resetAddMode();
            return;
        }
        form.action = btn.dataset.url;
        methodInput.value = 'PUT';
        title.textContent = 'Edit candidate';
        document.getElementById('candName').value = btn.dataset.name || '';
        document.getElementById('candEmail').value = btn.dataset.email || '';
        document.getElementById('candMobile').value = btn.dataset.mobile || '';
        document.getElementById('candPosition').value = btn.dataset.position || '';
        document.getElementById('candStatus').value = btn.dataset.status || 'applied';
        document.getElementById('candNotes').value = btn.dataset.notes || '';
        document.getElementById('candResume').value = '';
        removeResume.checked = false;

        if (btn.dataset.hasResume === '1') {
            resumeCurrent.style.display = 'block';
            resumeLink.href = btn.dataset.resumeUrl || '#';
            resumeLink.textContent = btn.dataset.resumeName || 'Download resume';
        } else {
            resumeCurrent.style.display = 'none';
            resumeLink.href = '#';
            resumeLink.textContent = '';
        }
    });
});
</script>
@endpush
