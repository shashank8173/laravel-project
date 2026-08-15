<?php

namespace App\Http\Controllers;

use App\Models\ApiToken;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ApiTokenController extends Controller
{
    public function index(): View
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);

        $tokens = ApiToken::query()
            ->with('creator:id,fname,lname')
            ->latest('id')
            ->get();

        $baseUrl = rtrim((string) config('app.url'), '/').'/api/v1';

        return view('developer.api-tokens', compact('tokens', 'baseUrl'));
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'abilities' => ['nullable', 'array'],
            'abilities.*' => ['in:*,employees:read,employees:write'],
            'expires_days' => ['nullable', 'integer', 'min:1', 'max:3650'],
        ]);

        $abilities = $data['abilities'] ?? ['*'];
        if (in_array('*', $abilities, true)) {
            $abilities = ['*'];
        }

        $expiresAt = ! empty($data['expires_days'])
            ? now()->addDays((int) $data['expires_days'])
            : null;

        $issued = ApiToken::issue(
            trim($data['name']),
            (int) auth()->id(),
            $abilities,
            $expiresAt
        );

        return redirect()
            ->route('developer.api-tokens')
            ->with('success', 'API token created. Copy it now — it will not be shown again.')
            ->with('plain_token', $issued['plain_text']);
    }

    public function revoke(ApiToken $apiToken): RedirectResponse
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);

        if (! $apiToken->revoked_at) {
            $apiToken->update(['revoked_at' => now()]);
        }

        return back()->with('success', 'API token revoked.');
    }

    public function destroy(ApiToken $apiToken): RedirectResponse
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);

        $apiToken->delete();

        return back()->with('success', 'API token deleted.');
    }
}
