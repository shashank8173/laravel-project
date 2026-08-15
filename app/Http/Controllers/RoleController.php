<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RoleController extends Controller
{
    public const ROLES = ['user', 'admin', 'super admin'];

    public function index(Request $request): View
    {
        $q = trim((string) $request->get('q', ''));
        $role = $request->get('role') ?: null;

        if ($role && ! in_array($role, self::ROLES, true)) {
            $role = null;
        }

        $base = Employee::query()->where('archive_status', 0);

        $employees = (clone $base)
            ->with(['department:id,name', 'designation:id,name'])
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($inner) use ($q) {
                    $inner->where('fname', 'like', "%{$q}%")
                        ->orWhere('lname', 'like', "%{$q}%")
                        ->orWhereRaw("CONCAT(fname, ' ', lname) like ?", ["%{$q}%"])
                        ->orWhere('email', 'like', "%{$q}%")
                        ->orWhere('office_email', 'like', "%{$q}%")
                        ->orWhere('mobile1', 'like', "%{$q}%");
                });
            })
            ->when($role, fn ($query) => $query->where('role', $role))
            ->orderByDesc('id')
            ->paginate(30)
            ->withQueryString();

        $stats = [
            'all' => (clone $base)->count(),
            'user' => (clone $base)->where('role', 'user')->count(),
            'admin' => (clone $base)->where('role', 'admin')->count(),
            'super admin' => (clone $base)->where('role', 'super admin')->count(),
        ];

        $roles = self::ROLES;

        return view('roles.index', compact('employees', 'role', 'q', 'stats', 'roles'));
    }

    public function update(Request $request, Employee $employee): RedirectResponse
    {
        $data = $request->validate([
            'role' => ['required', 'in:user,admin,super admin'],
        ]);

        if ($employee->isAdmin() && $data['role'] === 'user') {
            $adminCount = Employee::query()
                ->where('archive_status', 0)
                ->whereIn('role', ['admin', 'super admin'])
                ->where('id', '!=', $employee->id)
                ->count();

            if ($adminCount === 0) {
                return back()->withErrors(['role' => 'Cannot demote the last admin.']);
            }
        }

        $employee->update(['role' => $data['role'], 'update_date' => now()]);

        return back()->with('success', 'Role updated for '.$employee->full_name);
    }
}
