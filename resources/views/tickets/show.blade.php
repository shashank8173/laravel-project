@extends('layouts.app')

@section('title', 'Ticket #'.$ticket->TicketID)
@section('heading', 'Ticket #'.$ticket->TicketID)

@section('page_actions')
@if(auth()->user()->isAdmin())
    <a href="{{ route('tickets.manage') }}" class="btn btn-outline-secondary me-2">HR Management</a>
@endif
<a href="{{ route('tickets.index') }}" class="btn btn-outline-secondary">Back</a>
@endsection

@push('styles')
@include('tickets.partials.styles')
@endpush

@section('content')
<div class="tk-wrap">
    <div class="row g-3">
        <div class="col-lg-8">
            <div class="tk-panel mb-3">
                <div class="tk-panel-head">
                    <div>
                        <div class="small text-muted mb-1">Ticket details</div>
                        <h5>{{ $ticket->Title }}</h5>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        @include('tickets.partials.pills', ['status' => $ticket->Status, 'priority' => $ticket->Priority])
                    </div>
                </div>
                <div class="tk-panel-body">
                    <div class="tk-detail-grid mb-3">
                        <div class="tk-meta-card">
                            <div class="k">Category</div>
                            <p class="v">{{ $ticket->category?->name ?? '—' }}</p>
                        </div>
                        <div class="tk-meta-card">
                            <div class="k">Employee</div>
                            <p class="v">
                                @if($ticket->employee && auth()->user()->isAdmin())
                                    <a href="{{ route('employees.show', $ticket->employee) }}">{{ $ticket->employee->full_name }}</a>
                                @else
                                    {{ $ticket->employee?->full_name ?? '—' }}
                                @endif
                            </p>
                        </div>
                        <div class="tk-meta-card">
                            <div class="k">Rating</div>
                            <p class="v">{{ $ticket->Rating ?? '—' }}</p>
                        </div>
                        <div class="tk-meta-card">
                            <div class="k">Created</div>
                            <p class="v">{{ optional($ticket->CreatedAt)->format('d M Y, H:i') ?? '—' }}</p>
                        </div>
                    </div>
                    <div class="tk-meta-card">
                        <div class="k">Description</div>
                        <div class="v hrm-rich-content" style="font-weight:500;">{!! $ticket->Description ?: '<em>No description provided.</em>' !!}</div>
                    </div>
                </div>
            </div>

            <div class="tk-panel">
                <div class="tk-panel-head">
                    <h5>Conversation</h5>
                    <span class="tk-sub">{{ $ticket->comments->count() }} comment(s)</span>
                </div>
                <div class="tk-panel-body">
                    @forelse($ticket->comments as $comment)
                        <div class="tk-comment d-flex gap-2">
                            <div class="tk-avatar">{{ strtoupper(substr($comment->author?->fname ?? 'U', 0, 1)) }}</div>
                            <div class="flex-grow-1">
                                <div class="meta">{{ $comment->author?->full_name ?? 'User' }} · {{ optional($comment->created_at)->format('d M Y H:i') }}</div>
                                <div>{{ $comment->comment }}</div>
                            </div>
                        </div>
                    @empty
                        <div class="tk-empty py-4">
                            <i class="fa-regular fa-comments"></i>
                            No comments yet.
                        </div>
                    @endforelse

                    @if($ticket->Status !== 'Closed')
                        <form method="POST" action="{{ route('tickets.comment', $ticket->TicketID) }}" class="mt-3">
                            @csrf
                            <label class="form-label fw-semibold">Add comment</label>
                            <textarea name="comment" class="form-control mb-2" rows="3" required placeholder="Write a reply…">{{ old('comment') }}</textarea>
                            <button class="btn add-btn"><i class="fa-solid fa-reply me-1"></i> Post Comment</button>
                        </form>
                    @else
                        <p class="text-muted small mb-0 mt-2">Closed tickets cannot receive new comments.</p>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="tk-panel">
                <div class="tk-panel-head"><h5>Update status</h5></div>
                <div class="tk-panel-body">
                    @if(($allowedStatuses ?? []) !== [])
                    <form method="POST" action="{{ route('tickets.status', $ticket->TicketID) }}">
                        @csrf
                        @method('PATCH')
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Status</label>
                            <select name="Status" class="form-select" required>
                                @foreach($allowedStatuses as $s)
                                    <option value="{{ $s }}" @selected(old('Status', $ticket->Status) === $s)>{{ $s }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Comment (optional)</label>
                            <textarea name="comment" class="form-control" rows="3" placeholder="Note with status change…"></textarea>
                        </div>
                        @if(in_array('Closed', $allowedStatuses, true))
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Rating (when closing)</label>
                            <input type="number" name="Rating" min="1" max="10" class="form-control" value="{{ old('Rating', $ticket->Rating) }}" placeholder="1–10">
                        </div>
                        @endif
                        <button class="btn add-btn w-100">Save changes</button>
                    </form>
                    @else
                        <p class="text-muted small mb-0">Status is managed by HR while the ticket is open or in progress. You can close it after it is resolved.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
