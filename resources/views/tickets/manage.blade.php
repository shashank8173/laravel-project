@extends('layouts.app')

@section('title', 'HR Ticket Management')
@section('heading', 'HR Ticket Management')

@section('page_actions')
<a href="{{ route('tickets.manage', ['tab' => 'categories']) }}"
   class="btn {{ ($tab ?? 'tickets') === 'categories' ? 'add-btn' : 'btn-outline-secondary' }} me-2">
    <i class="fa-solid fa-tags"></i> Categories
</a>
<a href="{{ route('tickets.index') }}" class="btn add-btn"><i class="fa-solid fa-plus"></i> Create Ticket</a>
@endsection

@push('styles')
@include('tickets.partials.styles')
<style>
    .tk-wrap .tk-tabs {
        display:flex; gap:.5rem; flex-wrap:wrap; margin-bottom:1rem;
        padding:.35rem; background:var(--tk-soft); border:1px solid var(--tk-line); border-radius:14px;
        width:fit-content; max-width:100%;
    }
    .tk-wrap .tk-tab {
        border:1px solid transparent; background:transparent; color:var(--tk-muted);
        font-weight:700; padding:.55rem 1rem; border-radius:10px; text-decoration:none;
        display:inline-flex; align-items:center; gap:.4rem;
    }
    .tk-wrap .tk-tab:hover { color:var(--tk-ink); }
    .tk-wrap .tk-tab.active {
        background:var(--tk-card, #fff); color:#c2410c; border-color:#ffd7b0;
        box-shadow:0 2px 8px rgba(15,39,68,.06);
    }
    .tk-wrap .cat-form-row {
        display:flex; gap:.65rem; flex-wrap:wrap; align-items:end;
    }
    .tk-wrap .cat-form-row .form-control { max-width:360px; }
</style>
@endpush

@section('content')
@php $activeTab = $tab ?? 'tickets'; @endphp
<div class="tk-wrap">
    <div class="tk-tabs" role="tablist">
        <a class="tk-tab {{ $activeTab === 'categories' ? 'active' : '' }}"
           href="{{ route('tickets.manage', ['tab' => 'categories']) }}">
            <i class="fa-solid fa-tags"></i> Categories
        </a>
        <a class="tk-tab {{ $activeTab === 'tickets' ? 'active' : '' }}"
           href="{{ route('tickets.manage', ['tab' => 'tickets']) }}">
            <i class="fa-solid fa-ticket"></i> Tickets
        </a>
    </div>

    @if($activeTab === 'categories')
        <div class="tk-panel mb-3">
            <div class="tk-panel-head">
                <h5>Add category</h5>
                <span class="small text-muted">Used when creating tickets</span>
            </div>
            <div class="p-3">
                <form method="POST" action="{{ route('tickets.categories.store') }}" class="cat-form-row">
                    @csrf
                    <div class="flex-grow-1" style="min-width:220px;">
                        <label class="form-label small fw-semibold text-muted">Category name</label>
                        <input type="text" name="name" class="form-control" value="{{ old('name') }}"
                               placeholder="e.g. Hardware, Leave, Payroll" required>
                    </div>
                    <button class="btn add-btn"><i class="fa-solid fa-plus"></i> Add Category</button>
                </form>
            </div>
        </div>

        <div class="tk-panel">
            <div class="tk-panel-head">
                <h5>Category list</h5>
                <span class="small text-muted">{{ $categories->count() }} categor{{ $categories->count() === 1 ? 'y' : 'ies' }}</span>
            </div>
            <div class="table-responsive p-2">
                <table class="table align-middle mb-0">
                    <thead>
                    <tr>
                        <th style="width:72px;">#</th>
                        <th>Category name</th>
                        <th class="text-end" style="width:200px;">Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($categories as $i => $category)
                        <tr>
                            <td class="text-muted">{{ $i + 1 }}</td>
                            <td class="fw-semibold" style="color:var(--tk-ink);">{{ $category->name }}</td>
                            <td class="text-end text-nowrap">
                                <button type="button" class="btn btn-sm btn-outline-primary"
                                        data-bs-toggle="modal" data-bs-target="#editCat{{ $category->id }}">Edit</button>
                                <form method="POST" action="{{ route('tickets.categories.destroy', $category) }}" class="d-inline"
                                      onsubmit="return confirm('Delete this category?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="text-muted text-center py-4">No categories yet. Add one above.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @foreach($categories as $category)
        <div class="modal fade tk-modal" id="editCat{{ $category->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <form method="POST" action="{{ route('tickets.categories.update', $category) }}">
                        @csrf
                        @method('PUT')
                        <div class="modal-header">
                            <h5 class="modal-title">Edit category</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <label class="form-label fw-semibold">Category name</label>
                            <input type="text" name="name" class="form-control" value="{{ $category->name }}" required>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn tk-btn tk-btn-ghost" data-bs-dismiss="modal">Cancel</button>
                            <button class="btn add-btn">Update</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        @endforeach
    @else
    <div class="row g-3 mb-4 tk-metrics">
        <div class="col-6 col-lg-3">
            <div class="tk-metric is-open">
                <div class="tk-icon"><i class="fa-solid fa-folder-open"></i></div>
                <div class="tk-label">Open</div>
                <p class="tk-value">{{ $counts['open'] }}</p>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="tk-metric is-progress">
                <div class="tk-icon"><i class="fa-solid fa-rotate"></i></div>
                <div class="tk-label">In Progress</div>
                <p class="tk-value">{{ $counts['in_progress'] }}</p>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="tk-metric is-resolved">
                <div class="tk-icon"><i class="fa-solid fa-circle-check"></i></div>
                <div class="tk-label">Resolved · Month</div>
                <p class="tk-value">{{ $counts['resolved'] }}</p>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="tk-metric is-closed">
                <div class="tk-icon"><i class="fa-solid fa-lock"></i></div>
                <div class="tk-label">Closed · Month</div>
                <p class="tk-value">{{ $counts['closed'] }}</p>
            </div>
        </div>
    </div>

    <div class="tk-panel">
        <div class="tk-panel-head">
            <h5>All Tickets</h5>
            <div class="tk-search" style="flex:1; max-width:420px; margin-left:auto;">
                <i class="fa-solid fa-magnifying-glass text-muted"></i>
                <input type="search" id="ticketSearch" class="form-control form-control-sm border-0 bg-transparent shadow-none" placeholder="Search title, employee, category…">
            </div>
        </div>

        <div class="d-none d-lg-grid px-3 py-2 small text-uppercase fw-bold text-muted" style="grid-template-columns: 72px 1.4fr .9fr .9fr .7fr .85fr auto; gap:.75rem; letter-spacing:.04em; font-size:.68rem;">
            <div>ID</div>
            <div>Ticket</div>
            <div>Category</div>
            <div>Employee</div>
            <div>Priority</div>
            <div>Status</div>
            <div class="text-end">Actions</div>
        </div>

        <div id="ticketList">
            @forelse($tickets as $ticket)
                <div class="tk-row ticket-item"
                     data-search="{{ strtolower($ticket->TicketID.' '.$ticket->Title.' '.($ticket->category?->name ?? '').' '.($ticket->employee?->full_name ?? '').' '.$ticket->Status.' '.$ticket->Priority) }}">
                    <div class="tk-id">#{{ $ticket->TicketID }}</div>
                    <div>
                        <p class="tk-title">{{ $ticket->Title }}</p>
                        <p class="tk-sub d-lg-none">{{ $ticket->employee?->full_name }} · {{ $ticket->category?->name }}</p>
                    </div>
                    <div class="tk-sub d-none d-lg-block">{{ $ticket->category?->name ?? '—' }}</div>
                    <div class="tk-sub d-none d-lg-block">{{ $ticket->employee?->full_name ?? '—' }}</div>
                    <div>@include('tickets.partials.pills', ['priority' => $ticket->Priority])</div>
                    <div>@include('tickets.partials.pills', ['status' => $ticket->Status])</div>
                    <div class="tk-actions">
                        @if(! in_array($ticket->Status, ['Closed', 'Resolved'], true))
                            <select class="form-select status-select"
                                    data-ticket-id="{{ $ticket->TicketID }}"
                                    data-current-status="{{ $ticket->Status }}"
                                    data-action="{{ route('tickets.status', $ticket->TicketID) }}">
                                <option value="Open" @selected($ticket->Status === 'Open')>Open</option>
                                <option value="In Progress" @selected($ticket->Status === 'In Progress')>In Progress</option>
                                <option value="Resolved" @selected($ticket->Status === 'Resolved')>Resolved</option>
                            </select>
                        @endif
                        <button type="button" class="btn tk-btn tk-btn-primary"
                                data-bs-toggle="modal"
                                data-bs-target="#ticketModal{{ $ticket->TicketID }}">
                            <i class="fa-regular fa-eye"></i> View
                        </button>
                    </div>
                </div>
            @empty
                <div class="tk-empty">
                    <i class="fa-solid fa-ticket"></i>
                    No tickets found.
                </div>
            @endforelse
        </div>
    </div>
    @endif
</div>

@if($activeTab === 'tickets')
@foreach($tickets as $ticket)
<div class="modal fade tk-modal" id="ticketModal{{ $ticket->TicketID }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <div class="small text-muted mb-1">Ticket #{{ $ticket->TicketID }}</div>
                    <h5 class="modal-title mb-0">{{ $ticket->Title }}</h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body tk-wrap">
                <div class="d-flex flex-wrap gap-2 mb-3">
                    @include('tickets.partials.pills', ['status' => $ticket->Status, 'priority' => $ticket->Priority])
                </div>
                <div class="tk-detail-grid mb-3">
                    <div class="tk-meta-card">
                        <div class="k">Category</div>
                        <p class="v">{{ $ticket->category?->name ?? '—' }}</p>
                    </div>
                    <div class="tk-meta-card">
                        <div class="k">Employee</div>
                        <p class="v">{{ $ticket->employee?->full_name ?? '—' }}</p>
                    </div>
                </div>
                <div class="tk-meta-card mb-3">
                    <div class="k">Description</div>
                    <p class="v" style="font-weight:500; white-space:pre-wrap;">{{ $ticket->Description ?: '—' }}</p>
                </div>

                <h6 class="fw-bold mb-2">Comments</h6>
                @forelse($ticket->comments->sortByDesc('created_at') as $comment)
                    <div class="tk-comment d-flex gap-2">
                        <div class="tk-avatar">{{ strtoupper(substr($comment->author?->fname ?? 'U', 0, 1)) }}</div>
                        <div>
                            <div class="meta">{{ $comment->author?->full_name ?? 'User' }} · {{ optional($comment->created_at)->format('d M Y, H:i') }}</div>
                            <div>{{ $comment->comment }}</div>
                        </div>
                    </div>
                @empty
                    <p class="text-muted small">No comments yet.</p>
                @endforelse

                @if($ticket->Status !== 'Closed')
                    <form method="POST" action="{{ route('tickets.comment', $ticket->TicketID) }}" class="mt-3">
                        @csrf
                        <label class="form-label fw-semibold">Add a comment</label>
                        <textarea class="form-control mb-2" name="comment" rows="3" required placeholder="Write an update…"></textarea>
                        <button type="submit" class="btn tk-btn tk-btn-primary">Submit Comment</button>
                    </form>
                @else
                    <p class="text-muted small mt-3 mb-0">Commenting is disabled for closed tickets.</p>
                @endif
            </div>
            <div class="modal-footer">
                <a href="{{ route('tickets.show', $ticket->TicketID) }}" class="btn tk-btn tk-btn-ghost">Open full page</a>
                <button type="button" class="btn tk-btn tk-btn-ghost" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endforeach

<div class="modal fade tk-modal" id="commentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" id="statusUpdateForm" action="">
                @csrf
                @method('PATCH')
                <div class="modal-header">
                    <h5 class="modal-title">Update status</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small mb-3">Optional comment will be saved with this status change.</p>
                    <label for="status_comment" class="form-label fw-semibold">Comment</label>
                    <textarea class="form-control" id="status_comment" name="comment" rows="4" placeholder="Add a note…"></textarea>
                    <input type="hidden" name="Status" id="modal_status">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn tk-btn tk-btn-ghost" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn tk-btn tk-btn-primary">Save status</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
@endsection

@push('scripts')
@if(($tab ?? 'tickets') === 'tickets')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const search = document.getElementById('ticketSearch');
    if (search) {
        search.addEventListener('input', function () {
            const q = this.value.trim().toLowerCase();
            document.querySelectorAll('.ticket-item').forEach(function (row) {
                row.style.display = !q || (row.dataset.search || '').includes(q) ? '' : 'none';
            });
        });
    }

    const modalEl = document.getElementById('commentModal');
    const statusForm = document.getElementById('statusUpdateForm');
    const statusInput = document.getElementById('modal_status');
    const commentInput = document.getElementById('status_comment');
    if (!modalEl) return;

    document.querySelectorAll('.status-select').forEach(function (select) {
        select.addEventListener('change', function () {
            const newStatus = this.value;
            const currentStatus = this.dataset.currentStatus;
            if (newStatus !== currentStatus) {
                statusForm.action = this.dataset.action;
                statusInput.value = newStatus;
                commentInput.value = '';
                bootstrap.Modal.getOrCreateInstance(modalEl).show();
            } else {
                this.value = currentStatus;
            }
        });
    });

    modalEl.addEventListener('hidden.bs.modal', function () {
        commentInput.value = '';
        statusInput.value = '';
        document.querySelectorAll('.status-select').forEach(function (select) {
            select.value = select.dataset.currentStatus;
        });
    });
});
</script>
@endif
@endpush
