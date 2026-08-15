<?php

namespace App\Services;

use App\Models\EmployeeNoticePeriodStep;
use App\Models\NoticePeriodFile;
use App\Models\NoticePeriodStep;
use App\Models\Resignation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class NoticePeriodService
{
    public function syncEmployeeSteps(int $employeeId): void
    {
        $templateIds = NoticePeriodStep::query()
            ->orderBy('step_order')
            ->pluck('step_id');

        foreach ($templateIds as $stepId) {
            EmployeeNoticePeriodStep::query()->firstOrCreate(
                [
                    'employee_id' => $employeeId,
                    'step_id' => $stepId,
                ],
                [
                    'status' => 0,
                    'comment' => '',
                    'created_at' => now(),
                ]
            );
        }
    }

    public function seedForResignation(int $employeeId): void
    {
        $this->syncEmployeeSteps($employeeId);
    }

    public function syncNewTemplateStep(int $stepId): void
    {
        $employeeIds = Resignation::query()
            ->where('status', '!=', 'Declined')
            ->whereHas('employeeNoticeSteps', fn ($q) => $q->where('status', 0))
            ->pluck('employee_id')
            ->unique();

        // Also include active resignations even if they have no incomplete steps yet
        $activeIds = Resignation::query()
            ->where('status', '!=', 'Declined')
            ->pluck('employee_id');

        $ids = $employeeIds->merge($activeIds)->unique();

        foreach ($ids as $employeeId) {
            EmployeeNoticePeriodStep::query()->firstOrCreate(
                [
                    'employee_id' => (int) $employeeId,
                    'step_id' => $stepId,
                ],
                [
                    'status' => 0,
                    'comment' => '',
                    'created_at' => now(),
                ]
            );
        }
    }

    public function wipeEmployeeSteps(int $employeeId): void
    {
        $files = NoticePeriodFile::query()->where('employee_id', $employeeId)->get();
        foreach ($files as $file) {
            $this->deleteFileRecord($file);
        }

        EmployeeNoticePeriodStep::query()->where('employee_id', $employeeId)->delete();
    }

    public function deleteTemplateStep(NoticePeriodStep $step): void
    {
        DB::transaction(function () use ($step) {
            $files = NoticePeriodFile::query()->where('step_id', $step->step_id)->get();
            foreach ($files as $file) {
                $this->deleteFileRecord($file);
            }

            EmployeeNoticePeriodStep::query()->where('step_id', $step->step_id)->delete();
            $step->delete();

            $remaining = NoticePeriodStep::query()->orderBy('step_order')->orderBy('step_id')->get();
            foreach ($remaining as $index => $row) {
                $row->update(['step_order' => $index]);
            }
        });
    }

    public function reorder(array $orderedStepIds): void
    {
        foreach (array_values($orderedStepIds) as $index => $stepId) {
            NoticePeriodStep::query()
                ->where('step_id', (int) $stepId)
                ->update(['step_order' => $index]);
        }
    }

    public function deleteFileRecord(NoticePeriodFile $file): void
    {
        $path = ltrim(str_replace('\\', '/', (string) $file->file_path), '/');
        if ($path !== '' && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }

        $absolute = $file->absolutePath();
        if ($absolute && is_file($absolute) && str_contains($absolute, 'hrmpulse_live-main')) {
            @unlink($absolute);
        }

        $file->delete();
    }
}
