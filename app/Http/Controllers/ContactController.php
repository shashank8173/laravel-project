<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ContactController extends Controller
{
    public function index(Request $request): View
    {
        $q = trim((string) $request->get('q', ''));
        $departmentId = $request->integer('department_id') ?: null;

        $base = Employee::query()
            ->where('status', 1)
            ->where('archive_status', 0);

        $contacts = (clone $base)
            ->with(['department', 'designation'])
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($inner) use ($q) {
                    $inner->where('fname', 'like', "%{$q}%")
                        ->orWhere('lname', 'like', "%{$q}%")
                        ->orWhereRaw("CONCAT(fname, ' ', lname) like ?", ["%{$q}%"])
                        ->orWhere('email', 'like', "%{$q}%")
                        ->orWhere('mobile1', 'like', "%{$q}%")
                        ->orWhere('mobile2', 'like', "%{$q}%")
                        ->orWhere('office_email', 'like', "%{$q}%");
                });
            })
            ->when($departmentId, fn ($query) => $query->where('department_id', $departmentId))
            ->orderBy('fname')
            ->orderBy('lname')
            ->paginate(30)
            ->withQueryString();

        $departments = Department::query()
            ->orderBy('name')
            ->get(['id', 'name']);

        $stats = [
            'total' => (clone $base)->count(),
            'with_mobile' => (clone $base)->whereNotNull('mobile1')->where('mobile1', '!=', '')->count(),
            'with_email' => (clone $base)->where(function ($query) {
                $query->where(function ($inner) {
                    $inner->whereNotNull('office_email')->where('office_email', '!=', '');
                })->orWhere(function ($inner) {
                    $inner->whereNotNull('email')->where('email', '!=', '');
                });
            })->count(),
            'departments' => (clone $base)->whereNotNull('department_id')->distinct('department_id')->count('department_id'),
        ];

        return view('contacts.index', compact('contacts', 'q', 'departmentId', 'departments', 'stats'));
    }
}
