<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ThemeController extends Controller
{
    public const THEMES = ['light', 'dark', 'dark-blue'];

    public function update(Request $request): JsonResponse|RedirectResponse
    {
        $data = $request->validate([
            'theme' => ['required', 'string', 'in:'.implode(',', self::THEMES)],
        ]);

        $user = Auth::user();
        $user->ui_theme = $data['theme'];
        $user->save();

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'ok' => true,
                'theme' => $data['theme'],
            ]);
        }

        return back()->with('success', 'Theme updated.');
    }
}
