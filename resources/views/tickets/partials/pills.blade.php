@php
    $statusSlug = strtolower(str_replace(' ', '-', (string) ($status ?? '')));
    $prioSlug = strtolower((string) ($priority ?? ''));
@endphp
@if($status ?? false)
    <span class="tk-pill dot status-{{ $statusSlug }}">{{ $status }}</span>
@endif
@if($priority ?? false)
    <span class="tk-pill prio-{{ $prioSlug }}">{{ $priority }}</span>
@endif
