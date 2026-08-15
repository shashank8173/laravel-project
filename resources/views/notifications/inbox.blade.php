@extends('layouts.app')

@section('title', 'My Notifications')
@section('heading', 'My Notifications')

@section('page_actions')
<a href="{{ route('settings.notifications') }}" class="btn btn-outline-secondary">Sound settings</a>
@endsection

@section('content')
<div class="card-soft p-0 overflow-hidden">
    <div class="table-responsive">
        <table class="table mb-0 align-middle">
            <thead>
            <tr>
                <th>Notification</th>
                <th>From</th>
                <th>When</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            @forelse($items as $n)
                <tr class="{{ $n->isUnread() ? 'table-warning' : '' }}">
                    <td>
                        <div class="fw-semibold">{{ $n->title }}</div>
                        @if($n->body)<div class="small text-muted">{{ $n->body }}</div>@endif
                    </td>
                    <td>{{ $n->actor?->full_name ?: '—' }}</td>
                    <td class="small text-muted">{{ optional($n->created_at)->format('d M Y, h:i A') }}</td>
                    <td class="text-end">
                        @if($n->link)
                            <a href="{{ $n->link }}" class="btn btn-sm btn-outline-primary">Open</a>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="4" class="text-center text-muted py-4">No notifications yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="p-3">{{ $items->links() }}</div>
</div>
@endsection
