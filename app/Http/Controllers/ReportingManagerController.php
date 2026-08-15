<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\ReportingManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportingManagerController extends Controller
{
    public function index(Request $request): View
    {
        $q = trim((string) $request->get('q', ''));
        $type = $request->get('type') ?: null;

        if ($type && ! in_array($type, ['Primary', 'Secondary'], true)) {
            $type = null;
        }

        $rows = ReportingManager::query()
            ->with(['employee:id,fname,lname', 'manager:id,fname,lname'])
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($inner) use ($q) {
                    $inner->whereHas('employee', function ($e) use ($q) {
                        $e->where('fname', 'like', "%{$q}%")
                            ->orWhere('lname', 'like', "%{$q}%")
                            ->orWhereRaw("CONCAT(fname, ' ', lname) like ?", ["%{$q}%"]);
                    })->orWhereHas('manager', function ($m) use ($q) {
                        $m->where('fname', 'like', "%{$q}%")
                            ->orWhere('lname', 'like', "%{$q}%")
                            ->orWhereRaw("CONCAT(fname, ' ', lname) like ?", ["%{$q}%"]);
                    });
                });
            })
            ->when($type, fn ($query) => $query->where('reporting_manager_type', $type))
            ->orderByDesc('id')
            ->paginate(30)
            ->withQueryString();

        $employees = Employee::query()
            ->with(['department:id,name', 'designation:id,name'])
            ->where('status', 1)
            ->where('archive_status', 0)
            ->orderBy('fname')
            ->get(['id', 'fname', 'lname', 'department_id', 'designation_id', 'emp_id']);

        $stats = [
            'assignments' => ReportingManager::query()->count(),
            'managers' => ReportingManager::query()->distinct('reporting_manager_id')->count('reporting_manager_id'),
            'employees' => ReportingManager::query()->distinct('employee_id')->count('employee_id'),
            'primary' => ReportingManager::query()->where('reporting_manager_type', 'Primary')->count(),
            'secondary' => ReportingManager::query()->where('reporting_manager_type', 'Secondary')->count(),
        ];

        return view('reporting.index', compact('rows', 'employees', 'q', 'type', 'stats'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'employee_id' => ['required', 'integer', 'exists:hrm_employee,id'],
            'reporting_manager_id' => ['required', 'integer', 'exists:hrm_employee,id', 'different:employee_id'],
            'reporting_manager_type' => ['required', 'in:Primary,Secondary'],
        ]);

        $exists = ReportingManager::query()
            ->where('employee_id', $data['employee_id'])
            ->where('reporting_manager_id', $data['reporting_manager_id'])
            ->exists();

        if ($exists) {
            return back()->withErrors(['reporting_manager_id' => 'This manager is already assigned to the employee.'])->withInput();
        }

        ReportingManager::create([
            ...$data,
            'date' => now(),
        ]);

        return back()->with('success', 'Reporting manager assigned.');
    }

    public function destroy(ReportingManager $reportingManager): RedirectResponse
    {
        $reportingManager->delete();

        return back()->with('success', 'Assignment removed.');
    }
}
