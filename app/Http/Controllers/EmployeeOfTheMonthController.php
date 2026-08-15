<?php

namespace App\Http\Controllers;

use App\Models\EmployeeOfTheMonth;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmployeeOfTheMonthController extends Controller
{
    public function index(Request $request): View
    {
        $q = trim((string) $request->get('q', ''));
        $isAdmin = (bool) ($request->user()?->isAdmin());

        $entries = EmployeeOfTheMonth::query()
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($inner) use ($q) {
                    $inner->where('name', 'like', "%{$q}%")
                        ->orWhere('designation', 'like', "%{$q}%")
                        ->orWhere('message', 'like', "%{$q}%");
                });
            })
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(12)
            ->withQueryString();

        $latest = EmployeeOfTheMonth::query()
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->first();

        return view('eom.index', compact('entries', 'latest', 'q', 'isAdmin'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeAdmin($request);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'designation' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string'],
            'image' => ['nullable', 'image', 'max:4096'],
        ]);

        EmployeeOfTheMonth::create([
            'name' => $data['name'],
            'designation' => $data['designation'],
            'message' => $data['message'],
            'image_url' => $this->storeImage($request) ?? 'default.png',
            'created_at' => now(),
        ]);

        return back()->with('success', 'Employee of the Month added.');
    }

    public function update(Request $request, EmployeeOfTheMonth $eom): RedirectResponse
    {
        $this->authorizeAdmin($request);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'designation' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string'],
            'image' => ['nullable', 'image', 'max:4096'],
        ]);

        $payload = [
            'name' => $data['name'],
            'designation' => $data['designation'],
            'message' => $data['message'],
        ];

        if ($request->hasFile('image')) {
            $payload['image_url'] = $this->storeImage($request);
        }

        $eom->update($payload);

        return back()->with('success', 'Entry updated.');
    }

    public function destroy(Request $request, EmployeeOfTheMonth $eom): RedirectResponse
    {
        $this->authorizeAdmin($request);
        $eom->delete();

        return back()->with('success', 'Entry deleted.');
    }

    private function authorizeAdmin(Request $request): void
    {
        if (! $request->user()?->isAdmin()) {
            abort(403);
        }
    }

    private function storeImage(Request $request): ?string
    {
        if (! $request->hasFile('image')) {
            return null;
        }

        $dir = public_path('assets/upload-image');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $name = uniqid('eom_', true).'.'.$request->file('image')->getClientOriginalExtension();
        $request->file('image')->move($dir, $name);

        return 'assets/upload-image/'.$name;
    }
}
