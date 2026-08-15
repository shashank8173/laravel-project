@extends('layouts.app')

@section('title', 'Tickets')
@section('heading', 'Tickets')

@section('page_actions')
<button type="button" class="btn add-btn me-2" data-bs-toggle="modal" data-bs-target="#raiseTicketModal">
    <i class="fa-solid fa-plus"></i> Raise Ticket
</button>
@if(auth()->user()->isAdmin())
    <a href="{{ route('tickets.manage') }}" class="btn btn-outline-secondary">
        <i class="fas fa-ticket-alt"></i> HR Management
    </a>
@endif
@endsection

@push('styles')
@include('tickets.partials.styles')
@endpush

@section('content')
@php
    $isAdmin = auth()->user()->isAdmin();
    $filterBase = array_filter(['q' => $q !== '' ? $q : null]);
@endphp
<div class="tk-wrap">
    <div class="tk-hero">
        <div>
            <h3>{{ $isAdmin ? 'Support tickets' : 'My support tickets' }}</h3>
            <p>Track requests, filter by status, and raise a new ticket when you need help from HR.</p>
        </div>
        <button type="button" class="btn add-btn" data-bs-toggle="modal" data-bs-target="#raiseTicketModal">
            <i class="fa-solid fa-paper-plane me-1"></i> New ticket
        </button>
    </div>

    <div class="row g-3 mb-3 tk-metrics">
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
                <div class="tk-label">Resolved</div>
                <p class="tk-value">{{ $counts['resolved'] }}</p>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="tk-metric is-closed">
                <div class="tk-icon"><i class="fa-solid fa-lock"></i></div>
                <div class="tk-label">Closed</div>
                <p class="tk-value">{{ $counts['closed'] }}</p>
            </div>
        </div>
    </div>

    <div class="tk-panel mb-3">
        <div class="tk-panel-head">
            <h5><i class="fa-solid fa-magnifying-glass me-1" style="color:var(--tk-accent)"></i> Find tickets</h5>
            <span class="small text-muted">{{ $counts['all'] }} total</span>
        </div>
        <div class="tk-panel-body">
            <form method="GET" action="{{ route('tickets.index') }}" class="row g-2 align-items-end mb-3">
                <div class="col-md-8">
                    <label class="form-label">Search</label>
                    <input type="search" name="q" value="{{ $q }}" class="form-control"
                           placeholder="Title, ID, category, description…">
                </div>
                <div class="col-md-4 d-flex gap-2">
                    <button class="btn add-btn flex-fill">Search</button>
                    @if($q !== '' || $status)
                        <a href="{{ route('tickets.index') }}" class="btn btn-outline-secondary">Clear</a>
                    @endif
                </div>
                @if($status)
                    <input type="hidden" name="status" value="{{ $status }}">
                @endif
            </form>

            <div class="tk-filter-pills">
                <a class="tk-filter-pill {{ ! $status ? 'active' : '' }}"
                   href="{{ route('tickets.index', $filterBase) }}">All · {{ $counts['all'] }}</a>
                <a class="tk-filter-pill {{ $status === 'Open' ? 'active' : '' }}"
                   href="{{ route('tickets.index', $filterBase + ['status' => 'Open']) }}">Open</a>
                <a class="tk-filter-pill {{ $status === 'In Progress' ? 'active' : '' }}"
                   href="{{ route('tickets.index', $filterBase + ['status' => 'In Progress']) }}">In Progress</a>
                <a class="tk-filter-pill {{ $status === 'Resolved' ? 'active' : '' }}"
                   href="{{ route('tickets.index', $filterBase + ['status' => 'Resolved']) }}">Resolved</a>
                <a class="tk-filter-pill {{ $status === 'Closed' ? 'active' : '' }}"
                   href="{{ route('tickets.index', $filterBase + ['status' => 'Closed']) }}">Closed</a>
                <a class="tk-filter-pill {{ $status === 'Reopened' ? 'active' : '' }}"
                   href="{{ route('tickets.index', $filterBase + ['status' => 'Reopened']) }}">Reopened</a>
            </div>
        </div>
    </div>

    <div class="tk-panel">
        <div class="tk-panel-head">
            <h5>{{ $isAdmin ? 'All tickets' : 'My tickets' }}</h5>
            <span class="small text-muted">{{ $tickets->total() }} showing</span>
        </div>

        <div class="d-none d-lg-grid px-3 py-2 small text-uppercase fw-bold text-muted"
             style="grid-template-columns: 72px 1.6fr .9fr .7fr .85fr auto; gap:.75rem; letter-spacing:.04em; font-size:.68rem;">
            <div>ID</div>
            <div>Ticket</div>
            <div>Category</div>
            <div>Priority</div>
            <div>Status</div>
            <div class="text-end">Action</div>
        </div>

        @forelse($tickets as $ticket)
            <div class="tk-row" style="grid-template-columns: 72px 1.6fr .9fr .7fr .85fr auto;">
                <div class="tk-id">#{{ $ticket->TicketID }}</div>
                <div>
                    <p class="tk-title">
                        <a href="{{ route('tickets.show', $ticket->TicketID) }}" class="text-decoration-none" style="color:inherit;">
                            {{ $ticket->Title }}
                        </a>
                    </p>
                    <p class="tk-sub">
                        @if($isAdmin && $ticket->employee)
                            {{ $ticket->employee->full_name }} ·
                        @endif
                        {{ optional($ticket->CreatedAt)->format('d M Y') ?? '—' }}
                    </p>
                </div>
                <div class="tk-sub d-none d-lg-block">{{ $ticket->category?->name ?? '—' }}</div>
                <div>@include('tickets.partials.pills', ['priority' => $ticket->Priority])</div>
                <div>@include('tickets.partials.pills', ['status' => $ticket->Status])</div>
                <div class="tk-actions">
                    <a href="{{ route('tickets.show', $ticket->TicketID) }}" class="btn tk-btn tk-btn-primary">
                        <i class="fa-regular fa-eye"></i> Open
                    </a>
                </div>
            </div>
        @empty
            <div class="tk-empty">
                <i class="fa-solid fa-inbox"></i>
                No tickets found.
                <div class="mt-3">
                    <button type="button" class="btn add-btn" data-bs-toggle="modal" data-bs-target="#raiseTicketModal">
                        Raise your first ticket
                    </button>
                </div>
            </div>
        @endforelse

        @if($tickets->hasPages())
            <div class="p-3 border-top">{{ $tickets->links() }}</div>
        @endif
    </div>
