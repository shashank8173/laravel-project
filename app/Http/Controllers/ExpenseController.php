<?php

namespace App\Http\Controllers;

use App\Models\EmailConfiguration;
use App\Models\Employee;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\ExpenseCompany;
use App\Models\PayslipSetting;
use App\Services\EmployeeNotificationService;
use App\Services\HrmMailer;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ExpenseController extends Controller
{
    private const PAYMENT_METHODS = [
        'CREDIT CARD',
        'DEBIT CARD',
        'NET BANKING',
        'UPI',
        'CHEQUE',
        'DD',
        'OFFLINE',
    ];

    public function my(Request $request): View
    {
        $status = $request->get('status');
        $employeeId = Auth::id();

        $advanceCategoryIds = ExpenseCategory::query()
            ->whereRaw('UPPER(TRIM(name)) = ?', [ExpenseCategory::ADVANCE_DISBURSEMENT])
            ->pluck('id');

        $base = Expense::query()
            ->where('employee_id', $employeeId)
            ->when($advanceCategoryIds->isNotEmpty(), fn ($q) => $q->whereNotIn('category_id', $advanceCategoryIds));

        $stats = [
            'total' => (clone $base)->count(),
            'pending' => (clone $base)->where('status', 'Pending')->count(),
            'approved' => (clone $base)->where('status', 'Approved')->count(),
            'rejected' => (clone $base)->where('status', 'Rejected')->count(),
            'amount' => (float) (clone $base)->sum('amount'),
            'approved_amount' => (float) (clone $base)->where('status', 'Approved')->sum('amount'),
        ];

        $expenses = (clone $base)
            ->with('category')
            ->when($status, fn ($q) => $q->where('status', $status))
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        $categories = ExpenseCategory::query()
            ->whereRaw('UPPER(TRIM(name)) != ?', [ExpenseCategory::ADVANCE_DISBURSEMENT])
            ->orderBy('name')
            ->get();

        $isAdmin = (bool) ($request->user()?->isAdmin());

        return view('expenses.employee', compact('expenses', 'categories', 'status', 'stats', 'isAdmin'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'category_id' => ['required', 'integer', 'exists:expense_categories,id'],
            'expense_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0'],
            'description' => ['nullable', 'string'],
            'receipt' => ['nullable', 'file', 'max:8192'],
        ]);

        $category = ExpenseCategory::query()->find($data['category_id']);
        if ($category?->isAdvanceDisbursement()) {
            return back()
                ->withInput()
                ->withErrors(['category_id' => 'Advance Disbursement Amount cannot be submitted by employees.']);
        }

        $path = null;
        if ($request->hasFile('receipt')) {
            $path = $request->file('receipt')->store('receipts', 'public');
        }

        Expense::create([
            'employee_id' => Auth::id(),
            'category_id' => $data['category_id'],
            'expense_date' => $data['expense_date'],
            'amount' => $data['amount'],
            'description' => $data['description'] ?? null,
            'receipt_path' => $path,
            'status' => 'Pending',
            'submitted_at' => now(),
            'payment_method' => '',
            'reference_id' => '',
        ]);

        return back()->with('success', 'Expense submitted.');
    }

    /**
     * Legacy manage_expenses.php
     */
    public function adminIndex(Request $request): View
    {
        $filters = $this->filtersFromRequest($request);

        $query = $this->filteredQuery($filters)->with(['employee', 'category', 'company']);

        $expenses = (clone $query)
            ->orderByDesc('expense_date')
            ->orderByDesc('id')
            ->paginate(40)
            ->withQueryString();

        $summaryRows = (clone $query)->get(['amount', 'expense_date', 'employee_id', 'company_id']);
        $totalAmount = (float) $summaryRows->sum('amount');
        $monthlyTotals = $summaryRows
            ->groupBy(fn (Expense $e) => optional($e->expense_date)->format('Y-m') ?: 'unknown')
            ->map(fn ($group) => (float) $group->sum('amount'))
            ->sortKeysDesc();

        $counts = [
            'all' => Expense::query()->count(),
            'pending' => Expense::query()->where('status', 'Pending')->count(),
            'approved' => Expense::query()->where('status', 'Approved')->count(),
            'rejected' => Expense::query()->where('status', 'Rejected')->count(),
        ];

        $months = Expense::query()
            ->selectRaw("DATE_FORMAT(expense_date, '%Y-%m') as month")
            ->whereNotNull('expense_date')
            ->groupBy('month')
            ->orderByDesc('month')
            ->pluck('month');

        ExpenseCategory::ensureAdvanceExists();

        $categories = ExpenseCategory::query()->orderBy('name')->get();
        $companies = ExpenseCompany::query()->orderBy('name')->get();
        $employees = Employee::query()
            ->with(['department:id,name', 'designation:id,name'])
            ->where('archive_status', 0)
            ->where('id', '!=', 14)
            ->orderBy('fname')
            ->get(['id', 'fname', 'lname', 'emp_id', 'department_id', 'designation_id']);

        $paymentMethods = self::PAYMENT_METHODS;
        $advanceBreakdowns = $this->buildAdvanceBreakdowns($filters);
        $officialFrom = EmailConfiguration::officialFromEmails();
        $docsFrom = EmailConfiguration::docsFrom();

        return view('expenses.admin', compact(
            'expenses',
            'categories',
            'companies',
            'employees',
            'filters',
            'counts',
            'totalAmount',
            'monthlyTotals',
            'months',
            'paymentMethods',
            'advanceBreakdowns',
            'officialFrom',
            'docsFrom'
        ));
    }

    public function adminPdf(Request $request): Response
    {
        $filters = $this->filtersFromRequest($request);
        $pdf = $this->buildExpensePdf($filters);
        $filename = 'expense_report_'.now()->format('d-m-Y').'.pdf';

        return $pdf->download($filename);
    }

    public function adminShare(Request $request, HrmMailer $mailer): RedirectResponse
    {
        $data = $request->validate([
            'to_email' => ['required', 'email'],
            'cc_emails' => ['nullable', 'string', 'max:1000'],
            'from_email' => ['nullable', 'email'],
            'subject' => ['required', 'string', 'max:200'],
            'body' => ['required', 'string', 'max:5000'],
            'status' => ['nullable', 'string'],
            'employee' => ['nullable', 'integer'],
            'company' => ['nullable', 'integer'],
            'month' => ['nullable', 'string'],
            'week' => ['nullable', 'string'],
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date'],
        ]);

        $cc = array_values(array_filter(array_map(
            'trim',
            preg_split('/[,;]+/', (string) ($data['cc_emails'] ?? '')) ?: []
        )));

        foreach ($cc as $ccEmail) {
            if (! filter_var($ccEmail, FILTER_VALIDATE_EMAIL)) {
                return back()
                    ->withInput()
                    ->withErrors(['cc_emails' => 'Invalid CC email: '.$ccEmail]);
            }
        }

        $filters = $this->filtersFromRequest($request);
        $filename = 'expense_report_'.now()->format('d-m-Y').'.pdf';
        $tmpDir = storage_path('app/temp');
        if (! is_dir($tmpDir)) {
            mkdir($tmpDir, 0755, true);
        }
        $tmpPath = $tmpDir.DIRECTORY_SEPARATOR.'expense_share_'.uniqid('', true).'.pdf';
        $ok = false;

        try {
            file_put_contents($tmpPath, $this->buildExpensePdf($filters)->output());

            $html = nl2br(e($data['body']));
            $ok = $mailer->sendDocument(
                $data['to_email'],
                $data['subject'],
                $html,
                $cc,
                $tmpPath,
                $filename,
                $data['from_email'] ?? null
            );
        } finally {
            if (is_file($tmpPath)) {
                @unlink($tmpPath);
            }
        }

        $redirectParams = $request->only(['status', 'employee', 'company', 'month', 'week', 'from_date', 'to_date']);

        if (! $ok) {
            return redirect()
                ->route('expenses.admin', $redirectParams)
                ->withErrors(['share' => 'Failed to send email. Check SMTP / Email Settings.']);
        }

        return redirect()
            ->route('expenses.admin', $redirectParams)
            ->with('success', 'Expense PDF emailed to '.$data['to_email'].'.');
    }

    public function adminStore(Request $request): RedirectResponse
    {
        $data = $this->validateExpense($request);

        if (empty($data['employee_id']) && empty($data['company_id'])) {
            return back()->withErrors(['employee_id' => 'Select an employee or a company.'])->withInput();
        }

        $path = $this->storeReceipt($request);

        Expense::create([
            'employee_id' => $data['employee_id'] ?: null,
            'company_id' => $data['company_id'] ?: null,
            'category_id' => $data['category_id'],
            'expense_date' => $data['expense_date'],
            'amount' => $data['amount'],
            'description' => $data['description'] ?? '',
            'payment_method' => $data['payment_method'] ?? '',
            'reference_id' => $data['reference_id'] ?? '',
            'receipt_path' => $path ?? '',
            'status' => 'Pending',
            'submitted_at' => now(),
        ]);

        return redirect()
            ->route('expenses.admin', $request->only(['status', 'employee', 'company', 'month', 'week', 'from_date', 'to_date']))
            ->with('success', 'Expense added successfully.');
    }

    public function adminUpdate(Request $request, Expense $expense): RedirectResponse
    {
        $expense->loadMissing('category');
        $data = $this->validateExpense($request);

        if (empty($data['employee_id']) && empty($data['company_id'])) {
            return back()->withErrors(['employee_id' => 'Select an employee or a company.'])->withInput();
        }

        $categoryId = (int) $data['category_id'];
        if ($expense->isAdvanceDisbursement()) {
            $categoryId = (int) $expense->category_id;
        }

        $payload = [
            'employee_id' => $data['employee_id'] ?: null,
            'company_id' => $data['company_id'] ?: null,
            'category_id' => $categoryId,
            'expense_date' => $data['expense_date'],
            'amount' => $data['amount'],
            'description' => $data['description'] ?? '',
            'payment_method' => $data['payment_method'] ?? '',
            'reference_id' => $data['reference_id'] ?? '',
        ];

        if ($request->hasFile('receipt')) {
            $payload['receipt_path'] = $this->storeReceipt($request);
        }

        $expense->update($payload);

        return redirect()
            ->route('expenses.admin', $request->only(['status', 'employee', 'company', 'month', 'week', 'from_date', 'to_date']))
            ->with('success', 'Expense updated successfully.');
    }

    public function updateStatus(Request $request, Expense $expense): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:Pending,Approved,Rejected'],
        ]);

        $expense->update([
            'status' => $data['status'],
            'approved_by' => Auth::id(),
            'approved_at' => now(),
        ]);

        if (in_array($data['status'], ['Approved', 'Rejected'], true) && $expense->employee_id) {
            $actor = Auth::user();
            app(EmployeeNotificationService::class)->notify(
                (int) $expense->employee_id,
                'expense_'.strtolower($data['status']),
                'Expense '.$data['status'].' · ₹'.number_format((float) $expense->amount, 2),
                'By '.($actor?->full_name ?: 'Admin'),
                route('expenses.mine'),
                $actor
            );
        }

        return back()->with('success', 'Expense '.$data['status'].' successfully.');
    }

    public function destroy(Expense $expense): RedirectResponse
    {
        $expense->loadMissing('category');

        if ($expense->isAdvanceDisbursement()) {
            return back()->withErrors([
                'expense' => 'Advance Disbursement Amount records cannot be deleted.',
            ]);
        }

        $expense->delete();

        return back()->with('success', 'Expense deleted successfully.');
    }

    public function storeCategory(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
        ]);

        if (strtoupper(trim($data['name'])) === ExpenseCategory::ADVANCE_DISBURSEMENT) {
            ExpenseCategory::ensureAdvanceExists();

            return back()->with('success', 'Advance Disbursement Amount category is already available.');
        }

        ExpenseCategory::create([
            'name' => $data['name'],
            'description' => $data['description'] ?? '',
        ]);

        return back()->with('success', 'Category added.');
    }

    public function updateCategory(Request $request, ExpenseCategory $category): RedirectResponse
    {
        if ($category->isAdvanceDisbursement()) {
            return back()->withErrors([
                'category' => 'Advance Disbursement Amount category cannot be renamed or changed.',
            ]);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
        ]);

        if (strtoupper(trim($data['name'])) === ExpenseCategory::ADVANCE_DISBURSEMENT) {
            return back()->withErrors([
                'category' => 'That name is reserved for Advance Disbursement Amount.',
            ]);
        }

        $category->update([
            'name' => $data['name'],
            'description' => $data['description'] ?? $category->description,
        ]);

        return back()->with('success', 'Category updated.');
    }

    public function destroyCategory(ExpenseCategory $category): RedirectResponse
    {
        if ($category->isAdvanceDisbursement()) {
            return back()->withErrors([
                'category' => 'Advance Disbursement Amount category cannot be deleted.',
            ]);
        }

        if ($category->expenses()->exists()) {
            return back()->withErrors(['category' => 'Cannot delete category used by expenses.']);
        }

        $category->delete();

        return back()->with('success', 'Category deleted.');
    }

    public function storeExpenseCompany(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        ExpenseCompany::create(['name' => $data['name']]);

        return back()->with('success', 'Company added.');
    }

    public function updateExpenseCompany(Request $request, ExpenseCompany $expenseCompany): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $expenseCompany->update(['name' => $data['name']]);

        return back()->with('success', 'Company updated.');
    }

    public function destroyExpenseCompany(ExpenseCompany $expenseCompany): RedirectResponse
    {
        if ($expenseCompany->expenses()->exists()) {
            return back()->withErrors(['company' => 'Cannot delete company used by expenses.']);
        }

        $expenseCompany->delete();

        return back()->with('success', 'Company deleted.');
    }

    private function validateExpense(Request $request): array
    {
        return $request->validate([
            'employee_id' => ['nullable', 'integer', 'exists:hrm_employee,id'],
            'company_id' => ['nullable', 'integer', 'exists:companiesexpense,id'],
            'category_id' => ['required', 'integer', 'exists:expense_categories,id'],
            'expense_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0'],
            'description' => ['nullable', 'string'],
            'payment_method' => ['nullable', 'string', 'max:50'],
            'reference_id' => ['nullable', 'string', 'max:120'],
            'receipt' => ['nullable', 'file', 'max:8192'],
        ]);
    }

    private function storeReceipt(Request $request): ?string
    {
        if (! $request->hasFile('receipt')) {
            return null;
        }

        return $request->file('receipt')->store('receipts', 'public');
    }

    private function buildExpensePdf(array $filters)
    {
        $expenses = $this->filteredQuery($filters)
            ->with(['employee', 'category', 'company'])
            ->orderBy('expense_date')
            ->orderBy('id')
            ->get();

        $totalAmount = (float) $expenses->sum('amount');
        $payslip = PayslipSetting::current();
        $filterLabel = $this->filterLabel($filters);
        $receiptNo = 'EXP-'.now()->format('Ymd-His');
        $amountInWords = $this->amountInWords($totalAmount);
        $logoPath = $payslip->logoAbsolutePath();
        $logoDataUri = null;
        if ($logoPath && is_file($logoPath)) {
            $mime = mime_content_type($logoPath) ?: 'image/jpeg';
            if (! str_starts_with($mime, 'image/')) {
                $ext = strtolower(pathinfo($logoPath, PATHINFO_EXTENSION));
                $mime = match ($ext) {
                    'png' => 'image/png',
                    'gif' => 'image/gif',
                    'webp' => 'image/webp',
                    default => 'image/jpeg',
                };
            }
            $logoDataUri = 'data:'.$mime.';base64,'.base64_encode((string) file_get_contents($logoPath));
        }

        return Pdf::loadView('expenses.pdf', compact(
            'expenses',
            'totalAmount',
            'payslip',
            'filterLabel',
            'receiptNo',
            'amountInWords',
            'logoDataUri',
            'filters'
        ))->setPaper('a4', 'portrait');
    }

    /**
     * @return array{status:?string,employee:int,company:int,month:?string,week:?string,from_date:?string,to_date:?string}
     */
    private function filtersFromRequest(Request $request): array
    {
        return [
            'status' => $request->get('status') ?: null,
            'employee' => (int) $request->get('employee', 0),
            'company' => (int) $request->get('company', 0),
            'month' => $request->get('month') ?: null,
            'week' => $request->get('week') ?: null,
            'from_date' => $request->get('from_date') ?: null,
            'to_date' => $request->get('to_date') ?: null,
        ];
    }

    private function filteredQuery(array $filters)
    {
        return Expense::query()
            ->when($filters['status'], fn ($q) => $q->where('status', $filters['status']))
            ->when($filters['employee'] > 0, fn ($q) => $q->where('employee_id', $filters['employee']))
            ->when($filters['company'] > 0, fn ($q) => $q->where('company_id', $filters['company']))
            ->when($filters['month'], fn ($q) => $q->whereRaw("DATE_FORMAT(expense_date, '%Y-%m') = ?", [$filters['month']]))
            ->when($filters['month'] && $filters['week'] && in_array($filters['week'], ['1', '2', '3', '4', '5'], true), function ($q) use ($filters) {
                [$start, $end] = $this->weekRange($filters['month'], (int) $filters['week']);
                $q->whereBetween('expense_date', [$start, $end]);
            })
            ->when($filters['from_date'] && $filters['to_date'], function ($q) use ($filters) {
                $q->whereBetween('expense_date', [$filters['from_date'], $filters['to_date']]);
            });
    }

    /**
     * @return array{0:string,1:string}
     */
    private function weekRange(string $month, int $week): array
    {
        $monthStart = Carbon::createFromFormat('Y-m-d', $month.'-01')->startOfDay();
        $weekStart = $monthStart->copy()->addDays(($week - 1) * 7);
        $weekEnd = $weekStart->copy()->addDays(6);
        if ($weekEnd->format('Y-m') !== $month) {
            $weekEnd = $monthStart->copy()->endOfMonth();
        }

        return [$weekStart->toDateString(), $weekEnd->toDateString()];
    }

    /**
     * Remaining = Advance Disbursement − other expenses.
     * Applies to every employee and every expense-company.
     * Plus (green)  = advance still with them / settled after company returned amount
     * Minus (red)   = only expenses so far, or overspent vs advance
     *
     * @return array<int, array{type:string,name:string,advance:float,expenses:float,remaining:float,categories:array<int, array{name:string,total:float}>}>
     */
    private function buildAdvanceBreakdowns(array $filters): array
    {
        $rows = $this->filteredQuery($filters)
            ->with(['employee', 'company', 'category'])
            ->get(['employee_id', 'company_id', 'category_id', 'amount']);

        if ($rows->isEmpty()) {
            return [];
        }

        $buckets = [];

        foreach ($rows as $expense) {
            if ($expense->employee_id) {
                $key = 'e:'.$expense->employee_id;
                if (! isset($buckets[$key])) {
                    $buckets[$key] = [
                        'type' => 'employee',
                        'name' => $expense->employee?->full_name ?? ('Employee #'.$expense->employee_id),
                        'items' => collect(),
                    ];
                }
                $buckets[$key]['items']->push($expense);
            }

            if ($expense->company_id) {
                $key = 'c:'.$expense->company_id;
                if (! isset($buckets[$key])) {
                    $buckets[$key] = [
                        'type' => 'company',
                        'name' => $expense->company?->name ?? ('Company #'.$expense->company_id),
                        'items' => collect(),
                    ];
                }
                $buckets[$key]['items']->push($expense);
            }
        }

        $breakdowns = [];

        foreach ($buckets as $bucket) {
            /** @var \Illuminate\Support\Collection<int, Expense> $items */
            $items = $bucket['items'];

            $categories = $items
                ->groupBy(fn (Expense $e) => $e->category?->name ?: 'Uncategorized')
                ->map(fn ($group, $name) => [
                    'name' => (string) $name,
                    'total' => (float) $group->sum('amount'),
                ])
                ->sortBy('name')
                ->values()
                ->all();

            $advance = (float) $items
                ->filter(fn (Expense $e) => $e->isAdvanceDisbursement())
                ->sum('amount');

            $expenseTotal = (float) $items
                ->reject(fn (Expense $e) => $e->isAdvanceDisbursement())
                ->sum('amount');

            $breakdowns[] = [
                'type' => $bucket['type'],
                'name' => $bucket['name'],
                'advance' => $advance,
                'expenses' => $expenseTotal,
                'remaining' => $advance - $expenseTotal,
                'categories' => $categories,
            ];
        }

        usort($breakdowns, function ($a, $b) {
            $typeCmp = strcmp($a['type'], $b['type']);
            if ($typeCmp !== 0) {
                return $typeCmp;
            }

            return strcasecmp($a['name'], $b['name']);
        });

        return $breakdowns;
    }

    private function filterLabel(array $filters): string
    {
        $parts = [];

        if (! empty($filters['employee'])) {
            $emp = Employee::query()->find($filters['employee']);
            $parts[] = 'Employee: '.($emp?->full_name ?? '#'.$filters['employee']);
        }

        if (! empty($filters['company'])) {
            $com = ExpenseCompany::query()->find($filters['company']);
            $parts[] = 'Company: '.($com?->name ?? '#'.$filters['company']);
        }

        if (! empty($filters['status'])) {
            $parts[] = 'Status: '.$filters['status'];
        }

        if (! empty($filters['from_date']) && ! empty($filters['to_date'])) {
            $parts[] = 'Date: '.$filters['from_date'].' to '.$filters['to_date'];
        } elseif (! empty($filters['month']) && ! empty($filters['week'])) {
            $monthName = Carbon::createFromFormat('Y-m', $filters['month'])->format('F Y');
            $parts[] = 'Week '.$filters['week'].' of '.$monthName;
        } elseif (! empty($filters['month'])) {
            $parts[] = 'Month: '.Carbon::createFromFormat('Y-m', $filters['month'])->format('F Y');
        }

        return $parts ? implode(' | ', $parts) : 'All expenses (no filters)';
    }

    private function amountInWords(float $amount): string
    {
        $rupees = (int) floor($amount);
        $paise = (int) round(($amount - $rupees) * 100);

        $words = $this->numberToWords($rupees).' Rupees';
        if ($paise > 0) {
            $words .= ' and '.$this->numberToWords($paise).' Paise';
        }

        return $words.' Only';
    }

    private function numberToWords(int $number): string
    {
        if ($number === 0) {
            return 'Zero';
        }

        $ones = ['', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine', 'Ten', 'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen', 'Seventeen', 'Eighteen', 'Nineteen'];
        $tens = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];

        $convert = function (int $n) use (&$convert, $ones, $tens): string {
            if ($n < 20) {
                return $ones[$n];
            }
            if ($n < 100) {
                return trim($tens[(int) floor($n / 10)].' '.$ones[$n % 10]);
            }
            if ($n < 1000) {
                return trim($ones[(int) floor($n / 100)].' Hundred'.($n % 100 ? ' '.$convert($n % 100) : ''));
            }
            if ($n < 100000) {
                return trim($convert((int) floor($n / 1000)).' Thousand'.($n % 1000 ? ' '.$convert($n % 1000) : ''));
            }
            if ($n < 10000000) {
                return trim($convert((int) floor($n / 100000)).' Lakh'.($n % 100000 ? ' '.$convert($n % 100000) : ''));
            }

            return trim($convert((int) floor($n / 10000000)).' Crore'.($n % 10000000 ? ' '.$convert($n % 10000000) : ''));
        };

        return $convert($number);
    }
}
