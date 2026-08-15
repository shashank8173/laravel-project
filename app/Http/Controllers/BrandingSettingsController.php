<?php

namespace App\Http\Controllers;

use App\Models\BrandingSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class BrandingSettingsController extends Controller
{
    public function index(): View
    {
        $branding = BrandingSetting::current();

        return view('developer.branding', compact('branding'));
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'logo_url' => ['nullable', 'string', 'max:500'],
            'icon_url' => ['nullable', 'string', 'max:500'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif,svg', 'max:4096'],
            'icon' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif,svg,ico', 'max:2048'],
            'clear_logo_file' => ['nullable', 'boolean'],
            'clear_icon_file' => ['nullable', 'boolean'],
        ]);

        $branding = BrandingSetting::current();

        $branding->logo_url = filled($data['logo_url'] ?? null) ? trim($data['logo_url']) : null;
        $branding->icon_url = filled($data['icon_url'] ?? null) ? trim($data['icon_url']) : null;

        if ($request->boolean('clear_logo_file') && $branding->logo_path) {
            $this->deleteStored($branding->logo_path);
            $branding->logo_path = null;
        }

        if ($request->boolean('clear_icon_file') && $branding->icon_path) {
            $this->deleteStored($branding->icon_path);
            $branding->icon_path = null;
        }

        if ($request->hasFile('logo')) {
            if ($branding->logo_path) {
                $this->deleteStored($branding->logo_path);
            }
            $branding->logo_path = $request->file('logo')->store('branding', 'public');
        }

        if ($request->hasFile('icon')) {
            if ($branding->icon_path) {
                $this->deleteStored($branding->icon_path);
            }
            $branding->icon_path = $request->file('icon')->store('branding', 'public');
        }

        $branding->save();
        BrandingSetting::forgetCache();

        return back()->with('success', 'Branding updated. Logo and icon will appear across the app.');
    }

    private function deleteStored(?string $path): void
    {
        $path = ltrim(str_replace('\\', '/', (string) $path), '/');
        if ($path !== '' && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
