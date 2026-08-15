<?php

namespace App\Http\Controllers;

use App\Models\AdvanceSalary;
use App\Models\Employee;
use App\Models\PayslipSetting;
use App\Models\SalaryManagement;
use App\Services\HrmMailer;
use App\Services\SalaryCalculationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class SalaryController extends Controller
{
    public function index(Request $request): View
    {
        $q = trim((string) $request->get('q', ''));

        $employees = Employee::query()
            ->with('designation')
            ->where('archive_status', 0)
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($inner) use ($q) {
                    $inner->where('fname', 'like', "%{$q}%")
                        ->orWhere('lname', 'like', "%{$q}%")
                        ->orWhere('emp_id', 'like', "%{$q}%");
                });
            })
            ->orderBy('fname')
            ->paginate(30)
            ->withQueryString();

        return view('salary.index', compact('employees', 'q'));
    }

    public function update(Request $request, Employee $employee): RedirectResponse
    {
        $data = $request->validate([
            'salary' => ['required', 'numeric', 'min:0'],
        ]);

        $employee->update(['salary' => $data['salary']]);

        $row = SalaryManagement::query()->firstOrNew(['emp_id' => $employee->id]);
        if (! $row->exists) {
            $row->actual_salary = $data['salary'];
            $row->added_date = now();
            $row->status = 1;
        }
        $row->current_salary = $data['salary'];
        $row->updated_date = now();
        $row->save();

        return back()->with('success', 'Salary updated.');
    }

    /**
     * Legacy calculate-salary.php?id=&month=&year=
     */
    public function calculate(Request $request, SalaryCalculationService $calculator): View
    {
        $employeeId = (int) $request->get('id');
        $month = (int) $request->get('month', now()->month);
        $year = (int) $request->get('year', now()->year);

        $employees = Employee::query()
            ->with(['department:id,name', 'designation:id,name'])
            ->where('status', 1)
            ->where('archive_status', 0)
            ->where('id', '!=', 14)
            ->orderBy('fname')
            ->get(['id', 'fname', 'lname', 'salary', 'department_id', 'designation_id', 'emp_id']);

        $employee = null;
        $calc = null;
        $payslip = PayslipSetting::current();

        if ($employeeId > 0 && $month >= 1 && $month <= 12 && $year > 2000) {
            $employee = Employee::query()
                ->with(['designation', 'department', 'bankDetail'])
                ->find($employeeId);

            if ($employee) {
                $calc = $calculator->calculate($employee, $month, $year);
            }
        }

        return view('salary.calculate', compact('employees', 'employee', 'month', 'year', 'calc', 'payslip'));
    }

    /**
     * Update company details / logo / digital signature used on salary slips.
     */
    public function updatePayslipSettings(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'company_name' => ['required', 'string', 'max:255'],
            'company_address' => ['nullable', 'string', 'max:2000'],
            'cin' => ['nullable', 'string', 'max:64'],
            'location' => ['nullable', 'string', 'max:120'],
            'signatory_name' => ['nullable', 'string', 'max:120'],
            'footer_note' => ['nullable', 'string', 'max:1000'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:2048'],
            'signature' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:2048'],
            'remove_logo' => ['nullable', 'boolean'],
            'remove_signature' => ['nullable', 'boolean'],
        ]);

        $payslip = PayslipSetting::current();
        $payslip->fill([
            'company_name' => $data['company_name'],
            'company_address' => $data['company_address'] ?? null,
            'cin' => $data['cin'] ?? null,
            'location' => $data['location'] ?? 'Delhi',
            'signatory_name' => $data['signatory_name'] ?? 'Authorized Signatory',
            'footer_note' => $data['footer_note'] ?? null,
        ]);

        if ($request->boolean('remove_logo') && $payslip->logo_path) {
            Storage::disk('public')->delete($payslip->logo_path);
            $payslip->logo_path = null;
        }

        if ($request->boolean('remove_signature') && $payslip->signature_path) {
            Storage::disk('public')->delete($payslip->signature_path);
            $payslip->signature_path = null;
        }

        if ($request->hasFile('logo')) {
            if ($payslip->logo_path) {
                Storage::disk('public')->delete($payslip->logo_path);
            }
            $payslip->logo_path = $request->file('logo')->store('payslip', 'public');
        }

        if ($request->hasFile('signature')) {
            if ($payslip->signature_path) {
                Storage::disk('public')->delete($payslip->signature_path);
            }
            $payslip->signature_path = $request->file('signature')->store('payslip', 'public');
        }

        $payslip->save();

        return back()->with('success', 'Payslip company details saved.');
    }

    /**
     * Legacy email/send-salary-slip.php
     */
    public function sendSlip(Request $request, HrmMailer $mailer): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'employee_id' => ['nullable', 'integer', 'exists:hrm_employee,id'],
            'pdf' => ['required', 'file', 'mimes:pdf', 'max:10240'],
        ]);

        $to = $data['email'];
        if (! empty($data['employee_id'])) {
            $employee = \App\Models\Employee::query()->find($data['employee_id']);
            $official = $employee?->officialEmail();
            if (! $official) {
                return response()->json([
                    'success' => false,
                    'error' => 'Employee official email missing. Set office_email first.',
                ], 422);
            }
            $to = $official;
        }

        $pdf = $data['pdf'];
        $tmp = $pdf->getRealPath();

        $ok = $mailer->sendDocument(
            $to,
            'Your Salary Slip',
            'Dear Employee,<br><br>Please find your salary slip attached.<br><br>Best regards,<br>HR Team',
            [],
            $tmp ?: null,
            'Salary_Slip.pdf'
        );

        return $ok
            ? response()->json(['success' => true])
            : response()->json(['success' => false, 'error' => 'Failed to send email. Check SMTP settings.'], 500);
    }

    public function advances(): View
    {
        $advances = AdvanceSalary::query()
            ->with('employee')
            ->latest('id')
            ->paginate(30);

        $employees = Employee::query()
            ->with(['department:id,name', 'designation:id,name'])
            ->where('status', 1)
            ->where('archive_status', 0)
            ->orderBy('fname')
            ->get(['id', 'fname', 'lname', 'department_id', 'designation_id', 'emp_id']);

        return view('salary.advances', compact('advances', 'employees'));
    }

    public function storeAdvance(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'emp_id' => ['required', 'integer', 'exists:hrm_employee,id'],
            'advance_amount' => ['required', 'numeric', 'min:1'],
            'monthly_deduction' => ['required', 'numeric', 'min:1'],
            'advance_date' => ['required', 'date'],
        ]);

        AdvanceSalary::create([
            ...$data,
            'remaining_amount' => $data['advance_amount'],
            'status' => 1,
            'added_date' => now(),
        ]);

        return back()->with('success', 'Advance salary recorded.');
    }
}
