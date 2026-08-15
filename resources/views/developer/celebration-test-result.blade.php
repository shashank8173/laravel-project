@extends('layouts.app')

@section('title', 'Celebration Test')
@section('heading', 'Notification test result')

@section('page_actions')
<a href="{{ route('developer.cron') }}" class="btn btn-outline-secondary btn-sm">Back to Cron Jobs</a>
@endsection

@push('styles')
<style>
    .tr-wrap { --ink:#0f2744; --muted:#6b7c93; --line:#e8eef5; --accent:#ff9b44; }
    .tr-wrap .panel { background:#fff; border:1px solid var(--line); border-radius:16px; padding:1.15rem; margin-bottom:1rem; }
    .tr-wrap .ok { color:#15803d; font-weight:700; }
    .tr-wrap .bad { color:#b91c1c; font-weight:700; }
    .tr-wrap .msg { background:#0f2744; color:#e8eef5; border-radius:12px; padding:.85rem 1rem; font-family:ui-monospace,Consolas,monospace; font-size:.85rem; }
</style>
@endpush

@section('content')
<div class="tr-wrap">
    <div class="panel">
        <h5 class="mb-2">Result ({{ $mode }})</h5>
        <div class="msg mb-3">{{ $result['message'] }}</div>
        <p class="mb-1">
            Sent: <span class="{{ ($result['sent'] ?? 0) > 0 ? 'ok' : '' }}">{{ $result['sent'] ?? 0 }}</span>
            · Failed: <span class="{{ ($result['failed'] ?? 0) > 0 ? 'bad' : '' }}">{{ $result['failed'] ?? 0 }}</span>
            · Date: <strong>{{ $result['date'] ?? '-' }}</strong>
        </p>
        @if(($result['failed'] ?? 0) > 0)
            <p class="bad mb-0">SMTP may have failed — check Email Settings.</p>
        @elseif(($result['sent'] ?? 0) > 0)
            <p class="ok mb-0">Notifications were sent to official emails. Please check the inbox.</p>
        @elseif(($result['dry_run'] ?? false))
            <p class="text-muted mb-0">This is a preview only — no emails were sent. Use Send to deliver real messages.</p>
        @endif
    </div>

    <div class="row g-3">
        <div class="col-md-4">
            <div class="panel">
                <h6>Birthdays</h6>
                @forelse(($result['birthdays'] ?? []) as $row)
                    <div>{{ $row['name'] }} &lt;{{ $row['email'] }}&gt;</div>
                @empty
                    <div class="text-muted">None</div>
                @endforelse
            </div>
        </div>
        <div class="col-md-4">
            <div class="panel">
                <h6>Anniversaries</h6>
                @forelse(($result['anniversaries'] ?? []) as $row)
                    <div>{{ $row['name'] }} &lt;{{ $row['email'] }}&gt;</div>
                @empty
                    <div class="text-muted">None</div>
                @endforelse
            </div>
        </div>
        <div class="col-md-4">
            <div class="panel">
                <h6>Holidays</h6>
                @forelse(($result['holidays'] ?? []) as $row)
                    <div>{{ $row['name'] }}@if(!empty($row['date'])) <span class="text-muted">({{ $row['date'] }})</span>@endif</div>
                @empty
                    <div class="text-muted">None</div>
                @endforelse
            </div>
        </div>
    </div>

    <div class="panel d-flex flex-wrap gap-2">
        <a class="btn btn-outline-secondary" href="{{ $previewUrl }}">Preview again</a>
        <a class="btn" style="background:#ff9b44;color:#fff;font-weight:700;" href="{{ $sendUrl }}"
           onclick="return confirm('Really send emails now?')">Send now</a>
        <a class="btn btn-outline-secondary" href="{{ route('developer.cron') }}">Cron guide</a>
        <a class="btn btn-outline-secondary" href="{{ route('settings.greetings') }}">Greeting Cards</a>
        <a class="btn btn-outline-secondary" href="{{ route('holidays.index') }}">Holidays</a>
    </div>
</div>
@endsection
