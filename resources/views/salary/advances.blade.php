@extends('layouts.app')

@section('title', 'Advance Salary')
@section('heading', 'Advance Salary')

@section('content')
<div class="row g-3">
    <div class="col-lg-4">
        <div class="card-soft p-3">
            <h5 class="mb-3">Record Advance</h5>
            <form method="POST" action="{{ route('salary.advances.store') }}">
                @csrf
                <div class="mb-2">
                    <label class="form-label">Employee</label>
                    <x-employee-select
                        name="emp_id"
                        :employees="$employees"
                        :required="true"
                    />
                </div>
                <div class="mb-2">
                    <label class="form-label">Advance Amount</label>
                    <input type="number" step="0.01" min="1" name="advance_amount" class="form-control" required>
                </div>
                <div class="mb-2">
                    <label class="form-label">Monthly Deduction</label>
                    <input type="number" step="0.01" min="1" name="monthly_deduction" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Advance Date</label>
                    <input type="date" name="advance_date" class="form-control" value="{{ old('advance_date', now()->toDateString()) }}" required>
                </div>
                <button class="btn btn-primary w-100">Save Advance</button>
            </form>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card-soft p-3">
            <h5 class="mb-3">Advances</h5>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Amount</th>
                        <th>Monthly Deduction</th>
                        <th>Remaining</th>
                        <th>Date</th>
                        <th>Status</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($advances as $advance)
                        <tr>
                            <td>{{ $advance->employee?->full_name ?? '—' }}</td>
                            <td>{{ number_format((float)$advance->advance_amount, 2) }}</td>
                            <td>{{ number_format((float)$advance->monthly_deduction, 2) }}</td>
                            <td>{{ number_format((float)$advance->remaining_amount, 2) }}</td>
                            <td>{{ $advance->advance_date }}</td>
                            <td>{{ $advance->status }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-muted">No advances recorded.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $advances->links() }}</div>
        </div>
    </div>
</div>
@endsection
