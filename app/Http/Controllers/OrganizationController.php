<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Designation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrganizationController extends Controller
{
    public function departments(Request $request): View
    {
        $q = trim((string) $request->get('q', ''));

        $departments = Department::query()
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($inner) use ($q) {
                    $inner->where('name', 'like', "%{$q}%")
                        ->orWhere('code', 'like', "%{$q}%");
                });
            })
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('organization.departments', compact('departments', 'q'));
    }

    public function storeDepartment(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:252'],
            'code' => ['required', 'string', 'max:50'],
        ]);

        Department::create($data);

        return back()->with('success', 'Department created.');
    }

    public function updateDepartment(Request $request, Department $department): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:252'],
            'code' => ['required', 'string', 'max:50'],
        ]);

        $department->update($data);

        return back()->with('success', 'Department updated.');
    }

    public function destroyDepartment(Department $department): RedirectResponse
    {
        $department->delete();

        return back()->with('success', 'Department deleted.');
    }

    public function designations(Request $request): View
    {
        $q = trim((string) $request->get('q', ''));

        $designations = Designation::query()
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($inner) use ($q) {
                    $inner->where('name', 'like', "%{$q}%")
                        ->orWhere('department_name', 'like', "%{$q}%");
                });
            })
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        $departments = Department::query()->orderBy('name')->get(['id', 'name']);

        return view('organization.designations', compact('designations', 'departments', 'q'));
    }

    public function storeDesignation(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:252'],
            'department_name' => ['required', 'string', 'max:252'],
        ]);

        Designation::create($data);

        return back()->with('success', 'Designation created.');
    }

    public function updateDesignation(Request $request, Designation $designation): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:252'],
            'department_name' => ['required', 'string', 'max:252'],
        ]);

        $designation->update($data);

        return back()->with('success', 'Designation updated.');
    }

    public function destroyDesignation(Designation $designation): RedirectResponse
    {
        $designation->delete();

        return back()->with('success', 'Designation deleted.');
    }
}
