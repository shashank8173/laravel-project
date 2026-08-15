@extends('layouts.app')

@section('title', 'Notice Period History')
@section('heading', 'Resignation History')

@section('page_actions')
<a href="{{ route('notice-period.index') }}" class="btn btn-outline-secondary">Back to Notice Period</a>
@endsection

@push('styles')
<style>
    .np-wrap { --ink:#0f2744; --muted:#6b7c93; --line:#e8eef5; }
    .np-wrap .np-panel { background:#fff; border:1px solid var(--line); border-radius:18px; overflow:hidden; }
    .np-wrap .np-panel-head {
        padding:1rem 1.15rem; border-bottom:1px solid var(--line); background:linear-gradient(180deg,#fff,#fafbfd);
    }
    .np-wrap .np-panel-head h5 { margin:0; font-weight:750; color:var(--ink); }
    .np-wrap .np-panel-body { padding:1.15rem; }
</style>
@endpush

@section('content')
<div class="np-wrap">
    <div class="np-panel">
        <div class="np-panel-head"><h5>All resignation history</h5></div>
        <div class="np-panel-body">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Status</th>
                        <th>Days</th>
                        <th>Changed by</th>
                        <th>When</th>
                        <th>Comment</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($rows as $row)
                        <tr>
                            <td class="fw-semibold">{{ $row->employee?->full_name ?? ('#'.$row->employee_id) }}</td>
                            <td>{{ $row->status }}</td>
                            <td>{{ $row->notice_period_days }}</td>
                            <td>{{ $row->changer?->full_name ?: '—' }}</td>
                            <td>{{ optional($row->changed_at)->format('d M Y H:i') }}</td>
                            <td>{{ $row->comment }}</td>
                            <td>
                                <a href="{{ route('notice-period.show', $row->employee_id) }}" class="btn btn-sm btn-outline-primary">Open</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-muted text-center py-4">No history.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $rows->links() }}</div>
        </div>
    </div>
</div>
@endsection
