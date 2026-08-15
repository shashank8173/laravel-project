@extends('layouts.app')

@section('title', 'Announcement')
@section('heading', 'Announcement')

@section('page_actions')
@if($isAdmin)
<a href="{{ route('eom.index') }}" class="btn btn-outline-secondary me-2">Employee of the Month</a>
<a href="#" class="btn add-btn" data-bs-toggle="modal" data-bs-target="#add_activity">
    <i class="la la-plus-circle"></i> Add Announcement
</a>
@endif
@endsection

@push('styles')
<style>
    .act-wrap { --ink:#0f2744; --muted:#6b7c93; --line:#e8eef5; --soft:#f4f7fb; --accent:#ff9b44; }
    .act-wrap .act-panel {
        background:#fff; border:1px solid var(--line); border-radius:18px; overflow:hidden;
    }
    .act-wrap .act-panel-head {
        display:flex; align-items:center; justify-content:space-between; gap:1rem; flex-wrap:wrap;
        padding:1rem 1.15rem; border-bottom:1px solid var(--line);
        background:linear-gradient(180deg,#fff,#fafbfd);
    }
    .act-wrap .act-panel-head h5 { margin:0; font-weight:750; color:var(--ink); font-size:1.05rem; }
    .act-wrap .act-panel-body { padding:1.15rem; }
    .act-wrap .act-metric {
        border:1px solid var(--line); border-radius:14px; background:#fff; padding:1rem 1.1rem; height:100%;
        position:relative; overflow:hidden;
    }
    .act-wrap .act-metric::before {
        content:""; position:absolute; left:0; top:0; bottom:0; width:4px; background:var(--accent);
    }
    .act-wrap .act-metric .k {
        font-size:.7rem; text-transform:uppercase; letter-spacing:.04em; color:var(--muted); font-weight:700;
    }
    .act-wrap .act-metric .v { font-size:1.55rem; font-weight:800; color:var(--ink); margin:0; line-height:1.1; }
    .act-wrap .act-card {
        border:1px solid var(--line); border-radius:16px; background:#fff; height:100%;
        display:flex; flex-direction:column; overflow:hidden;
        transition:transform .15s ease, box-shadow .15s ease;
    }
    .act-wrap .act-card:hover { transform:translateY(-2px); box-shadow:0 10px 24px rgba(15,39,68,.07); }
    .act-wrap .act-card .head {
        padding:1rem 1rem .55rem; display:flex; justify-content:space-between; gap:.75rem; align-items:flex-start;
    }
    .act-wrap .act-card .title { margin:0; font-weight:800; color:var(--ink); font-size:1.02rem; }
    .act-wrap .act-card .body { padding:.25rem 1rem 1rem; flex:1; }
    .act-wrap .act-card .desc {
        margin:0; color:#334155; font-size:.92rem; line-height:1.5; white-space:pre-line;
        display:-webkit-box; -webkit-line-clamp:4; -webkit-box-orient:vertical; overflow:hidden;
    }
    .act-wrap .act-card .foot {
        display:flex; justify-content:space-between; align-items:center; gap:.5rem; flex-wrap:wrap;
        padding:.75rem 1rem; border-top:1px solid var(--line); background:#fafbfd;
    }
    .act-wrap .act-meta { font-size:.78rem; color:var(--muted); font-weight:600; }
    .act-wrap .act-chip {
        display:inline-flex; align-items:center; gap:.3rem; padding:.28rem .65rem; border-radius:999px;
        background:#fff7ed; color:#c2410c; border:1px solid #ffd7b0; font-size:.72rem; font-weight:700;
    }
    .act-wrap .form-label { font-size:.8rem; font-weight:600; color:var(--muted); }
    .act-wrap .emp-picker {
        max-height:220px; overflow-y:auto; border:1px solid var(--line); border-radius:12px;
        padding:.75rem; background:var(--soft);
    }
</style>
@endpush

@section('content')
@php
    $total = method_exists($notifications, 'total') ? $notifications->total() : $notifications->count();
    $latest = $notifications->first();
@endphp
<div class="act-wrap">
    <div class="row g-3 mb-3">
        <div class="col-md-4">
            <div class="act-metric">
                <div class="k">Total announcements</div>
                <p class="v">{{ $totalAll ?? $total }}</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="act-metric">
                <div class="k">Showing</div>
                <p class="v">{{ $notifications->count() }}</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="act-metric">
                <div class="k">Latest</div>
                <p class="v" style="font-size:1.02rem;padding-top:.4rem;">{{ \Illuminate\Support\Str::limit($latest->title ?? '—', 28) }}</p>
            </div>
        </div>
    </div>

    <div class="act-panel mb-3">
        <div class="act-panel-head">
            <h5><i class="fa-solid fa-magnifying-glass me-1" style="color:var(--accent)"></i> Search announcements</h5>
        </div>
        <div class="act-panel-body">
            <form method="GET" action="{{ route('activities.index') }}" class="row g-2 align-items-end">
                <div class="col-md-9">
                    <label class="form-label">Title / description</label>
                    <input type="text" name="q" value="{{ $q ?? '' }}" class="form-control" placeholder="Search announcements...">
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button class="btn add-btn flex-fill">Search</button>
                    @if(($q ?? '') !== '')
                        <a href="{{ route('activities.index') }}" class="btn btn-outline-secondary">Clear</a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <div class="act-panel">
        <div class="act-panel-head">
            <h5>All announcements</h5>
            <span class="small text-muted">{{ $total }} record(s)</span>
        </div>
        <div class="act-panel-body">
            <div class="row g-3">
                @forelse($notifications as $index => $row)
                    <div class="col-lg-4 col-md-6">
                        <div class="act-card">
                            <div class="head">
                                <div>
                                    <span class="act-chip"><i class="fa-solid fa-bullhorn"></i> Announcement</span>
                                    <h6 class="title mt-2">{{ $row->title }}</h6>
                                </div>
                                @if($isAdmin)
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="fa-solid fa-ellipsis-vertical"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li>
                                                <a class="dropdown-item edit-btn" href="#"
                                                   data-title="{{ $row->title }}"
                                                   data-description="{{ e($row->description) }}"
                                                   data-url="{{ route('activities.update', $row) }}"
                                                   data-bs-toggle="modal"
                                                   data-bs-target="#edit_activity">
                                                    <i class="fa-solid fa-pen me-1"></i> Edit
                                                </a>
                                            </li>
                                            <li>
                                                <form method="POST" action="{{ route('activities.destroy', $row) }}"
                                                      onsubmit="return confirm('Delete this announcement?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="dropdown-item text-danger">
                                                        <i class="fa-regular fa-trash-can me-1"></i> Delete
                                                    </button>
                                                </form>
                                            </li>
                                        </ul>
                                    </div>
                                @endif
                            </div>
                            <div class="body">
                                <div class="desc hrm-rich-content">{!! $row->description !!}</div>
                            </div>
                            <div class="foot">
                                <span class="act-meta">
                                    <i class="fa-regular fa-clock me-1"></i>
                                    {{ $row->date }} {{ $row->time }}
                                </span>
                                <span class="act-meta">
                                    {{ $row->sender?->full_name ? 'By '.$row->sender->full_name : '—' }}
                                </span>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-12">
                        <div class="text-center text-muted py-5">
                            <div class="mb-2" style="font-size:2rem;opacity:.4;"><i class="fa-solid fa-bullhorn"></i></div>
                            No announcements yet.
                        </div>
                    </div>
                @endforelse
            </div>
            <div class="mt-3">{{ $notifications->links() }}</div>
        </div>
    </div>
</div>

@if($isAdmin)
<div class="modal fade" id="add_activity" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <form method="POST" action="{{ route('activities.store') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Add Announcement</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Title <span class="text-danger">*</span></label>
                        <input class="form-control" required type="text" name="title" value="{{ old('title') }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description <span class="text-danger">*</span></label>
                        <textarea required class="form-control js-rich-editor" rows="5" name="description"
                                  placeholder="Click to write announcement…">{{ old('description') }}</textarea>
                    </div>
                    <div class="mb-1">
                        <label class="form-label d-block">Send to <span class="text-danger">*</span></label>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" id="check_all_employees">
                            <label class="form-check-label" for="check_all_employees">Select all employees</label>
                        </div>
                        <div class="emp-picker">
                            <x-employee-select
                                name="emp_send_to[]"
                                id="activityRecipients"
                                :employees="$employees"
                                :selected="old('emp_send_to', [])"
                                :multiple="true"
                                :required="true"
                                placeholder="Search & select employees…"
                            />
                            <div class="form-text mt-1">Search by name, department or designation. Selected employees get <strong>in-app bell</strong> + email (if office email is set).</div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Cancel</button>
                    <button class="btn add-btn" type="submit">Save Announcement</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="edit_activity" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <form method="POST" id="editForm" action="#">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title">Edit Announcement</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Title <span class="text-danger">*</span></label>
                        <input class="form-control" required type="text" name="title" id="edit_title">
                    </div>
                    <div class="mb-1">
                        <label class="form-label">Description <span class="text-danger">*</span></label>
                        <textarea required class="form-control js-rich-editor" rows="5" name="description" id="edit_description"
                                  placeholder="Click to edit announcement…"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Cancel</button>
                    <button class="btn add-btn" type="submit">Update Announcement</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const checkAll = document.getElementById('check_all_employees');
    const recipients = document.getElementById('activityRecipients');
    if (checkAll && recipients) {
        checkAll.addEventListener('change', function () {
            Array.prototype.forEach.call(recipients.options, function (opt) {
                if (opt.value) opt.selected = checkAll.checked;
            });
            recipients.dispatchEvent(new Event('change', { bubbles: true }));
        });
    }

    const addForm = document.getElementById('add_activity')?.querySelector('form');
    if (addForm && recipients) {
        addForm.addEventListener('submit', function (e) {
            const selected = Array.prototype.filter.call(recipients.options, function (opt) {
                return opt.value && opt.selected;
            });
            if (!selected.length) {
                e.preventDefault();
                alert('Please select at least one employee (Send to).');
                return false;
            }

            // Ensure multi-select values always POST (some browsers skip clipped selects)
            addForm.querySelectorAll('input.js-emp-send-to-sync').forEach(function (el) { el.remove(); });
            selected.forEach(function (opt) {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'emp_send_to[]';
                input.value = opt.value;
                input.className = 'js-emp-send-to-sync';
                addForm.appendChild(input);
            });
            recipients.disabled = true; // avoid duplicate emp_send_to[] from select
        });
    }

    document.querySelectorAll('.edit-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.getElementById('edit_title').value = this.dataset.title || '';
            document.getElementById('edit_description').value = this.dataset.description || '';
            document.getElementById('editForm').action = this.dataset.url || '';
        });
    });
});
</script>
@endpush
