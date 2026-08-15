<?php

namespace App\Http\Controllers;

use App\Models\NoticePeriodStep;
use App\Services\NoticePeriodService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NoticePeriodStepController extends Controller
{
    public function index(): View
    {
        $steps = NoticePeriodStep::query()
            ->orderBy('step_order')
            ->orderBy('step_id')
            ->get();

        return view('notice-period.steps', compact('steps'));
    }

    public function store(Request $request, NoticePeriodService $service): RedirectResponse
    {
        $data = $request->validate([
            'step_name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $maxOrder = (int) NoticePeriodStep::query()->max('step_order');

        $step = NoticePeriodStep::create([
            'step_name' => $data['step_name'],
            'description' => $data['description'] ?? '',
            'step_order' => $maxOrder + 1,
            'created_at' => now(),
        ]);

        $service->syncNewTemplateStep((int) $step->step_id);

        return back()->with('success', 'Step added and synced to active notice periods.');
    }

    public function update(Request $request, NoticePeriodStep $noticePeriodStep): RedirectResponse
    {
        $data = $request->validate([
            'step_name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $noticePeriodStep->update([
            'step_name' => $data['step_name'],
            'description' => $data['description'] ?? '',
        ]);

        return back()->with('success', 'Step updated.');
    }

    public function destroy(NoticePeriodStep $noticePeriodStep, NoticePeriodService $service): RedirectResponse
    {
        $service->deleteTemplateStep($noticePeriodStep);

        return back()->with('success', 'Step deleted.');
    }

    public function reorder(Request $request, NoticePeriodService $service): JsonResponse
    {
        $data = $request->validate([
            'order' => ['required', 'array', 'min:1'],
            'order.*' => ['integer', 'exists:notice_period_steps,step_id'],
        ]);

        $service->reorder($data['order']);

        return response()->json(['status' => 'ok', 'message' => 'Order saved.']);
    }
}
