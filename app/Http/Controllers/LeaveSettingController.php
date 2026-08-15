<?php

namespace App\Http\Controllers;

use App\Models\LeaveType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LeaveSettingController extends Controller
{
    public function index(): View
    {
        $types = LeaveType::query()
            ->withCount('leaves')
            ->orderBy('id')
            ->get();

        $stats = [
            'types' => $types->count(),
            'days' => (int) $types->sum('number_of_leave'),
            'used' => (int) $types->sum('leaves_count'),
        ];

        return view('leaves.settings', compact('types', 'stats'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:252'],
            'number_of_leave' => ['required', 'integer', 'min:0'],
        ]);

        LeaveType::create($data);

        return back()->with('success', 'Leave type added.');
    }

    public function update(Request $request, LeaveType $leaveType): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:252'],
            'number_of_leave' => ['required', 'integer', 'min:0'],
        ]);

        $leaveType->update($data);

        return back()->with('success', 'Leave type updated.');
    }

    public function destroy(LeaveType $leaveType): RedirectResponse
    {
        if ($leaveType->leaves()->exists()) {
            return back()->withErrors(['leaveType' => 'Cannot delete a leave type that has applications.']);
        }

        $leaveType->delete();

        return back()->with('success', 'Leave type deleted.');
    }
}
