@extends('layouts.app')

@section('title', 'Salary')
@section('heading', 'Employee Salary')

@section('page_actions')
<a href="{{ route('salary.calculate') }}" class="btn add-btn me-2"><i class="fa-solid fa-calculator"></i> Calculate Salary</a>
<a href="{{ route('salary.advances') }}" class="btn btn-outline-secondary">Advance Salary</a>
@endsection

@section('content')
<form class="row g-2 mb-3" method="GET" action="{{ route('salary.index') }}">
    <div class="col-md-8">
        <input type="search" name="q" value="{{ $q }}" class="form-control" placeholder="Search name or emp id">
    </div>
    <div class="col-md-4">
        <button class="btn btn-primary">Search</button>
        <a href="{{ route('salary.index') }}" class="btn btn-outline-secondary">Reset</a>
    </div>
</form>

<div class="card-soft p-3">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
            <tr>
                <th>Employee</th>
                <th>Emp ID</th>
                <th>Designation</th>
                <th>Current Salary</th>
                <th>Update</th>
                <th>Slip</th>
            </tr>
            </thead>
            <tbody>
            @forelse($employees as $employee)
                <tr>
                    <td>{{ $employee->full_name }}</td>
                    <td>{{ $employee->emp_id ?? '—' }}</td>
                    <td>{{ $employee->designation?->name ?? '—' }}</td>
                    <td>{{ number_format((float)($employee->salary ?? 0), 2) }}</td>
                    <td>
                        <form method="POST" action="{{ route('salary.update', $employee) }}" class="d-flex gap-1">
                            @csrf
                            @method('PUT')
                            <input type="number" step="0.01" min="0" name="salary" class="form-control form-control-sm" style="max-width:140px" value="{{ $employee->salary }}" required>
                            <button class="btn btn-sm btn-primary">Save</button>
                        </form>
                    </td>
                    <td>
                        <a class="btn btn-sm btn-outline-primary"
                           href="{{ route('salary.calculate', ['id' => $employee->id, 'month' => now()->month, 'year' => now()->year]) }}">
                            Calculate
                        </a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-muted">No employees found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-3">{{ $employees->links() }}</div>
</div>
@endsection