</div>

<div class="modal fade tk-modal" id="raiseTicketModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <form method="POST" action="{{ route('tickets.store') }}">
                @csrf
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title mb-0">Raise a ticket</h5>
                        <div class="small text-muted">Describe the issue clearly so HR can respond faster.</div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body tk-wrap">
                    @if($categories->isEmpty())
                        <div class="alert alert-warning mb-0">
                            No ticket categories available yet.
                            @if($isAdmin)
                                <a href="{{ route('tickets.manage', ['tab' => 'categories']) }}">Add a category</a> first.
                            @endif
                        </div>
                    @else
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Category</label>
                                <select name="CategoryID" class="form-select" required>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}" @selected((string) old('CategoryID') === (string) $category->id)>
                                            {{ $category->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Priority</label>
                                <div class="tk-prio-group">
                                    @foreach(['Low', 'Medium', 'High'] as $priority)
                                        <label class="tk-prio-option">
                                            <input type="radio" name="Priority" value="{{ $priority }}"
                                                   @checked(old('Priority', 'Medium') === $priority) required>
                                            <span class="prio-{{ strtolower($priority) }}">{{ $priority }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Title</label>
                                <input type="text" name="Title" class="form-control" value="{{ old('Title') }}"
                                       placeholder="Short summary of the issue" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Description</label>
                                <textarea name="Description" class="form-control js-rich-editor" rows="5"
                                          placeholder="Click to describe what happened…">{{ old('Description') }}</textarea>
                            </div>
                        </div>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn tk-btn tk-btn-ghost" data-bs-dismiss="modal">Cancel</button>
                    @if($categories->isNotEmpty())
                        <button class="btn add-btn"><i class="fa-solid fa-paper-plane me-1"></i> Submit ticket</button>
                    @endif
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@if($errors->any() || old('Title') || old('CategoryID'))
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const el = document.getElementById('raiseTicketModal');
    if (el) bootstrap.Modal.getOrCreateInstance(el).show();
});
</script>
@endpush
@endif
