<?php

namespace App\Services;

use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class EmployeeUpsertService
{
    /**
     * @param  array<string, mixed>  $payload
     * @return array{employee:Employee,created:bool}
     */
    public function upsert(array $payload): array
    {
        $data = $this->normalizeAndValidate($payload);
        $existing = $this->findExisting($data);

        if ($existing) {
            $update = $data;
            if (empty($update['password'])) {
                unset($update['password']);
            }
            $existing->update([
                ...$update,
                'update_date' => now(),
            ]);

            return ['employee' => $existing->fresh(['department', 'designation']), 'created' => false];
        }

        if (empty($data['fname'])) {
            throw ValidationException::withMessages(['fname' => 'fname is required when creating an employee.']);
        }
        if (empty($data['office_email'])) {
            throw ValidationException::withMessages(['office_email' => 'office_email is required when creating an employee.']);
        }

        $employee = Employee::create([
            ...$data,
            'status' => $data['status'] ?? 1,
            'archive_status' => $data['archive_status'] ?? 0,
            'role' => $data['role'] ?? 'user',
            'password' => $data['password'] ?? config('hrm.default_employee_password', 'Welcome@123'),
            'added_date' => now(),
        ]);

        return ['employee' => $employee->fresh(['department', 'designation']), 'created' => true];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function normalizeAndValidate(array $payload): array
    {
        $payload = $this->applyAliases($payload);

        $validator = Validator::make($payload, [
            'fname' => ['nullable', 'string', 'max:208'],
            'lname' => ['nullable', 'string', 'max:58'],
            'office_email' => ['nullable', 'email', 'max:252'],
            'email' => ['nullable', 'email', 'max:58'],
            'mobile1' => ['nullable', 'string', 'max:58'],
            'mobile2' => ['nullable', 'string', 'max:58'],
            'password' => ['nullable', 'string', 'max:255'],
            'dob' => ['nullable', 'date'],
            'doj' => ['nullable', 'date'],
            'gender' => ['nullable', 'integer'],
            'department_id' => ['nullable', 'integer', 'exists:hrm_department,id'],
            'designation_id' => ['nullable', 'integer', 'exists:hrm_designation,id'],
            'department' => ['nullable', 'string', 'max:255'],
            'designation' => ['nullable', 'string', 'max:255'],
            'role' => ['nullable', 'in:user,admin,super admin'],
            'emp_id' => ['nullable', 'string', 'max:50'],
            'external_id' => ['nullable', 'string', 'max:120'],
            'job_title' => ['nullable', 'string', 'max:252'],
            'salary' => ['nullable', 'string', 'max:252'],
            'current_address' => ['nullable', 'string', 'max:255'],
            'permanent_address' => ['nullable', 'string', 'max:255'],
            'employee_type' => ['nullable', 'string', 'max:252'],
            'work_location' => ['nullable', 'string', 'max:252'],
            'status' => ['nullable', 'integer', 'in:0,1'],
            'archive_status' => ['nullable', 'integer', 'in:0,1'],
        ]);

        $validator->after(function ($v) use ($payload) {
            if (
                empty($payload['external_id'])
                && empty($payload['office_email'])
                && empty($payload['emp_id'])
            ) {
                $v->errors()->add('external_id', 'Need external_id, office_email, or emp_id to match an employee.');
            }
        });

        $data = $validator->validate();

        return $this->resolveOrgUnits($data);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function applyAliases(array $payload): array
    {
        $aliases = [
            'first_name' => 'fname',
            'FirstName' => 'fname',
            'givenName' => 'fname',
            'last_name' => 'lname',
            'LastName' => 'lname',
            'familyName' => 'lname',
            'work_email' => 'office_email',
            'WorkEmail' => 'office_email',
            'official_email' => 'office_email',
            'Email' => 'office_email',
            'personal_email' => 'email',
            'Phone' => 'mobile1',
            'mobile' => 'mobile1',
            'Mobile' => 'mobile1',
            'EmployeeID' => 'emp_id',
            'employee_code' => 'emp_id',
            'employeeNumber' => 'emp_id',
            'Department' => 'department',
            'Designation' => 'designation',
            'JobTitle' => 'job_title',
            'DateOfJoining' => 'doj',
            'HireDate' => 'doj',
            'DateOfBirth' => 'dob',
        ];

        foreach ($aliases as $from => $to) {
            if (! array_key_exists($to, $payload) && array_key_exists($from, $payload)) {
                $payload[$to] = $payload[$from];
            }
        }

        return $payload;
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
    public function findExisting(array $data): ?Employee
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
}
