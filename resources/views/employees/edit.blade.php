@extends('layouts.app')

@section('title', 'Edit '.$employee->full_name)
@section('heading', 'Edit Employee')

@section('page_actions')
<a href="{{ route('employees.show', $employee) }}" class="btn btn-outline-secondary me-1">
    <i class="fa-solid fa-eye me-1"></i> View
</a>
<a href="{{ route('employees.index') }}" class="btn btn-outline-secondary">
    <i class="fa-solid fa-list me-1"></i> List
</a>
@endsection

@push('styles')
<style>
    .ee-wrap { --ink:#0f2744; --muted:#6b7c93; --line:#e8eef5; --soft:#f4f7fb; --accent:#ff9b44; }
    .ee-wrap .ee-panel { background:#fff; border:1px solid var(--line); border-radius:18px; overflow:hidden; margin-bottom:1rem; }
    .ee-wrap .ee-panel-head {
        display:flex; justify-content:space-between; align-items:flex-start; gap:1rem; flex-wrap:wrap;
        padding:1rem 1.15rem; border-bottom:1px solid var(--line);
        background:linear-gradient(180deg,#fff,#fafbfd);
    }
    .ee-wrap .ee-panel-head h5 { margin:0; font-weight:750; color:var(--ink); font-size:1rem; }
    .ee-wrap .ee-panel-head .sub { font-size:.78rem; color:var(--muted); display:block; margin-top:.15rem; }
    .ee-wrap .ee-panel-body { padding:1.15rem; }
    .ee-wrap .ee-panel-foot {
        padding:.85rem 1.15rem; border-top:1px solid var(--line); background:#fafbfd;
        display:flex; justify-content:flex-end; gap:.5rem; flex-wrap:wrap;
    }
    .ee-wrap .form-label { font-size:.8rem; font-weight:600; color:var(--muted); }
    .ee-wrap .help { font-size:.75rem; color:var(--muted); margin-top:.25rem; }
    .ee-wrap .table > :not(caption) > * > * { vertical-align:middle; }
    .ee-wrap .hero {
        border:1px solid var(--line); border-radius:18px; background:#fff; padding:1rem 1.15rem;
        display:flex; align-items:center; gap:.9rem; flex-wrap:wrap; margin-bottom:1rem;
        background:linear-gradient(135deg, rgba(255,155,68,.1), #fff 55%);
    }
    .ee-wrap .hero img {
        width:56px; height:56px; border-radius:50%; object-fit:cover; border:2px solid #ffe0c2;
    }
    .ee-wrap .hero .name { font-weight:800; color:var(--ink); margin:0; font-size:1.05rem; }
    .ee-wrap .hero .sub { font-size:.8rem; color:var(--muted); margin:0; }
    .ee-wrap .btn-save {
        background:var(--accent); border-color:var(--accent); color:#fff; font-weight:700;
        border-radius:10px; padding:.45rem 1.1rem;
    }
    .ee-wrap .btn-save:hover { filter:brightness(.96); color:#fff; }
</style>
@endpush

@section('content')
@php($bank = $employee->bankDetail)
<div class="ee-wrap">
    <div class="hero">
        <img src="{{ $employee->profile_image_url }}" alt="" id="editPhotoPreview"
             onerror="this.src='{{ asset('assets/img/profiles/avatar-02.jpg') }}'">
        <div>
            <p class="name">{{ $employee->full_name }}</p>
            <p class="sub">{{ $employee->emp_id ?: 'ID #'.$employee->id }} · {{ $employee->designation?->name ?: '—' }} · {{ $employee->department?->name ?: '—' }}</p>
        </div>
    </div>

    <form method="POST" action="{{ route('employees.update', $employee) }}" enctype="multipart/form-data">
        @csrf @method('PUT')

        <div class="ee-panel">
            <div class="ee-panel-head">
                <div>
                    <h5><i class="fa-solid fa-image me-1" style="color:var(--accent)"></i> Profile photo</h5>
                    <span class="sub">Optional · replaces current image</span>
                </div>
            </div>
            <div class="ee-panel-body">
                <div class="row g-3 align-items-end">
                    <div class="col-md-6">
                        <label class="form-label">Upload new photo</label>
                        <input type="file" name="image" id="editPhotoInput" class="form-control" accept="image/jpeg,image/png,image/webp,image/gif">
                        <div class="form-text">JPG, PNG, WEBP, GIF · max 4MB</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="ee-panel">
            <div class="ee-panel-head">
                <div>
                    <h5><i class="fa-solid fa-user me-1" style="color:var(--accent)"></i> Basic info</h5>
                    <span class="sub">Personal and contact details</span>
                </div>
            </div>
            <div class="ee-panel-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">First name *</label>
                        <input type="text" name="fname" value="{{ old('fname', $employee->fname) }}" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Last name</label>
                        <input type="text" name="lname" value="{{ old('lname', $employee->lname) }}" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Date of birth</label>
                        <input type="date" name="dob" value="{{ old('dob', optional($employee->dob)->format('Y-m-d')) }}" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Gender</label>
                        <select name="gender" class="form-select">
                            <option value="1" @selected(old('gender', $employee->gender)==1)>Male</option>
                            <option value="2" @selected(old('gender', $employee->gender)==2)>Female</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Blood group</label>
                        <input type="text" name="bgroup" value="{{ old('bgroup', $employee->bgroup) }}" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Marital status</label>
                        <select name="marital_status" class="form-select">
                            <option value="1" @selected(old('marital_status', $employee->marital_status)==1)>Married</option>
                            <option value="2" @selected(old('marital_status', $employee->marital_status)==2)>Unmarried</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Personal email</label>
                        <input type="email" name="email" value="{{ old('email', $employee->email) }}" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Office email</label>
                        <input type="email" name="office_email" value="{{ old('office_email', $employee->office_email) }}" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Mobile</label>
                        <input type="text" name="mobile1" value="{{ old('mobile1', $employee->mobile1) }}" class="form-control">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Current address</label>
                        <textarea name="current_address" class="form-control" rows="2">{{ old('current_address', $employee->current_address) }}</textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Permanent address</label>
                        <textarea name="permanent_address" class="form-control" rows="2">{{ old('permanent_address', $employee->permanent_address) }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        <div class="ee-panel">
            <div class="ee-panel-head">
                <div>
                    <h5><i class="fa-solid fa-briefcase me-1" style="color:var(--accent)"></i> Work & access</h5>
                    <span class="sub">Employment IDs, role, and status</span>
                </div>
            </div>
            <div class="ee-panel-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Employee ID</label>
                        <input type="text" name="emp_id" value="{{ old('emp_id', $employee->emp_id) }}" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Attendance machine ID</label>
                        <input type="number" name="attendance_id" value="{{ old('attendance_id', $employee->attendance_id) }}" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">New password</label>
                        <input type="text" name="password" class="form-control" placeholder="Leave blank to keep">
                        <div class="help">Only fill if you want to reset login password.</div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Department</label>
                        <select name="department_id" class="form-select">
                            <option value="">—</option>
                            @foreach($departments as $d)
                                <option value="{{ $d->id }}" @selected(old('department_id', $employee->department_id)==$d->id)>{{ $d->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Designation</label>
                        <select name="designation_id" class="form-select">
                            <option value="">—</option>
                            @foreach($designations as $d)
                                <option value="{{ $d->id }}" @selected(old('designation_id', $employee->designation_id)==$d->id)>{{ $d->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Role</label>
                        <select name="role" class="form-select">
                            @foreach(['user','admin','super admin'] as $role)
                                <option value="{{ $role }}" @selected(old('role', $employee->role)===$role)>{{ ucwords($role) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="1" @selected(old('status', $employee->status)==1)>Active</option>
                            <option value="0" @selected(old('status', $employee->status)==0)>Inactive</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Date of joining</label>
                        <input type="date" name="doj" value="{{ old('doj', optional($employee->doj)->format('Y-m-d')) }}" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Job title</label>
                        <input type="text" name="job_title" value="{{ old('job_title', $employee->job_title) }}" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Salary</label>
                        <input type="text" name="salary" value="{{ old('salary', $employee->salary) }}" class="form-control">
                    </div>
                </div>
            </div>
        </div>

        <div class="ee-panel">
            <div class="ee-panel-head">
                <div>
                    <h5><i class="fa-solid fa-building-columns me-1" style="color:var(--accent)"></i> Bank details</h5>
                    <span class="sub">Salary account and KYC references</span>
                </div>
            </div>
            <div class="ee-panel-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Bank name</label>
                        <input type="text" name="bank_name" class="form-control" value="{{ old('bank_name', $bank?->bank_name) }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Account type</label>
                        <input type="text" name="account_type" class="form-control" value="{{ old('account_type', $bank?->account_type) }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Holder name</label>
                        <input type="text" name="account_holder_name" class="form-control" value="{{ old('account_holder_name', $bank?->account_holder_name) }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Account number</label>
                        <input type="text" name="account_number" class="form-control" value="{{ old('account_number', $bank?->account_number) }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">IFSC</label>
                        <input type="text" name="ifsc" class="form-control" value="{{ old('ifsc', $bank?->ifsc) }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Branch</label>
                        <input type="text" name="branch" class="form-control" value="{{ old('branch', $bank?->branch) }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">PAN</label>
                        <input type="text" name="pan" class="form-control" value="{{ old('pan', $bank?->pan) }}">
                    </div>
                </div>
            </div>
            <div class="ee-panel-foot">
                <button class="btn btn-save">Save changes</button>
            </div>
        </div>
    </form>

    <div class="row g-3">
        <div class="col-lg-6">
            <div class="ee-panel">
                <div class="ee-panel-head">
                    <div>
                        <h5><i class="fa-solid fa-people-roof me-1" style="color:var(--accent)"></i> Family</h5>
                        <span class="sub">Dependent / emergency contacts</span>
                    </div>
                </div>
                <div class="ee-panel-body">
                    <div class="table-responsive mb-3">
                        <table class="table table-sm align-middle mb-0">
                            <thead>
                            <tr><th>Name</th><th>Relation</th><th>Phone</th><th></th></tr>
                            </thead>
                            <tbody>
                            @forelse($employee->familyMembers as $f)
                                <tr>
                                    <td>{{ $f->name }}</td>
                                    <td>{{ $f->relationship?->name ?? $f->relationship_id }}</td>
                                    <td>{{ $f->phone }}</td>
                                    <td class="text-end">
                                        <form method="POST" action="{{ route('employees.family.destroy', [$employee, $f]) }}" onsubmit="return confirm('Remove this family member?')">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger">Remove</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-muted">No family members</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                    <form method="POST" action="{{ route('employees.family.store', $employee) }}" class="row g-2 align-items-end">
                        @csrf
                        <div class="col-md-4">
                            <label class="form-label">Name</label>
                            <input name="name" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Relation</label>
                            <select name="relationship_id" class="form-select form-select-sm" required>
                                @foreach($relationships as $r)
                                    <option value="{{ $r->id }}">{{ $r->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Phone</label>
                            <input name="phone" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-md-2">
                            <button class="btn btn-sm add-btn w-100">Add</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="ee-panel">
                <div class="ee-panel-head">
                    <div>
                        <h5><i class="fa-solid fa-graduation-cap me-1" style="color:var(--accent)"></i> Education</h5>
                        <span class="sub">Qualification records</span>
                    </div>
                </div>
                <div class="ee-panel-body">
                    <div class="table-responsive mb-3">
                        <table class="table table-sm align-middle mb-0">
                            <thead>
                            <tr><th>Course</th><th>College</th><th>Grade</th><th></th></tr>
                            </thead>
                            <tbody>
                            @forelse($employee->educations as $e)
                                <tr>
                                    <td>{{ $e->course_name ?: $e->qualification_type }}</td>
                                    <td>{{ $e->college_name }}</td>
                                    <td>{{ $e->grade }}</td>
                                    <td class="text-end">
                                        <form method="POST" action="{{ route('employees.education.destroy', [$employee, $e]) }}" onsubmit="return confirm('Remove this record?')">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger">Remove</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-muted">No education records</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                    <form method="POST" action="{{ route('employees.education.store', $employee) }}" class="row g-2 align-items-end">
                        @csrf
                        <div class="col-md-3">
                            <label class="form-label">Type</label>
                            <input name="qualification_type" class="form-control form-control-sm" placeholder="UG / PG">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Course</label>
                            <input name="course_name" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">College</label>
                            <input name="college_name" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Grade</label>
                            <input name="grade" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-md-1">
                            <button class="btn btn-sm add-btn w-100">+</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="ee-panel">
        <div class="ee-panel-head">
            <div>
                <h5><i class="fa-solid fa-box-archive me-1" style="color:#ef4444"></i> Danger zone</h5>
                <span class="sub">Archive moves this employee to Former Employees</span>
            </div>
        </div>
        <div class="ee-panel-body d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="text-muted small">Admins / super admins cannot be archived from here.</div>
            <form method="POST" action="{{ route('employees.destroy', $employee) }}" onsubmit="return confirm('Archive this employee?')">
                @csrf @method('DELETE')
                <button class="btn btn-outline-danger">Archive employee</button>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.getElementById('editPhotoInput')?.addEventListener('change', function () {
    const file = this.files?.[0];
    const preview = document.getElementById('editPhotoPreview');
    if (file && preview) preview.src = URL.createObjectURL(file);
});
</script>
@endpush
