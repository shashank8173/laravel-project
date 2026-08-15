<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function show(): View
    {
        $user = Auth::user()->load(['department', 'designation']);

        return view('profile.show', compact('user'));
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'fname' => ['required', 'string', 'max:208'],
            'lname' => ['nullable', 'string', 'max:58'],
            'mobile1' => ['nullable', 'string', 'max:58'],
            'mobile2' => ['nullable', 'string', 'max:58'],
            'office_email' => ['nullable', 'email', 'max:252'],
            'current_address' => ['nullable', 'string', 'max:255'],
            'permanent_address' => ['nullable', 'string', 'max:255'],
            'dob' => ['nullable', 'date'],
            'nationality' => ['nullable', 'string', 'max:252'],
            'religion' => ['nullable', 'string', 'max:252'],
            'bgroup' => ['nullable', 'string', 'max:252'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:4096'],
        ]);

        $user = Auth::user();
        unset($data['image']);

        $user->update($data);

        if ($request->hasFile('image')) {
            $user->storeProfileImage($request->file('image'));
        }

        return back()->with('success', 'Profile updated.');
    }

    public function updatePhoto(Request $request): RedirectResponse
    {
        $request->validate([
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:4096'],
        ]);

        Auth::user()->storeProfileImage($request->file('image'));

        return back()->with('success', 'Profile photo updated.');
    }

    public function editPassword(): View
    {
        return view('profile.password');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'old_password' => ['required', 'string'],
            'new_password' => ['required', 'string', 'min:4', 'confirmed'],
        ]);

        $user = Auth::user();
        $provider = Auth::getProvider();

        if (! $provider->validateCredentials($user, ['password' => $data['old_password']])) {
            return back()->withErrors(['old_password' => 'Old password is incorrect.']);
        }

        // Keep legacy plaintext storage for compatibility with old app.
        $user->update(['password' => $data['new_password']]);

        return back()->with('success', 'Password changed.');
    }
}
