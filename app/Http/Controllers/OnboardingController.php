<?php

namespace App\Http\Controllers;

use App\Models\EmailConfiguration;
use App\Models\Employee;
use App\Models\EmployeeOnboardingStep;
use App\Models\OnboardingFile;
use App\Models\OnboardingStep;
use App\Services\HrmMailer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class OnboardingController extends Controller
{
    /**
     * Legacy onboarding.php + onboardingtest.php (steps tab).
     */
    public function index(Request $request): View
    {
        $tab = $request->get('tab', 'progress') === 'steps' ? 'steps' : 'progress';
        $employeeId = (int) $request->get('employee_id');

        $masterSteps = OnboardingStep::query()->orderBy('step_order')->get();

        $employees = Employee::query()
            ->with(['department:id,name', 'designation:id,name'])
            ->where('archive_status', 0)
            ->when(
                ($excluded = config('hrm.excluded_employee_ids', [14])) !== [],
                fn ($q) => $q->whereNotIn('id', $excluded)
            )
            ->orderBy('fname')
            ->get(['id', 'fname', 'lname', 'email', 'emp_id', 'doj', 'department_id', 'designation_id']);

        $selected = null;
        $progress = collect();
        $completed = 0;
        $total = 0;
        $percent = 0;

        if ($employeeId > 0) {
            $selected = $employees->firstWhere('id', $employeeId)
                ?: Employee::query()->find($employeeId);

            if ($selected) {
                $this->syncEmployeeSteps($employeeId);

                $progress = EmployeeOnboardingStep::query()
                    ->with(['step', 'allStepFiles'])
                    ->where('employee_id', $employeeId)
                    ->get()
                    ->sortBy(fn (EmployeeOnboardingStep $row) => (int) ($row->step?->step_order ?? 9999))
                    ->values();

                $total = $progress->count();
                $completed = $progress->where('status', 1)->count();
                $percent = $total > 0 ? (int) round(($completed / $total) * 100) : 0;
            }
        }

        return view('onboarding.index', compact(
            'tab',
            'masterSteps',
            'employees',
            'employeeId',
            'selected',
            'progress',
            'completed',
            'total',
            'percent'
        ));
    }

    public function updateStep(Request $request, EmployeeOnboardingStep $step, HrmMailer $mailer): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['nullable', 'boolean'],
            'comment' => ['nullable', 'string', 'max:5000'],
            'files' => ['nullable', 'array'],
            'files.*' => ['file', 'max:10240'],
        ]);

        $wasComplete = (int) $step->status === 1;
        $step->status = $request->boolean('status') ? 1 : 0;
        $step->comment = $data['comment'] ?? '';
        $step->update_date = now();
        $step->save();

        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $file) {
                if (! $file || ! $file->isValid()) {
                    continue;
                }
                $stored = $this->storeUpload($file, (int) $step->employee_id);
                OnboardingFile::create([
                    'step_id' => $step->step_id,
                    'employee_id' => $step->employee_id,
                    'document_name' => $file->getClientOriginalName(),
                    'file_path' => $stored,
                ]);
            }
        }

        if (! $wasComplete && (int) $step->status === 1) {
            $this->notifyOnboardingUpdate($mailer, $step);
        }

        return redirect()
            ->route('onboarding.index', ['employee_id' => $step->employee_id, 'tab' => 'progress'])
            ->with('success', 'Step updated.');
    }

    private function notifyOnboardingUpdate(HrmMailer $mailer, EmployeeOnboardingStep $step): void
    {
        $toList = array_values(array_filter(
            EmailConfiguration::recipients('ONBOARDING_RECIPIENTS'),
            fn ($email) => filter_var($email, FILTER_VALIDATE_EMAIL)
        ));
        if ($toList === []) {
            return;
        }

        $cc = array_values(array_filter(
            EmailConfiguration::recipients('ONBOARDING_CC'),
            fn ($email) => filter_var($email, FILTER_VALIDATE_EMAIL)
        ));

        $step->loadMissing(['employee', 'step']);
        $employeeName = $step->employee?->full_name ?: ('Employee #'.$step->employee_id);
        $stepName = $step->step?->step_name ?: ('Step #'.$step->step_id);

        $to = array_shift($toList);
        $mailer->send(
            $to,
            'Onboarding step completed: '.$employeeName,
            'Onboarding progress update.<br><br>'
                .'<strong>Employee:</strong> '.e($employeeName).'<br>'
                .'<strong>Step:</strong> '.e($stepName).'<br>'
                .'<strong>Status:</strong> Completed',
            array_values(array_unique(array_merge($toList, $cc)))
        );
    }

    public function destroyFile(Request $request, OnboardingFile $file): RedirectResponse
    {
        $employeeId = (int) $file->employee_id;
        $path = public_path(ltrim(str_replace('\\', '/', (string) $file->file_path), '/'));
        if (is_file($path)) {
            @unlink($path);
        }
        $file->delete();

        return redirect()
            ->route('onboarding.index', ['employee_id' => $employeeId, 'tab' => 'progress'])
            ->with('success', 'File deleted.');
    }

    public function storeMasterStep(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'step_name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
        ]);

        $maxOrder = (int) (OnboardingStep::query()->max('step_order') ?? -1);

        OnboardingStep::create([
            'step_name' => $data['step_name'],
            'description' => $data['description'] ?? '',
            'step_order' => $maxOrder + 1,
        ]);

        return redirect()
            ->route('onboarding.index', ['tab' => 'steps'])
            ->with('success', 'Onboarding step added.');
    }

    public function updateMasterStep(Request $request, OnboardingStep $masterStep): RedirectResponse
    {
        $data = $request->validate([
            'step_name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
        ]);

        $masterStep->update([
            'step_name' => $data['step_name'],
            'description' => $data['description'] ?? '',
        ]);

        return redirect()
            ->route('onboarding.index', ['tab' => 'steps'])
            ->with('success', 'Step updated.');
    }

    public function destroyMasterStep(OnboardingStep $masterStep): RedirectResponse
    {
        DB::transaction(function () use ($masterStep) {
            OnboardingFile::query()->where('step_id', $masterStep->step_id)->delete();
            EmployeeOnboardingStep::query()->where('step_id', $masterStep->step_id)->delete();
            $masterStep->delete();

            $remaining = OnboardingStep::query()->orderBy('step_order')->get();
            foreach ($remaining as $i => $row) {
                $row->update(['step_order' => $i]);
            }
        });

        return redirect()
            ->route('onboarding.index', ['tab' => 'steps'])
            ->with('success', 'Step deleted.');
    }

    public function reorderMasterSteps(Request $request): JsonResponse
    {
        $data = $request->validate([
            'order' => ['required', 'array', 'min:1'],
            'order.*' => ['integer', 'exists:onboarding_steps,step_id'],
        ]);

        DB::transaction(function () use ($data) {
            foreach (array_values($data['order']) as $index => $stepId) {
                OnboardingStep::query()
                    ->where('step_id', $stepId)
                    ->update(['step_order' => $index]);
            }
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Step order saved.',
        ]);
    }

    private function syncEmployeeSteps(int $employeeId): void
    {
        $steps = OnboardingStep::query()->orderBy('step_order')->get();
        foreach ($steps as $step) {
            EmployeeOnboardingStep::query()->firstOrCreate(
                [
                    'employee_id' => $employeeId,
                    'step_id' => $step->step_id,
                ],
                [
                    'status' => 0,
                    'comment' => '',
                    'created_at' => now(),
                ]
            );
        }
    }

    private function storeUpload(\Illuminate\Http\UploadedFile $file, int $employeeId): string
    {
        $dir = public_path('uploads/onboarding');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $safe = preg_replace('/[^A-Za-z0-9._-]/', '_', $file->getClientOriginalName()) ?: 'file.bin';
        $name = $employeeId.'_'.time().'_'.$safe;
        $file->move($dir, $name);

        return 'uploads/onboarding/'.$name;
    }
}
