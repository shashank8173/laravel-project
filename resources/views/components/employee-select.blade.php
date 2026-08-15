@props([
    'name',
    'employees' => [],
    'selected' => null,
    'multiple' => false,
    'required' => false,
    'placeholder' => null,
    'exclude' => [],
    'id' => null,
    'class' => 'form-select',
    'ajax' => true,
])

@php
    $selectedValues = collect(is_array($selected) ? $selected : ($selected !== null && $selected !== '' ? [$selected] : []))
        ->map(fn ($v) => (int) $v)
        ->filter(fn ($v) => $v > 0)
        ->values()
        ->all();
    $excludeValues = collect($exclude)->map(fn ($v) => (int) $v)->filter()->values()->all();
    $isMultiple = (bool) $multiple;
    $placeholderText = $placeholder ?: ($isMultiple ? 'Search & select employees…' : 'Search employee by name, department or designation…');
@endphp

<select
    name="{{ $name }}"
    @if($id) id="{{ $id }}" @endif
    class="js-employee-select {{ $class }}"
    data-placeholder="{{ $placeholderText }}"
    data-ajax="{{ $ajax ? '1' : '0' }}"
    @if($excludeValues !== []) data-exclude="{{ implode(',', $excludeValues) }}" @endif
    @if($isMultiple) multiple @endif
    @if($required) required @endif
    {{ $attributes->except(['class', 'name', 'id', 'multiple', 'required']) }}
>
    @unless($isMultiple)
        <option value="">{{ $placeholderText }}</option>
    @endunless

    @foreach($employees as $emp)
        @continue(in_array((int) $emp->id, $excludeValues, true))
        <option
            value="{{ $emp->id }}"
            data-name="{{ $emp->full_name }}"
            data-department="{{ $emp->department?->name }}"
            data-designation="{{ $emp->designation?->name }}"
            data-department-id="{{ $emp->department_id }}"
            data-designation-id="{{ $emp->designation_id }}"
            data-emp-id="{{ $emp->emp_id }}"
            @selected(in_array((int) $emp->id, $selectedValues, true))
        >
            {{ $emp->full_name }}
            @if($emp->designation?->name) — {{ $emp->designation->name }} @endif
            @if($emp->department?->name) · {{ $emp->department->name }} @endif
        </option>
    @endforeach
</select>
