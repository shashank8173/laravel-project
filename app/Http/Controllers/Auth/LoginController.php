<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AdminLoginLog;
use App\Models\Employee;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function show(): View|RedirectResponse
    {
        if (Auth::check()) {
            return $this->redirectFor(Auth::user());
        }

        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $loginEmail = strtolower(trim($credentials['email']));

        // Login only with official / office email.
        $employee = Employee::query()
            ->whereRaw('LOWER(TRIM(office_email)) = ?', [$loginEmail])
            ->where('status', 1)
            ->where('archive_status', 0)
            ->first();

        if (! $employee || ! Auth::getProvider()->validateCredentials($employee, $credentials)) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'Wrong official email or password. Try forgot password or contact HR.']);
        }

        Auth::login($employee, $request->boolean('remember'));
        $request->session()->regenerate();

        DB::table('hrm_login_detail')->insert([
            'date_time' => now(),
            'emp_id' => $employee->id,
        ]);

        if ($employee->isAdmin()) {
            AdminLoginLog::create([
                'admin_id' => $employee->id,
                'email' => (string) ($employee->office_email ?: $employee->email),
                'action' => 'login',
                'timestamp' => now(),
                'ip_address' => $request->ip(),
                'browser_info' => substr((string) $request->userAgent(), 0, 500),
                'email_status' => 'n/a',
            ]);
        }

        return $this->redirectFor($employee);
    }

    public function destroy(Request $request): RedirectResponse
    {
        $user = Auth::user();
        if ($user && $user->isAdmin()) {
            AdminLoginLog::create([
                'admin_id' => $user->id,
                'email' => (string) ($user->officialEmail() ?: $user->email),
                'action' => 'logout',
                'timestamp' => now(),
                'ip_address' => $request->ip(),
                'browser_info' => substr((string) $request->userAgent(), 0, 500),
                'email_status' => 'n/a',
            ]);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function redirectFor(Employee $employee): RedirectResponse
    {
        if ($employee->canAccessAdminDashboard()) {
            return redirect()->intended(route('dashboard.admin'));
        }

        return redirect()->intended(route('dashboard.employee'));
    }
}
