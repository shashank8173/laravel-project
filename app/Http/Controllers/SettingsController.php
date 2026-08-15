<?php

namespace App\Http\Controllers;

use App\Models\Holiday;
use App\Models\Notification;
use App\Models\OfficeTiming;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function officeTiming(): View
    {
        $timing = OfficeTiming::query()->first() ?? new OfficeTiming;

        return view('settings.office-timing', compact('timing'));
    }

    public function saveOfficeTiming(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'login_time' => ['required'],
            'logout_time' => ['required'],
            'relaxation_time' => ['nullable'],
            'normal_fine' => ['nullable', 'integer', 'min:0'],
            'extra_fine_time' => ['nullable'],
            'extra_fine' => ['nullable', 'integer', 'min:0'],
            'half_day_time' => ['nullable'],
            'evening_half_time' => ['nullable'],
            'saturday_option' => ['required', 'in:all-on,1st-3rd-on,all-off'],
        ]);

        $timing = OfficeTiming::query()->first() ?? new OfficeTiming;
        $timing->fill($data)->save();

        return back()->with('success', 'Office timing saved.');
    }

    public function notifications(): View
    {
        $notifications = Notification::query()
            ->with('sender')
            ->latest('id')
            ->paginate(30);

        return view('settings.notifications', compact('notifications'));
    }

    public function storeNotification(Request $request): RedirectResponse
    {
        $user = $request->user();
        if (! $user || (! $user->isAdmin() && ! $user->isHrDepartment())) {
            abort(403);
        }

        $data = $request->validate([
            'title' => ['required', 'string'],
            'description' => ['required', 'string'],
            'send_to' => ['required', 'string'],
        ]);

        Notification::create([
            ...$data,
            'date' => now()->toDateString(),
            'time' => now()->format('H:i:s'),
            'sent_by' => Auth::id(),
        ]);

        return back()->with('success', 'Notification sent.');
    }

    public function storeHoliday(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:252'],
            'date' => ['required', 'string', 'max:252'],
            'year' => ['required', 'integer'],
            'no_of_days' => ['required', 'string', 'max:252'],
        ]);

        Holiday::create([
            ...$data,
            'added_by' => Auth::id(),
        ]);

        return back()->with('success', 'Holiday added.');
    }

    public function destroyHoliday(Holiday $holiday): RedirectResponse
    {
        $holiday->delete();

        return back()->with('success', 'Holiday deleted.');
    }
}
