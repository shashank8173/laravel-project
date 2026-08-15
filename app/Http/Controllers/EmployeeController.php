<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\EmployeeEducation;
use App\Models\EmployeeFamily;
use App\Models\FamilyRelationship;
use App\Models\BankDetail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmployeeController extends Controller
{
    public function index(Request $request): View
    {
        $q = trim((string) $request->get('q', ''));
        $designationId = $request->integer('designation_id') ?: null;
        $departmentId = $request->integer('department_id') ?: null;
        $role = $request->get('role') ?: null;

        if ($role && ! in_array($role, ['user', 'admin', 'super admin'], true)) {
            $role = null;
        }

        $base = Employee::query()->where('archive_status', 0);

        $employees = (clone $base)
            ->with(['department', 'designation'])
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($inner) use ($q) {
                    $inner->where('fname', 'like', "%{$q}%")
                        ->orWhere('lname', 'like', "%{$q}%")
                        ->orWhereRaw("CONCAT(fname, ' ', lname) like ?", ["%{$q}%"])
                        ->orWhere('email', 'like', "%{$q}%")
                        ->orWhere('office_email', 'like', "%{$q}%")
                        ->orWhere('emp_id', 'like', "%{$q}%")
                        ->orWhere('mobile1', 'like', "%{$q}%");
                });
            })
            ->when($designationId, fn ($query) => $query->where('designation_id', $designationId))
            ->when($departmentId, fn ($query) => $query->where('department_id', $departmentId))
            ->when($role, fn ($query) => $query->where('role', $role))
            ->orderBy('fname')
            ->orderBy('lname')
            ->paginate(25)
            ->withQueryString();

        $designations = Designation::query()->orderBy('name')->get(['id', 'name']);
        $departments = Department::query()->orderBy('name')->get(['id', 'name']);

        $stats = [
            'total' => (clone $base)->count(),
            'active' => (clone $base)->where('status', 1)->count(),
            'inactive' => (clone $base)->where('status', '!=', 1)->count(),
            'admins' => (clone $base)->whereIn('role', ['admin', 'super admin'])->count(),
        ];

        return view('employees.index', compact(
            'employees',
            'q',
            'designations',
            'designationId',
            'departments',
            'departmentId',
            'role',
            'stats'
        ));
    }

    public function create(): View
    {
        return view('employees.create', $this->formData());
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateEmployee($request, true);

        $employee = Employee::create([
            ...$data,
            'status' => 1,
            'archive_status' => 0,
            'role' => $data['role'] ?? 'user',
            'added_date' => now(),
            'password' => $data['password'] ?? 'Welcome@123',
        ]);

        $this->syncRelated($employee, $request);

        return redirect()
            ->route('employees.edit', $employee)
            ->with('success', 'Employee created. You can add bank / family / education details below.');
    }

    public function show(Employee $employee): View
    {
        $employee->load(['department', 'designation', 'bankDetail', 'familyMembers.relationship', 'educations']);

        return view('employees.show', compact('employee'));
    }

    public function edit(Employee $employee): View
    {
        $employee->load(['bankDetail', 'familyMembers.relationship', 'educations']);

        return view('employees.edit', array_merge($this->formData(), compact('employee')));
    }

    public function update(Request $request, Employee $employee): RedirectResponse
    {
        $data = $this->validateEmployee($request, false, $employee->id);

        if (empty($data['password'])) {
            unset($data['password']);
        }

        $employee->update([
            ...$data,
            'update_date' => now(),
        ]);

        if ($request->hasFile('image')) {
            $request->validate([
                'image' => ['image', 'mimes:jpg,jpeg,png,webp,gif', 'max:4096'],
            ]);
            $employee->storeProfileImage($request->file('image'));
        }

        $this->syncRelated($employee, $request);

        return back()->with('success', 'Employee updated.');
    }

    public function updatePhoto(Request $request, Employee $employee): RedirectResponse
    {
        $request->validate([
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:4096'],
        ]);

        $employee->storeProfileImage($request->file('image'));

        return back()->with('success', 'Profile photo updated.');
    }

    public function destroy(Employee $employee): RedirectResponse
    {
        if ($employee->isAdmin()) {
            return back()->withErrors(['employee' => 'Cannot archive admin / super admin accounts.']);
        }

        $employee->update([
            'archive_status' => 1,
            'status' => 0,
            'update_date' => now(),
        ]);

        return redirect()->route('employees.index')->with('success', 'Employee archived.');
    }

    public function storeFamily(Request $request, Employee $employee): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:252'],
            'relationship_id' => ['required', 'integer'],
            'phone' => ['required', 'string', 'max:255'],
            'dependent' => ['nullable', 'string', 'max:252'],
        ]);

        $employee->familyMembers()->create([
            ...$data,
            'dependent' => $data['dependent'] ?? 'No',
        ]);

        return back()->with('success', 'Family member added.');
    }

    public function destroyFamily(Employee $employee, EmployeeFamily $family): RedirectResponse
    {
        abort_unless((int) $family->emp_id === (int) $employee->id, 404);
        $family->delete();

        return back()->with('success', 'Family member removed.');
    }

    public function storeEducation(Request $request, Employee $employee): RedirectResponse
    {
        $data = $request->validate([
            'qualification_type' => ['nullable', 'string', 'max:252'],
            'course_name' => ['nullable', 'string', 'max:252'],
            'course_type' => ['nullable', 'string', 'max:252'],
            'stream' => ['nullable', 'string', 'max:252'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
            'college_name' => ['nullable', 'string', 'max:252'],
            'university_name' => ['nullable', 'string', 'max:252'],
            'grade' => ['required', 'string', 'max:252'],
        ]);

        $employee->educations()->create($data);

        return back()->with('success', 'Education record added.');
    }

    public function destroyEducation(Employee $employee, EmployeeEducation $education): RedirectResponse
    {
        abort_unless((int) $education->emp_id === (int) $employee->id, 404);
        $education->delete();

        return back()->with('success', 'Education record removed.');
    }

    private function formData(): array
    {
        return [
            'departments' => Department::query()->orderBy('name')->get(),
            'designations' => Designation::query()->orderBy('name')->get(),
            'relationships' => FamilyRelationship::query()->orderBy('id')->get(),
        ];
    }

    private function validateEmployee(Request $request, bool $creating, ?int $ignoreId = null): array
    {
        $emailRule = ['nullable', 'email', 'max:58'];
        $emailRule[] = $ignoreId
            ? 'unique:hrm_employee,email,'.$ignoreId
            : 'unique:hrm_employee,email';

        return $request->validate([
            'fname' => ['required', 'string', 'max:208'],
            'lname' => ['nullable', 'string', 'max:58'],
            'email' => $emailRule,
            'office_email' => ['nullable', 'email', 'max:252'],
            'mobile1' => ['nullable', 'string', 'max:58'],
            'mobile2' => ['nullable', 'string', 'max:58'],
            'password' => ['nullable', 'string', 'max:255'],
            'dob' => ['nullable', 'date'],
            'doj' => ['nullable', 'date'],
            'gender' => ['nullable', 'integer'],
            'bgroup' => ['nullable', 'string', 'max:252'],
            'marital_status' => ['nullable', 'integer'],
            'department_id' => ['nullable', 'integer'],
            'designation_id' => ['nullable', 'integer'],
            'role' => ['nullable', 'in:user,admin,super admin'],
            'emp_id' => ['nullable', 'string', 'max:50'],
            'job_title' => ['nullable', 'string', 'max:252'],
            'attendance_id' => ['nullable', 'integer'],
            'salary' => ['nullable', 'string', 'max:252'],
            'current_address' => ['nullable', 'string', 'max:255'],
            'permanent_address' => ['nullable', 'string', 'max:255'],
            'city_id' => ['nullable', 'string', 'max:252'],
            'state_id' => ['nullable', 'string', 'max:252'],
            'pincode' => ['nullable', 'string', 'max:58'],
            'employee_type' => ['nullable', 'string', 'max:252'],
            'work_location' => ['nullable', 'string', 'max:252'],
            'experience' => ['nullable', 'string', 'max:255'],
            'fathers_name' => ['nullable', 'string', 'max:58'],
            'status' => ['nullable', 'integer'],
        ]);
    }

    private function syncRelated(Employee $employee, Request $request): void
    {
        if ($request->filled('bank_name') || $request->filled('account_number') || $request->filled('pan')) {
            $bank = $request->validate([
                'bank_name' => ['nullable', 'string', 'max:252'],
                'account_type' => ['nullable', 'string', 'max:252'],
                'account_holder_name' => ['nullable', 'string', 'max:252'],
                'account_number' => ['nullable', 'string', 'max:252'],
                'ifsc' => ['nullable', 'string', 'max:252'],
                'branch' => ['nullable', 'string', 'max:252'],
                'pan' => ['nullable', 'string', 'max:252'],
            ]);

            BankDetail::query()->updateOrCreate(
                ['emp_id' => $employee->id],
                $bank
            );
        }
    }
}
