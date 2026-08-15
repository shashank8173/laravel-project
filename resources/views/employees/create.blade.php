@extends('layouts.app')

@section('title', 'Add Employee')
@section('heading', 'Add Employee')

@section('page_actions')
<a href="{{ route('employees.index') }}" class="btn btn-outline-secondary">&larr; Back to Employees</a>
@endsection

@section('content')
<div class="mb-3"><a href="{{ route('employees.index') }}" class="btn btn-sm btn-outline-secondary">&larr; Back</a></div>

<form method="POST" action="{{ route('employees.store') }}" class="card-soft p-4">
    @csrf
    <div class="row g-3">
        <div class="col-md-4">
            <label class="form-label">First Name *</label>
            <input type="text" name="fname" value="{{ old('fname') }}" class="form-control" required>
        </div>
        <div class="col-md-4">
            <label class="form-label">Last Name</label>
            <input type="text" name="lname" value="{{ old('lname') }}" class="form-control">
        </div>
        <div class="col-md-4">
            <label class="form-label">DOB</label>
            <input type="date" name="dob" value="{{ old('dob') }}" class="form-control">
        </div>
        <div class="col-md-4">
            <label class="form-label">Gender</label>
            <select name="gender" class="form-select">
                <option value="1" @selected(old('gender')==1)>Male</option>
                <option value="2" @selected(old('gender')==2)>Female</option>
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label">Blood Group</label>
            <input type="text" name="bgroup" value="{{ old('bgroup') }}" class="form-control">
        </div>
        <div class="col-md-4">
            <label class="form-label">Marital Status</label>
            <select name="marital_status" class="form-select">
                <option value="1" @selected(old('marital_status')==1)>Married</option>
                <option value="2" @selected(old('marital_status', 2)==2)>Unmarried</option>
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label">Personal Email</label>
            <input type="email" name="email" value="{{ old('email') }}" class="form-control">
        </div>
        <div class="col-md-4">
            <label class="form-label">Office Email</label>
            <input type="email" name="office_email" value="{{ old('office_email') }}" class="form-control">
        </div>
        <div class="col-md-4">
            <label class="form-label">Mobile</label>
            <input type="text" name="mobile1" value="{{ old('mobile1') }}" class="form-control">
        </div>
        <div class="col-md-4">
            <label class="form-label">Emp ID</label>
            <input type="text" name="emp_id" value="{{ old('emp_id') }}" class="form-control">
        </div>
        <div class="col-md-4">
            <label class="form-label">Attendance Machine ID</label>
            <input type="number" name="attendance_id" value="{{ old('attendance_id') }}" class="form-control">
        </div>
        <div class="col-md-4">
            <label class="form-label">Password (default Welcome@123)</label>
            <input type="text" name="password" value="{{ old('password') }}" class="form-control" placeholder="Welcome@123">
        </div>
        <div class="col-md-4">
            <label class="form-label">Department</label>
            <select name="department_id" class="form-select">
                <option value="">—</option>
                @foreach($departments as $d)
                    <option value="{{ $d->id }}" @selected(old('department_id')==$d->id)>{{ $d->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label">Designation</label>
            <select name="designation_id" class="form-select">
                <option value="">—</option>
                @foreach($designations as $d)
                    <option value="{{ $d->id }}" @selected(old('designation_id')==$d->id)>{{ $d->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label">Role</label>
            <select name="role" class="form-select">
                <option value="user">user</option>
                <option value="admin">admin</option>
                <option value="super admin">super admin</option>
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label">DOJ</label>
            <input type="date" name="doj" value="{{ old('doj') }}" class="form-control">
        </div>
        <div class="col-md-4">
            <label class="form-label">Job Title</label>
            <input type="text" name="job_title" value="{{ old('job_title') }}" class="form-control">
        </div>
        <div class="col-md-4">
            <label class="form-label">Salary</label>
            <input type="text" name="salary" value="{{ old('salary') }}" class="form-control">
        </div>
        <div class="col-12">
            <label class="form-label">Current Address</label>
            <textarea name="current_address" class="form-control" rows="2">{{ old('current_address') }}</textarea>
        </div>
    </div>
    <div class="mt-3">
        <button class="btn btn-primary">Create Employee</button>
    </div>
</form>
@endsection
