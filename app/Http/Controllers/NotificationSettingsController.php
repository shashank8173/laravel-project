<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class NotificationSettingsController extends Controller
{
    public function index(): View
    {
        $user = Auth::user();

        return view('settings.notifications', [
            'notifyChatSound' => (bool) ($user->notify_chat_sound ?? true),
            'notifyCallRingtone' => (bool) ($user->notify_call_ringtone ?? true),
            'notifyAppSound' => (bool) ($user->notify_app_sound ?? true),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'notify_chat_sound' => ['nullable', 'boolean'],
            'notify_call_ringtone' => ['nullable', 'boolean'],
            'notify_app_sound' => ['nullable', 'boolean'],
        ]);

        $user = Auth::user();
        $user->notify_chat_sound = $request->boolean('notify_chat_sound');
        $user->notify_call_ringtone = $request->boolean('notify_call_ringtone');
        $user->notify_app_sound = $request->boolean('notify_app_sound');
        $user->save();

        return back()->with('success', 'Notification preferences saved.');
    }
}
