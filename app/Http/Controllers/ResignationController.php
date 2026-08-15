<?php

namespace App\Http\Controllers;

use App\Models\Resignation;
use App\Models\ResignationHistory;
use App\Services\NoticePeriodService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ResignationController extends Controller
{
    public function my(): View
    {
        $employeeId = (int) Auth::id();

        $resignations = Resignation::query()
            ->where('employee_id', $employeeId)
            ->latest('id')
            ->get();

        $active = $resignations->first(fn (Resignation $r) => in_array($r->status, ['Pending', 'Approved'], true));

        $history = ResignationHistory::query()
            ->with(['changer:id,fname,lname', 'resignation'])
            ->where('employee_id', $employeeId)
            ->latest('id')
            ->limit(20)
            ->get();

        $stats = [
            'total' => $resignations->count(),
            'pending' => $resignations->where('status', 'Pending')->count(),
            'approved' => $resignations->where('status', 'Approved')->count(),
            'declined' => $resignations->where('status', 'Declined')->count(),
        ];

        $canApply = ! $resignations->contains(fn (Resignation $r) => in_array($r->status, ['Pending', 'Approved'], true));

        return view('resignations.employee', compact('resignations', 'active', 'history', 'stats', 'canApply'));
    }

    public function store(Request $request, NoticePeriodService $service): RedirectResponse
    {
        $blocked = Resignation::query()
            ->where('employee_id', Auth::id())
            ->whereIn('status', ['Pending', 'Approved'])
            ->exists();

        if ($blocked) {
            return back()->withErrors(['resignation' => 'You already have an active resignation.']);
        }

        $data = $request->validate([
            'resignation_reason' => ['required', 'string', 'max:5000'],
            'intended_last_date' => ['required', 'date', 'after:today'],
            'notice_period_days' => ['required', 'integer', 'in:15,30'],
        ]);

        $resignation = Resignation::create([
            ...$data,
            'employee_id' => Auth::id(),
            'submitted_at' => now(),
            'status' => 'Pending',
        ]);

        ResignationHistory::create([
            'resignation_id' => $resignation->id,
            'employee_id' => Auth::id(),
            'status' => 'Pending',
            'notice_period_days' => $data['notice_period_days'],
            'changed_by' => Auth::id(),
            'changed_at' => now(),
            'comment' => 'Resignation submitted',
        ]);

        $service->seedForResignation((int) Auth::id());

        return back()->with('success', 'Resignation submitted.');
    }

    public function destroy(Resignation $resignation, NoticePeriodService $service): RedirectResponse
    {
        abort_unless((int) $resignation->employee_id === (int) Auth::id(), 403);
        abort_unless($resignation->status === 'Pending', 422);

        $employeeId = (int) $resignation->employee_id;
        $resignation->delete();
        $service->wipeEmployeeSteps($employeeId);

        return back()->with('success', 'Resignation withdrawn.');
    }

    public function adminIndex(Request $request): View
    {
        $q = trim((string) $request->get('q', ''));
        $status = $request->get('status') ?: null;

        if ($status && ! in_array($status, ['Pending', 'Approved', 'Declined'], true)) {
            $status = null;
        }

        $resignations = Resignation::query()
            ->with(['employee:id,fname,lname', 'approver:id,fname,lname'])
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($inner) use ($q) {
                    $inner->where('resignation_reason', 'like', "%{$q}%")
                        ->orWhereHas('employee', function ($e) use ($q) {
                            $e->where('fname', 'like', "%{$q}%")
                                ->orWhere('lname', 'like', "%{$q}%")
                                ->orWhereRaw("CONCAT(fname, ' ', lname) like ?", ["%{$q}%"]);
                        });
                });
            })
            ->when($status, fn ($query) => $query->where('status', $status))
            ->latest('id')
            ->paginate(30)
            ->withQueryString();

        $stats = [
            'all' => Resignation::query()->count(),
            'pending' => Resignation::query()->where('status', 'Pending')->count(),
            'approved' => Resignation::query()->where('status', 'Approved')->count(),
            'declined' => Resignation::query()->where('status', 'Declined')->count(),
        ];

        return view('resignations.admin', compact('resignations', 'status', 'q', 'stats'));
    }

    public function updateStatus(Request $request, Resignation $resignation, NoticePeriodService $service): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:Pending,Approved,Declined'],
            'notice_period_days' => ['nullable', 'integer', 'in:15,30'],
            'decline_reason' => ['nullable', 'string'],
        ]);

        if ($data['status'] === 'Declined' && empty($data['decline_reason'])) {
            return back()->withErrors(['decline_reason' => 'Decline reason is required.']);
        }

        $days = $data['notice_period_days'] ?? $resignation->notice_period_days;

        $resignation->update([
            'status' => $data['status'],
            'notice_period_days' => $days,
            'decline_reason' => $data['decline_reason'] ?? null,
            'approved_by' => Auth::id(),
            'approved_at' => $data['status'] === 'Pending' ? null : now(),
            'updated_at' => now(),
        ]);

        ResignationHistory::create([
            'resignation_id' => $resignation->id,
            'employee_id' => $resignation->employee_id,
            'status' => $data['status'],
            'notice_period_days' => $days,
            'changed_by' => Auth::id(),
            'changed_at' => now(),
            'comment' => $data['status'] === 'Declined'
                ? 'Status changed to Declined: '.($data['decline_reason'] ?? '')
                : 'Status changed to '.$data['status'],
        ]);

        if ($data['status'] === 'Declined') {
            $service->wipeEmployeeSteps((int) $resignation->employee_id);
        } else {
            $service->syncEmployeeSteps((int) $resignation->employee_id);
        }

        return back()->with('success', 'Resignation updated.');
    }
}
