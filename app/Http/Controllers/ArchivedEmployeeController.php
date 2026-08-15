<?php

namespace App\Http\Controllers;

use App\Models\ArchivedEmployee;
use App\Models\Employee;
use App\Models\PayslipSetting;
use App\Models\SalaryManagement;
use App\Models\UserAttendance;
use App\Services\SalaryCalculationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ArchivedEmployeeController extends Controller
{
    public function index(Request $request): View
    {
        $q = trim((string) $request->get('q', ''));

        $employees = ArchivedEmployee::query()
            ->with(['department', 'designation'])
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($inner) use ($q) {
                    $inner->where('fname', 'like', "%{$q}%")
                        ->orWhere('lname', 'like', "%{$q}%")
                        ->orWhere('email', 'like', "%{$q}%")
                        ->orWhere('emp_id', 'like', "%{$q}%")
                        ->orWhere('mobile1', 'like', "%{$q}%");
                });
            })
            ->orderByDesc('created_at')
            ->paginate(25)
            ->withQueryString();

        return view('employees.archived', compact('employees', 'q'));
    }

    public function details(ArchivedEmployee $archived): JsonResponse
    {
        $archived->load(['department', 'designation', 'bankDetail']);

        $statusLabel = match ((int) ($archived->status ?? 0)) {
            1 => 'Active',
            2 => 'Inactive',
            default => 'Ex-Employee',
        };

        $salary = $this->resolveSalary($archived);

        $attendanceMonths = UserAttendance::query()
            ->where('user_id', $archived->id)
            ->whereNotNull('clock_in_time')
            ->selectRaw('YEAR(clock_in_time) as y, MONTH(clock_in_time) as m, COUNT(*) as c')
            ->groupBy('y', 'm')
            ->orderByDesc('y')
            ->orderByDesc('m')
            ->limit(24)
            ->get()
            ->map(fn ($row) => [
                'year' => (int) $row->y,
                'month' => (int) $row->m,
                'count' => (int) $row->c,
            ])
            ->values()
            ->all();

        return response()->json([
            'id' => $archived->id,
            'emp_id' => $archived->emp_id,
            'full_name' => $archived->full_name,
            'fname' => $archived->fname,
            'lname' => $archived->lname,
            'email' => $archived->email,
            'office_email' => $archived->office_email,
            'mobile1' => $archived->mobile1,
            'mobile2' => $archived->mobile2,
            'fathers_name' => $archived->fathers_name,
            'dob' => optional($archived->dob)->format('d M Y'),
            'doj' => optional($archived->doj)->format('d M Y'),
            'archived_on' => optional($archived->created_at)->format('d M Y'),
            'department' => $archived->department?->name,
            'designation' => $archived->designation?->name,
            'image' => $archived->profile_image_url,
            'salary' => $salary,
            'gender' => $archived->gender,
            'bgroup' => $archived->bgroup,
            'marital_status' => $archived->marital_status,
            'marriage_anniversary' => $archived->marriage_anniversary,
            'current_address' => $archived->current_address,
            'permanent_address' => $archived->permanent_address,
            'city_id' => $archived->city_id,
            'state_id' => $archived->state_id,
            'pincode' => $archived->pincode,
            'experience' => $archived->experience,
            'employee_type' => $archived->employee_type,
            'work_location' => $archived->work_location,
            'job_title' => $archived->job_title,
            'probation_period' => $archived->probation_period,
            'probation_status' => $archived->probation_status,
            'religion' => $archived->religion,
            'nationality' => $archived->nationality,
            'attendance_id' => $archived->attendance_id,
            'house_type' => $archived->house_type,
            'staying_current_residence' => $archived->staying_current_residence,
            'living_current_city' => $archived->living_current_city,
            'other_detail' => $archived->other_detail,
            'added_date' => $archived->added_date,
            'update_date' => $archived->update_date,
            'status_label' => $statusLabel,
            'bank_name' => $archived->bankDetail?->bank_name,
            'account_number' => $archived->bankDetail?->account_number,
            'ifsc' => $archived->bankDetail?->ifsc,
            'attendance_months' => $attendanceMonths,
            'details_url' => route('employees.archived.details', $archived),
            'payslip_url' => route('employees.archived.payslip', $archived),
            'salary_url' => route('employees.archived.salary', $archived),
            'restore_url' => route('employees.archived.restore', $archived),
        ]);
    }

    public function updateSalary(Request $request, ArchivedEmployee $archived): RedirectResponse|JsonResponse
    {
        $data = $request->validate([
            'salary' => ['required', 'numeric', 'min:0'],
        ]);

        $archived->salary = $data['salary'];
        $archived->save();

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'salary' => (float) $archived->salary,
                'message' => 'Salary updated.',
            ]);
        }

        return back()->with('success', 'Salary updated for former employee.');
    }

    public function payslip(Request $request, ArchivedEmployee $archived, SalaryCalculationService $calculator): View
    {
        $data = $request->validate([
            'month' => ['nullable', 'integer', 'min:1', 'max:12'],
            'year' => ['nullable', 'integer', 'min:2000', 'max:2100'],
            'salary' => ['nullable', 'numeric', 'min:0'],
        ]);

        $month = (int) ($data['month'] ?? now()->month);
        $year = (int) ($data['year'] ?? now()->year);
        $salaryOverride = array_key_exists('salary', $data) && $data['salary'] !== null
            ? (float) $data['salary']
            : null;

        $archived->load(['department', 'designation', 'bankDetail']);
        $employee = $archived->toEmployee();
        $employee->salary = $this->resolveSalary($archived, $salaryOverride);

        $calc = $calculator->calculate($employee, $month, $year);
        $payslip = PayslipSetting::current();

        $punchDays = UserAttendance::query()
            ->where('user_id', $archived->id)
            ->whereMonth('clock_in_time', $month)
            ->whereYear('clock_in_time', $year)
            ->whereRaw("TIME(clock_in_time) > '00:00:00'")
            ->count();

        return view('employees.partials.archived-payslip', compact(
            'archived',
            'employee',
            'calc',
            'month',
            'year',
            'payslip',
            'punchDays'
        ));
    }

    /**
     * Prefer: request override → archived.salary → salary_management → live hrm_employee.salary
     */
    private function resolveSalary(ArchivedEmployee $archived, ?float $override = null): float
    {
        if ($override !== null && $override > 0) {
            return $override;
        }

        $archivedSalary = (float) ($archived->salary ?? 0);
        if ($archivedSalary > 0) {
            return $archivedSalary;
        }

        $managed = SalaryManagement::query()->where('emp_id', $archived->id)->first();
        if ($managed) {
            $fromManaged = (float) ($managed->current_salary ?: $managed->actual_salary ?: 0);
            if ($fromManaged > 0) {
                return $fromManaged;
            }
        }

        $live = (float) (Employee::query()->where('id', $archived->id)->value('salary') ?: 0);

        return $live > 0 ? $live : 0.0;
    }

    public function restore(ArchivedEmployee $archived): RedirectResponse
    {
        if (Employee::query()->where('id', $archived->id)->exists()) {
            return back()->withErrors(['restore' => 'An active employee with this ID already exists.']);
        }

        DB::transaction(function () use ($archived) {
            $data = collect($archived->getAttributes())
                ->except(['created_at'])
                ->all();

            $data['archive_status'] = 0;
            $data['status'] = 1;
            $data['update_date'] = now();

            $allowed = (new Employee)->getConnection()
                ->getSchemaBuilder()
                ->getColumnListing('hrm_employee');

            $data = array_intersect_key($data, array_flip($allowed));

            Employee::query()->insert($data);
            $archived->delete();
        });

        return back()->with('success', 'Employee restored.');
    }
}
