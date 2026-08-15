<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class EmployeeApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $q = trim((string) $request->get('q', ''));
        $status = $request->get('status');
        $perPage = min(100, max(1, (int) $request->get('per_page', 25)));

        $employees = Employee::query()
            ->with(['department:id,name', 'designation:id,name'])
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($inner) use ($q) {
                    $inner->where('fname', 'like', "%{$q}%")
                        ->orWhere('lname', 'like', "%{$q}%")
                        ->orWhere('office_email', 'like', "%{$q}%")
                        ->orWhere('email', 'like', "%{$q}%")
                        ->orWhere('emp_id', 'like', "%{$q}%")
                        ->orWhere('external_id', 'like', "%{$q}%")
                        ->orWhere('mobile1', 'like', "%{$q}%");
                });
            })
            ->when($status !== null && $status !== '', fn ($query) => $query->where('status', (int) $status))
            ->when($request->boolean('active_only', true), function ($query) {
                $query->where('archive_status', 0);
            })
            ->orderBy('fname')
            ->orderBy('lname')
            ->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'data' => $employees->getCollection()->map(fn (Employee $e) => $this->serialize($e))->values(),
            'meta' => [
                'current_page' => $employees->currentPage(),
                'per_page' => $employees->perPage(),
                'total' => $employees->total(),
                'last_page' => $employees->lastPage(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validatedPayload($request);

        if ($conflict = $this->findConflict($data)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Employee already exists. Use PUT to update or POST /api/v1/employees/upsert.',
                'existing_id' => $conflict->id,
            ], 409);
        }

        $employee = $this->createEmployee($data);

        return response()->json([
            'status' => 'success',
            'message' => 'Employee created.',
            'data' => $this->serialize($employee->fresh(['department', 'designation'])),
        ], 201);
    }

    public function show(Employee $employee): JsonResponse
    {
        $employee->load(['department:id,name', 'designation:id,name']);

        return response()->json([
            'status' => 'success',
            'data' => $this->serialize($employee),
        ]);
    }

    public function update(Request $request, Employee $employee): JsonResponse
    {
        $data = $this->validatedPayload($request, $employee->id, false);
        $this->applyUpdate($employee, $data);

        return response()->json([
            'status' => 'success',
            'message' => 'Employee updated.',
            'data' => $this->serialize($employee->fresh(['department', 'designation'])),
        ]);
    }

    /**
     * Create or update by external_id, then office_email, then emp_id.
     */
    public function upsert(Request $request): JsonResponse
    {
        $data = $this->validatedPayload($request, null, true);

        $existing = $this->findConflict($data);
        if ($existing) {
            $this->applyUpdate($existing, $data);

            return response()->json([
                'status' => 'success',
                'message' => 'Employee updated (upsert).',
                'created' => false,
                'data' => $this->serialize($existing->fresh(['department', 'designation'])),
            ]);
        }

        $employee = $this->createEmployee($data);

        return response()->json([
            'status' => 'success',
            'message' => 'Employee created (upsert).',
            'created' => true,
            'data' => $this->serialize($employee->fresh(['department', 'designation'])),
        ], 201);
    }

    public function destroy(Employee $employee): JsonResponse
    {
        if ($employee->isAdmin()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Cannot archive admin / super admin accounts via API.',
            ], 422);
        }

        $employee->update([
            'archive_status' => 1,
            'status' => 0,
            'update_date' => now(),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Employee archived.',
            'data' => $this->serialize($employee->fresh(['department', 'designation'])),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedPayload(Request $request, ?int $ignoreId = null, bool $forUpsert = false): array
    {
        $officeUnique = Rule::unique('hrm_employee', 'office_email')->where(fn ($q) => $q->where('archive_status', 0));
        $emailUnique = Rule::unique('hrm_employee', 'email');
        $externalUnique = Rule::unique('hrm_employee', 'external_id');
        $empCodeUnique = Rule::unique('hrm_employee', 'emp_id');

        if ($ignoreId) {
            $officeUnique = $officeUnique->ignore($ignoreId);
            $emailUnique = $emailUnique->ignore($ignoreId);
            $externalUnique = $externalUnique->ignore($ignoreId);
            $empCodeUnique = $empCodeUnique->ignore($ignoreId);
        }

        // Upsert may hit an existing row — uniqueness checked after match.
        if ($forUpsert) {
            $officeUnique = null;
            $emailUnique = null;
            $externalUnique = null;
            $empCodeUnique = null;
        }

        $rules = [
            'fname' => [$forUpsert || $ignoreId ? 'sometimes' : 'required', 'string', 'max:208'],
            'lname' => ['nullable', 'string', 'max:58'],
            'office_email' => array_values(array_filter([
                $forUpsert ? 'nullable' : ($ignoreId ? 'nullable' : 'required'),
                'email',
                'max:252',
                $officeUnique,
            ])),
            'email' => array_values(array_filter(['nullable', 'email', 'max:58', $emailUnique])),
            'mobile1' => ['nullable', 'string', 'max:58'],
            'mobile2' => ['nullable', 'string', 'max:58'],
            'password' => ['nullable', 'string', 'max:255'],
            'dob' => ['nullable', 'date'],
            'doj' => ['nullable', 'date'],
            'gender' => ['nullable', 'integer'],
            'bgroup' => ['nullable', 'string', 'max:252'],
            'marital_status' => ['nullable', 'integer'],
            'department_id' => ['nullable', 'integer', 'exists:hrm_department,id'],
            'designation_id' => ['nullable', 'integer', 'exists:hrm_designation,id'],
            'department' => ['nullable', 'string', 'max:255'],
            'designation' => ['nullable', 'string', 'max:255'],
            'role' => ['nullable', 'in:user,admin,super admin'],
            'emp_id' => array_values(array_filter(['nullable', 'string', 'max:50', $empCodeUnique])),
            'external_id' => array_values(array_filter(['nullable', 'string', 'max:120', $externalUnique])),
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
            'status' => ['nullable', 'integer', 'in:0,1'],
            'archive_status' => ['nullable', 'integer', 'in:0,1'],
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($forUpsert) {
            $validator->after(function ($v) use ($request) {
                $hasKey = filled($request->input('external_id'))
                    || filled($request->input('office_email'))
                    || filled($request->input('emp_id'));
                if (! $hasKey) {
                    $v->errors()->add('external_id', 'Provide external_id, office_email, or emp_id for upsert matching.');
                }
                if (! $request->filled('fname') && ! $this->findConflict($request->all())) {
                    // Creating new via upsert still needs fname + office_email ideally
                    if (! $request->filled('fname')) {
                        $v->errors()->add('fname', 'fname is required when creating a new employee via upsert.');
                    }
                    if (! $request->filled('office_email')) {
                        $v->errors()->add('office_email', 'office_email is required when creating a new employee via upsert.');
                    }
                }
            });
        }

        $data = $validator->validate();
        $data = $this->resolveOrgUnits($data);

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function resolveOrgUnits(array $data): array
    {
        if (empty($data['department_id']) && ! empty($data['department'])) {
            $name = trim((string) $data['department']);
            $dept = Department::query()->whereRaw('LOWER(name) = ?', [strtolower($name)])->first();
            if (! $dept) {
                $dept = Department::create([
                    'name' => $name,
                    'code' => strtoupper(substr(preg_replace('/\s+/', '', $name) ?: 'DEPT', 0, 20)),
                ]);
            }
            $data['department_id'] = $dept->id;
        }

        if (empty($data['designation_id']) && ! empty($data['designation'])) {
            $name = trim((string) $data['designation']);
            $desig = Designation::query()->whereRaw('LOWER(name) = ?', [strtolower($name)])->first();
            if (! $desig) {
                $desig = Designation::create([
                    'name' => $name,
                    'department_name' => $data['department'] ?? null,
                ]);
            }
            $data['designation_id'] = $desig->id;
        }

        unset($data['department'], $data['designation']);

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function findConflict(array $data): ?Employee
    {
        if (! empty($data['external_id'])) {
            $found = Employee::query()->where('external_id', $data['external_id'])->first();
            if ($found) {
                return $found;
            }
        }

        if (! empty($data['office_email'])) {
            $found = Employee::query()
                ->whereRaw('LOWER(TRIM(office_email)) = ?', [strtolower(trim((string) $data['office_email']))])
                ->first();
            if ($found) {
                return $found;
            }
        }

        if (! empty($data['emp_id'])) {
            return Employee::query()->where('emp_id', $data['emp_id'])->first();
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function createEmployee(array $data): Employee
    {
        return Employee::create([
            ...$data,
            'status' => $data['status'] ?? 1,
            'archive_status' => $data['archive_status'] ?? 0,
            'role' => $data['role'] ?? 'user',
            'password' => $data['password'] ?? config('hrm.default_employee_password', 'Welcome@123'),
            'added_date' => now(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function applyUpdate(Employee $employee, array $data): void
    {
        if (array_key_exists('password', $data) && ($data['password'] === null || $data['password'] === '')) {
            unset($data['password']);
        }

        $employee->update([
            ...$data,
            'update_date' => now(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function serialize(Employee $employee): array
    {
        return [
            'id' => $employee->id,
            'external_id' => $employee->external_id,
            'emp_id' => $employee->emp_id,
            'fname' => $employee->fname,
            'lname' => $employee->lname,
            'full_name' => $employee->full_name,
            'office_email' => $employee->office_email,
            'email' => $employee->email,
            'mobile1' => $employee->mobile1,
            'mobile2' => $employee->mobile2,
            'dob' => optional($employee->dob)->toDateString(),
            'doj' => optional($employee->doj)->toDateString(),
            'department_id' => $employee->department_id,
            'department' => $employee->department?->name,
            'designation_id' => $employee->designation_id,
            'designation' => $employee->designation?->name,
            'job_title' => $employee->job_title,
            'salary' => $employee->salary,
            'role' => $employee->role,
            'status' => (int) $employee->status,
            'archive_status' => (int) $employee->archive_status,
            'employee_type' => $employee->employee_type,
            'work_location' => $employee->work_location,
            'current_address' => $employee->current_address,
            'permanent_address' => $employee->permanent_address,
        ];
    }
}
