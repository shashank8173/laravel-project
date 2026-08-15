@extends('layouts.app')

@section('title', 'Migration Status')
@section('heading', 'Laravel 11 Migration Status')

@section('content')
<div class="alert alert-info">
    Old app: <code>hrmpulse_live-main</code> (still live). New app: <code>hrmpulse-laravel</code> on the same MySQL DB
    <code>u179772606_hrmdbnew</code>. Modules are moved one-by-one — not a big-bang rewrite.
</div>

<div class="card-soft p-3">
    <table class="table align-middle mb-0">
        <thead>
        <tr>
            <th>Module</th>
            <th>Status</th>
            <th>Legacy files</th>
        </tr>
        </thead>
        <tbody>
        @foreach($modules as $module)
            <tr>
                <td>{{ $module['name'] }}</td>
                <td>
                    @if($module['status'] === 'done')
                        <span class="badge text-bg-success">Done</span>
                    @elseif($module['status'] === 'partial')
                        <span class="badge text-bg-warning">Partial</span>
                    @else
                        <span class="badge text-bg-secondary">Todo</span>
                    @endif
                </td>
                <td class="small text-muted">{{ $module['legacy'] }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endsection
