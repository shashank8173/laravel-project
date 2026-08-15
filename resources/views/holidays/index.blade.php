@extends('layouts.app')

@section('title', 'Holidays')
@section('heading', 'Holidays')

@section('page_actions')
<a href="{{ $isAdmin ? route('leaves.admin') : route('leaves.employee') }}" class="btn btn-outline-secondary me-1">
    <i class="fa-solid fa-calendar-days me-1"></i> Leaves
</a>
@if($isAdmin)
<a href="{{ route('settings.greetings') }}" class="btn btn-outline-secondary me-1">
    <i class="fa-solid fa-image me-1"></i> Holiday cards
</a>
<button type="button" class="btn add-btn" data-bs-toggle="modal" data-bs-target="#addHolidayModal">
    <i class="fa-solid fa-plus"></i> Add holiday
</button>
@endif
@endsection

@push('styles')
<style>
    .hd-wrap { --ink:#0f2744; --muted:#6b7c93; --line:#e8eef5; --soft:#f4f7fb; --accent:#ff9b44; }
    .hd-wrap .hd-panel { background:#fff; border:1px solid var(--line); border-radius:18px; overflow:hidden; }
    .hd-wrap .hd-panel-head {
        display:flex; justify-content:space-between; align-items:center; gap:1rem; flex-wrap:wrap;
        padding:1rem 1.15rem; border-bottom:1px solid var(--line);
        background:linear-gradient(180deg,#fff,#fafbfd);
    }
    .hd-wrap .hd-panel-head h5 { margin:0; font-weight:750; color:var(--ink); }
    .hd-wrap .hd-panel-body { padding:1.15rem; }
    .hd-wrap .hd-metric {
        border:1px solid var(--line); border-radius:14px; background:#fff; padding:1rem 1.05rem; height:100%;
        position:relative; overflow:hidden;
    }
    .hd-wrap .hd-metric::before { content:""; position:absolute; left:0; top:0; bottom:0; width:4px; background:var(--accent); }
    .hd-wrap .hd-metric.is-blue::before { background:#2563eb; }
    .hd-wrap .hd-metric.is-green::before { background:#16a34a; }
    .hd-wrap .hd-metric.is-purple::before { background:#7c3aed; }
    .hd-wrap .hd-metric .k { font-size:.68rem; text-transform:uppercase; letter-spacing:.04em; color:var(--muted); font-weight:700; }
    .hd-wrap .hd-metric .v { font-size:1.25rem; font-weight:800; color:var(--ink); margin:.15rem 0 0; line-height:1.15; }
    .hd-wrap .hd-metric .hint { font-size:.72rem; color:var(--muted); margin-top:.2rem; }
    .hd-wrap .form-label { font-size:.8rem; font-weight:600; color:var(--muted); }
    .hd-wrap .table > :not(caption) > * > * { vertical-align:middle; }
    .hd-wrap .name { font-weight:750; color:var(--ink); margin:0; }
    .hd-wrap .sub { font-size:.78rem; color:var(--muted); }
    .hd-wrap .date-chip {
        display:inline-flex; flex-direction:column; align-items:center; justify-content:center;
        width:52px; height:52px; border-radius:12px; background:#fff7ed; color:#9a3412;
        border:1px solid #ffe0c2; flex-shrink:0;
    }
    .hd-wrap .date-chip .m { font-size:.65rem; font-weight:800; text-transform:uppercase; line-height:1; }
    .hd-wrap .date-chip .d { font-size:1.15rem; font-weight:800; line-height:1.1; margin-top:.1rem; }
    .hd-wrap .days-pill {
        display:inline-flex; padding:.28rem .65rem; border-radius:999px; font-size:.72rem; font-weight:700;
        background:#eff6ff; color:#1d4ed8;
    }
    .hd-wrap .tag-upcoming { background:#dcfce7; color:#15803d; }
    .hd-wrap .tag-past { background:#e2e8f0; color:#475569; }
    .hd-wrap .year-chip {
        display:inline-flex; padding:.22rem .5rem; border-radius:8px; background:var(--soft);
        color:var(--ink); font-size:.78rem; font-weight:700;
    }
</style>
@endpush

@section('content')
@php
    $parseDate = function ($value) {
        try {
            return $value ? \Carbon\Carbon::parse($value) : null;
        } catch (\Throwable) {
            return null;
        }
    };
    $nextName = $stats['next']['name'] ?? '—';
    $nextDate = isset($stats['next']['date']) ? $stats['next']['date']->format('d M Y') : 'No upcoming';
@endphp
<div class="hd-wrap">
    <div class="row g-3 mb-3">
        <div class="col-6 col-lg-3">
            <div class="hd-metric">
                <div class="k">Year</div>
                <p class="v">{{ $stats['year'] }}</p>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="hd-metric is-blue">
                <div class="k">Holidays</div>
                <p class="v">{{ $stats['count'] }}</p>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="hd-metric is-green">
                <div class="k">Total days</div>
                <p class="v">{{ $stats['days'] }}</p>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="hd-metric is-purple">
                <div class="k">Next holiday</div>
                <p class="v" style="font-size:1rem;">{{ $nextName }}</p>
                <div class="hint">{{ $nextDate }} · {{ $stats['upcoming'] }} upcoming</div>
            </div>
        </div>
    </div>

    <div class="hd-panel mb-3">
        <div class="hd-panel-head">
            <h5><i class="fa-solid fa-filter me-1" style="color:var(--accent)"></i> Filters</h5>
            @if($q !== '' || (int) $year !== (int) now()->year)
                <a href="{{ route('holidays.index') }}" class="btn btn-sm btn-outline-secondary">Clear</a>
            @endif
        </div>
        <div class="hd-panel-body">
            <form method="GET" action="{{ route('holidays.index') }}" class="row g-2 align-items-end">
                <div class="col-md-5">
                    <label class="form-label">Search</label>
                    <input type="search" name="q" value="{{ $q }}" class="form-control" placeholder="Holiday name or date">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Year</label>
                    <select name="year" class="form-select">
                        @foreach($years as $y)
                            <option value="{{ $y }}" @selected((int) $year === (int) $y)>{{ $y }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <button class="btn add-btn me-1">Apply</button>
                    <a href="{{ route('holidays.index') }}" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="hd-panel">
        <div class="hd-panel-head">
            <h5><i class="fa-solid fa-snowflake me-1" style="color:var(--accent)"></i> Holiday calendar</h5>
            <span class="small text-muted">{{ $holidays->total() }} record(s)</span>
        </div>
        <div class="table-responsive p-2">
            <table class="table align-middle mb-0">
                <thead>
                <tr>
                    <th>Date</th>
                    <th>Holiday</th>
                    <th>Year</th>
                    <th>Days</th>
                    <th>Status</th>
                    @if($isAdmin)
                        <th class="text-end">Actions</th>
                    @endif
                </tr>
                </thead>
                <tbody>
                @forelse($holidays as $holiday)
                    @php
                        $date = $parseDate($holiday->date);
                        $isUpcoming = $date && $date->isFuture();
                        $isToday = $date && $date->isToday();
                    @endphp
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <span class="date-chip">
                                    <span class="m">{{ $date ? $date->format('M') : '—' }}</span>
                                    <span class="d">{{ $date ? $date->format('d') : '?' }}</span>
                                </span>
                                <div>
                                    <div class="name">{{ $date ? $date->format('D, d M Y') : ($holiday->date ?: '—') }}</div>
                                    <div class="sub">{{ $date ? $date->diffForHumans() : '' }}</div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <p class="name">{{ $holiday->name }}</p>
                        </td>
                        <td><span class="year-chip">{{ $holiday->year }}</span></td>
                        <td><span class="days-pill">{{ $holiday->no_of_days ?: 1 }} day(s)</span></td>
                        <td>
                            @if($isToday)
                                <span class="days-pill" style="background:#fff7ed;color:#9a3412;">Today</span>
                            @elseif($isUpcoming)
                                <span class="days-pill tag-upcoming">Upcoming</span>
                            @else
                                <span class="days-pill tag-past">Past</span>
                            @endif
                        </td>
                        @if($isAdmin)
                            <td class="text-end">
                                <form method="POST" action="{{ route('holidays.destroy', $holiday) }}" class="d-inline"
                                      onsubmit="return confirm('Delete this holiday?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger">Delete</button>
                                </form>
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $isAdmin ? 6 : 5 }}" class="text-center text-muted py-4">No holidays found for {{ $year }}.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-3 border-top">{{ $holidays->links() }}</div>
    </div>
</div>

@if($isAdmin)
<div class="modal fade" id="addHolidayModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border:0;border-radius:16px;">
            <form method="POST" action="{{ route('holidays.store') }}">
                @csrf
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold">Add holiday</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body hd-wrap">
                    <div class="mb-3">
                        <label class="form-label">Name</label>
                        <input type="text" name="name" class="form-control" placeholder="e.g. Diwali" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Date</label>
                        <input type="date" name="date" class="form-control" required>
                    </div>
                    <div class="row g-3">
                        <div class="col-6">
                            <label class="form-label">Year</label>
                            <input type="number" name="year" class="form-control" value="{{ $year }}" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label">No. of days</label>
                            <input type="number" name="no_of_days" class="form-control" value="1" min="1" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button class="btn add-btn">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
@endsection

@if($errors->any() && $isAdmin)
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const el = document.getElementById('addHolidayModal');
    if (el) new bootstrap.Modal(el).show();
});
</script>
@endpush
@endif
