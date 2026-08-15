@extends('layouts.app')

@section('title', 'Create Project')
@section('heading', 'Create Project')

@section('page_actions')
<a href="{{ route('projects.index') }}" class="btn btn-outline-secondary">
    <i class="la la-arrow-left"></i> Back
</a>
@endsection

@push('styles')
<style>
    .pj-wrap { --ink:#0f2744; --muted:#6b7c93; --line:#e8eef5; }
    .pj-wrap .pj-panel { background:#fff; border:1px solid var(--line); border-radius:18px; overflow:hidden; }
    .pj-wrap .pj-panel-head {
        padding:1rem 1.15rem; border-bottom:1px solid var(--line);
        background:linear-gradient(180deg,#fff,#fafbfd);
    }
    .pj-wrap .pj-panel-head h5 { margin:0; font-weight:750; color:var(--ink); }
    .pj-wrap .pj-panel-body { padding:1.25rem; }
    .pj-wrap .form-label { font-size:.8rem; font-weight:600; color:var(--muted); }
</style>
@endpush

@section('content')
<div class="pj-wrap">
    <div class="pj-panel">
        <div class="pj-panel-head"><h5>Project details</h5></div>
        <div class="pj-panel-body">
            <form method="POST" action="{{ route('projects.store') }}">
                @csrf
                @include('projects._form', ['project' => null, 'employees' => $employees, 'priorities' => $priorities, 'statuses' => $statuses, 'includeTeam' => true])
                <div class="mt-3 d-flex gap-2">
                    <button type="submit" class="btn add-btn">Create Project</button>
                    <a href="{{ route('projects.index') }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
