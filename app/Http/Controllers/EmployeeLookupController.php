<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmployeeLookupController extends Controller
{
    public function filters(): JsonResponse
    {
        return response()->json([
            'departments' => Department::query()->orderBy('name')->get(['id', 'name']),
            'designations' => Designation::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function search(Request $request): JsonResponse
    {
        $q = trim((string) $request->get('q', ''));
        $departmentId = (int) $request->get('department_id', 0);
        $designationId = (int) $request->get('designation_id', 0);
        $limit = min(50, max(5, (int) $request->get('limit', 30)));
        $exclude = collect(explode(',', (string) $request->get('exclude', '')))
            ->map(fn ($id) => (int) trim($id))
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        $query = Employee::query()
            ->with(['department:id,name', 'designation:id,name'])
            ->where('status', 1)
            ->where('archive_status', 0);

        if ($exclude !== []) {
            $query->whereNotIn('id', $exclude);
        }

        if ($departmentId > 0) {
            $query->where('department_id', $departmentId);
        }

        if ($designationId > 0) {
            $query->where('designation_id', $designationId);
        }

        if ($q !== '') {
            $query->where(function ($inner) use ($q) {
                $inner->where('fname', 'like', "%{$q}%")
                    ->orWhere('lname', 'like', "%{$q}%")
                    ->orWhereRaw("CONCAT(TRIM(fname), ' ', TRIM(lname)) LIKE ?", ["%{$q}%"])
                    ->orWhere('emp_id', 'like', "%{$q}%")
                    ->orWhere('office_email', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%");
            });
        }

        $rows = $query
            ->orderBy('fname')
            ->orderBy('lname')
            ->limit($limit)
            ->get(['id', 'fname', 'lname', 'emp_id', 'department_id', 'designation_id', 'office_email']);

        return response()->json([
            'data' => $rows->map(fn (Employee $e) => [
                'id' => (int) $e->id,
                'name' => $e->full_name,
                'emp_id' => $e->emp_id,
                'department' => $e->department?->name,
                'designation' => $e->designation?->name,
                'department_id' => (int) ($e->department_id ?? 0),
                'designation_id' => (int) ($e->designation_id ?? 0),
            ])->values(),
        ]);
    }
}
