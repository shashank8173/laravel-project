<?php

namespace App\Http\Controllers;

use App\Models\GreetingImage;
use App\Models\GreetingSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class GreetingSettingsController extends Controller
{
    public function index(): View
    {
        $settings = GreetingSetting::current();
        $birthdayImages = GreetingImage::query()
            ->where('type', 'birthday')
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->get();
        $anniversaryImages = GreetingImage::query()
            ->where('type', 'anniversary')
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->get();
        $holidayImages = GreetingImage::query()
            ->where('type', 'holiday')
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->get();

        return view('settings.greetings', compact(
            'settings',
            'birthdayImages',
            'anniversaryImages',
            'holidayImages'
        ));
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'birthday_enabled' => ['nullable', 'boolean'],
            'birthday_subject' => ['required', 'string', 'max:200'],
            'birthday_heading' => ['required', 'string', 'max:200'],
            'birthday_message' => ['nullable', 'string', 'max:5000'],
            'birthday_footer' => ['nullable', 'string', 'max:500'],
            'birthday_alert_subject' => ['required', 'string', 'max:200'],
            'birthday_alert_heading' => ['required', 'string', 'max:200'],
            'birthday_alert_message' => ['nullable', 'string', 'max:5000'],
            'anniversary_enabled' => ['nullable', 'boolean'],
            'anniversary_subject' => ['required', 'string', 'max:200'],
            'anniversary_heading' => ['required', 'string', 'max:200'],
            'anniversary_message' => ['nullable', 'string', 'max:5000'],
            'anniversary_footer' => ['nullable', 'string', 'max:500'],
            'anniversary_alert_subject' => ['required', 'string', 'max:200'],
            'anniversary_alert_heading' => ['required', 'string', 'max:200'],
            'anniversary_alert_message' => ['nullable', 'string', 'max:5000'],
            'holiday_enabled' => ['nullable', 'boolean'],
            'holiday_subject' => ['required', 'string', 'max:200'],
            'holiday_heading' => ['required', 'string', 'max:200'],
            'holiday_message' => ['nullable', 'string', 'max:5000'],
            'holiday_footer' => ['nullable', 'string', 'max:500'],
        ]);

        $settings = GreetingSetting::current();
        $settings->fill([
            ...$data,
            'birthday_enabled' => $request->boolean('birthday_enabled'),
            'anniversary_enabled' => $request->boolean('anniversary_enabled'),
            'holiday_enabled' => $request->boolean('holiday_enabled'),
        ]);
        $settings->save();

        return back()->with('success', 'Greeting texts saved.');
    }

    public function storeImage(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'type' => ['required', 'in:birthday,anniversary,holiday'],
            'title' => ['nullable', 'string', 'max:120'],
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:5120'],
        ]);

        $path = $request->file('image')->store('greetings/'.$data['type'], 'public');

        GreetingImage::create([
            'type' => $data['type'],
            'title' => $data['title'] ?: null,
            'image_path' => $path,
            'is_active' => true,
            'sort_order' => (int) GreetingImage::query()->where('type', $data['type'])->max('sort_order') + 1,
        ]);

        return back()->with('success', ucfirst($data['type']).' card image uploaded.');
    }

    public function toggleImage(GreetingImage $greetingImage): RedirectResponse
    {
        $greetingImage->is_active = ! $greetingImage->is_active;
        $greetingImage->save();

        return back()->with('success', 'Image '.($greetingImage->is_active ? 'enabled' : 'disabled').'.');
    }

    public function destroyImage(GreetingImage $greetingImage): RedirectResponse
    {
        if ($greetingImage->image_path && Storage::disk('public')->exists($greetingImage->image_path)) {
            Storage::disk('public')->delete($greetingImage->image_path);
        }
        $greetingImage->delete();

        return back()->with('success', 'Card image deleted.');
    }
}
