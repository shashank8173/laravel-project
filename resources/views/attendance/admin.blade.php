@extends('layouts.app')

@section('title', 'Attendance Admin')
@section('heading', 'Attendance (Admin)')

@section('page_actions')
<a href="{{ route('attendance.all') }}" class="btn add-btn me-2"><i class="fa-solid fa-users"></i> Attendance All (HRM)</a>
<a href="{{ route('attendance.upload') }}" class="btn add-btn me-2"><i class="fa-solid fa-upload"></i> Upload Attendance</a>
<a href="{{ route('attendance.mine') }}" class="btn btn-outline-secondary">My Attendance</a>
@endsection

@section('content')
<form class="row g-2 mb-3" method="GET" action="{{ route('attendance.admin') }}">
    <div class="col-md-4">
        <x-employee-select
            name="employee_id"
            :employees="$employees"
            :selected="$employeeId"
            placeholder="All employees (search…)"
        />
    </div>
    <div class="col-md-2">
        <select name="month" class="form-select">
            @for($m = 1; $m <= 12; $m++)
                <option value="{{ $m }}" @selected((int)$month === $m)>{{ date('F', mktime(0, 0, 0, $m, 1)) }}</option>
            @endfor
        </select>
    </div>
    <div class="col-md-2">
        <select name="year" class="form-select">
            @for($y = now()->year - 2; $y <= now()->year + 1; $y++)
                <option value="{{ $y }}" @selected((int)$year === $y)>{{ $y }}</option>
            @endfor
        </select>
    </div>
    <div class="col-md-2">
        <button class="btn btn-primary">Filter</button>
    </div>
</form>

<div class="card-soft p-3">
    <div class="table-responsive">
        <table class="table table-sm align-middle mb-0">
            <thead>
            <tr>
                <th>Employee</th>
                <th>Attendance ID</th>
                <th>Month</th>
                <th>Year</th>
                @for($d = 1; $d <= 31; $d++)
                    <th>{{ $d }}</th>
                @endfor
            </tr>
            </thead>
            <tbody>
            @forelse($rows as $row)
                <tr>
                    <td>{{ $row->employee_name ?? '—' }}</td>
                    <td>{{ $row->attandance_id ?? '—' }}</td>
                    <td>{{ $row->month }}</td>
                    <td>{{ $row->year }}</td>
                    @for($d = 1; $d <= 31; $d++)
                        <td class="small">{{ $row->{'date'.$d} ?? '' }}</td>
                    @endfor
                </tr>
            @empty
                <tr><td colspan="35" class="text-muted">No machine attendance rows found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-3">{{ $rows->links() }}</div>
</div>
@endsection
