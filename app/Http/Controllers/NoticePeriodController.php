<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\EmployeeNoticePeriodStep;
use App\Models\EmailConfiguration;
use App\Models\NoticePeriodFile;
use App\Models\Resignation;
use App\Models\ResignationHistory;
use App\Services\HrmMailer;
use App\Services\NoticePeriodService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class NoticePeriodController extends Controller
{
    public function index(): View
    {
        $employees = Resignation::query()
            ->with(['employee.department:id,name', 'employee.designation:id,name'])
            ->where('status', '!=', 'Declined')
            ->latest('id')
            ->get()
            ->unique('employee_id')
            ->values();

        return view('notice-period.index', compact('employees'));
    }

    public function show(int $employee, NoticePeriodService $service): View
    {
        $emp = Employee::query()->findOrFail($employee);

        $service->syncEmployeeSteps($employee);

        $resignation = Resignation::query()
            ->with('approver')
            ->where('employee_id', $employee)
            ->where('status', '!=', 'Declined')
            ->latest('id')
            ->first();

        $steps = EmployeeNoticePeriodStep::query()
            ->with('step')
            ->where('employee_id', $employee)
            ->get()
            ->sortBy(fn ($row) => $row->step?->step_order ?? 999)
            ->values();

        $files = NoticePeriodFile::query()
            ->where('employee_id', $employee)
            ->orderByDesc('id')
            ->get()
            ->groupBy('step_id');

        $history = ResignationHistory::query()
            ->with('changer')
            ->where('employee_id', $employee)
            ->orderByDesc('changed_at')
            ->orderByDesc('id')
            ->get();

        $completed = $steps->where('status', 1)->count();
        $total = $steps->count();
        $percent = $total > 0 ? (int) round(($completed / $total) * 100) : 0;

        return view('notice-period.show', compact(
            'emp',
            'resignation',
            'steps',
            'files',
            'history',
            'completed',
            'total',
            'percent'
        ));
    }

    public function history(): View
    {
        $rows = ResignationHistory::query()
            ->with(['employee', 'changer', 'resignation'])
            ->orderByDesc('changed_at')
            ->orderByDesc('id')
            ->paginate(40);

        return view('notice-period.history', compact('rows'));
    }

    public function updateResignation(
        Request $request,
        int $employee,
        NoticePeriodService $service,
        HrmMailer $mailer
    ): RedirectResponse {
        $data = $request->validate([
            'resignation_id' => ['required', 'integer', 'exists:employee_resignations,id'],
            'status' => ['required', 'in:Pending,Approved,Declined'],
            'notice_period_days' => ['required', 'integer', 'in:15,30'],
            'decline_reason' => ['nullable', 'string', 'max:2000'],
        ]);

        if ($data['status'] === 'Declined' && blank($data['decline_reason'] ?? null)) {
            return back()->withErrors(['decline_reason' => 'Decline reason is required.']);
        }

        $resignation = Resignation::query()
            ->where('id', $data['resignation_id'])
            ->where('employee_id', $employee)
            ->firstOrFail();

        $employeeModel = Employee::query()->findOrFail($employee);
        $oldStatus = $resignation->status;
        $oldDays = (int) $resignation->notice_period_days;

        DB::transaction(function () use ($data, $resignation, $employee, $service, $oldStatus, $oldDays) {
            $resignation->update([
                'status' => $data['status'],
                'notice_period_days' => $data['notice_period_days'],
                'decline_reason' => $data['status'] === 'Declined' ? $data['decline_reason'] : null,
                'approved_by' => Auth::id(),
                'approved_at' => $data['status'] === 'Pending' ? null : now(),
                'updated_at' => now(),
            ]);

            if ($oldStatus !== $data['status']) {
                ResignationHistory::create([
                    'resignation_id' => $resignation->id,
                    'employee_id' => $employee,
                    'status' => $data['status'],
                    'notice_period_days' => $data['notice_period_days'],
                    'changed_by' => Auth::id(),
                    'changed_at' => now(),
                    'comment' => $data['status'] === 'Declined'
                        ? 'Status changed to Declined: '.$data['decline_reason']
                        : 'Status changed to '.$data['status'],
                ]);
            }

            if ($oldDays !== (int) $data['notice_period_days']) {
                ResignationHistory::create([
                    'resignation_id' => $resignation->id,
                    'employee_id' => $employee,
                    'status' => $data['status'],
                    'notice_period_days' => $data['notice_period_days'],
                    'changed_by' => Auth::id(),
                    'changed_at' => now(),
                    'comment' => 'Notice period changed to '.$data['notice_period_days'].' days',
                ]);
            }

            if ($data['status'] === 'Declined') {
                $service->wipeEmployeeSteps($employee);
            }
        });

        $this->notifyChange(
            $mailer,
            $employeeModel,
            $data['status'],
            (int) $data['notice_period_days'],
            $oldStatus !== $data['status']
                ? ($data['status'] === 'Declined' ? 'Status changed to Declined: '.$data['decline_reason'] : 'Status changed to '.$data['status'])
                : 'Notice period changed to '.$data['notice_period_days'].' days'
        );

        return redirect()
            ->route('notice-period.show', $employee)
            ->with('success', 'Resignation updated.');
    }

    public function updateStep(Request $request, EmployeeNoticePeriodStep $employeeStep): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['nullable', 'boolean'],
            'comment' => ['nullable', 'string'],
            'files' => ['nullable', 'array'],
            'files.*' => ['file', 'max:10240'],
        ]);

        $employeeStep->update([
            'status' => $request->boolean('status') ? 1 : 0,
            'comment' => $data['comment'] ?? '',
            'update_date' => now(),
        ]);

        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $uploaded) {
                if (! $uploaded) {
                    continue;
                }
                $path = $uploaded->store('notice_period', 'public');
                NoticePeriodFile::create([
                    'step_id' => $employeeStep->step_id,
                    'employee_id' => $employeeStep->employee_id,
                    'document_name' => $uploaded->getClientOriginalName(),
                    'file_path' => $path,
                    'created_at' => now(),
                ]);
            }
        }

        return redirect()
            ->route('notice-period.show', $employeeStep->employee_id)
            ->with('success', 'Step updated.');
    }

    public function destroyFile(NoticePeriodFile $file, NoticePeriodService $service): RedirectResponse
    {
        $employeeId = $file->employee_id;
        $service->deleteFileRecord($file);

        return redirect()
            ->route('notice-period.show', $employeeId)
            ->with('success', 'File deleted.');
    }

    public function downloadFile(NoticePeriodFile $file): BinaryFileResponse
    {
        $absolute = $file->absolutePath();
        if (! $absolute || ! is_file($absolute)) {
            // Try storage path directly
            $path = ltrim((string) $file->file_path, '/');
            if (Storage::disk('public')->exists($path)) {
                return Storage::disk('public')->download($path, $file->document_name);
            }
            abort(404, 'File not found.');
        }

        return response()->download($absolute, $file->document_name ?: basename($absolute));
    }

    private function notifyChange(
        HrmMailer $mailer,
        Employee $employee,
        string $status,
        int $days,
        string $comment
    ): void {
        $toList = EmailConfiguration::recipients('NOTICE_PERIOD_RECIPIENTS');
        $cc = EmailConfiguration::recipients('NOTICE_PERIOD_CC');
        if ($official = $employee->officialEmail()) {
            $cc[] = $official;
        }
        $cc = array_values(array_unique(array_filter($cc)));

        $admin = Auth::user();
        $html = '<h3>Notice Period / Resignation Update</h3>'
            .'<p><strong>Employee:</strong> '.e($employee->full_name).' (ID: '.$employee->id.')</p>'
            .'<p><strong>Official Email:</strong> '.e((string) ($official ?? '—')).'</p>'
            .'<p><strong>Status:</strong> '.e($status).'</p>'
            .'<p><strong>Notice Period:</strong> '.$days.' days</p>'
            .'<p><strong>Updated By:</strong> '.e($admin?->full_name ?? 'Admin').'</p>'
            .'<p><strong>Comment:</strong> '.e($comment).'</p>';

        foreach ($toList as $to) {
            $mailer->send($to, 'Notice Period Update - '.$employee->full_name, $html, $cc);
        }
    }
}
