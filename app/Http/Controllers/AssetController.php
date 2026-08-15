<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetAssignment;
use App\Models\Employee;
use App\Services\EmployeeNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AssetController extends Controller
{
    public function index(Request $request): View
    {
        $q = trim((string) $request->get('q', ''));

        $assets = Asset::query()
            ->with(['openAssignments.assignee'])
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($inner) use ($q) {
                    $inner->where('asset_name', 'like', "%{$q}%")
                        ->orWhere('asset_id', 'like', "%{$q}%");
                });
            })
            ->orderBy('asset_name')
            ->paginate(20)
            ->withQueryString();

        $total = Asset::query()->count();
        $assignedCount = AssetAssignment::query()->whereNull('return_date')->count();

        return view('assets.index', compact('assets', 'q', 'total', 'assignedCount'));
    }

    public function show(Asset $asset): View
    {
        $asset->load(['openAssignments.assignee']);

        $history = AssetAssignment::query()
            ->with('assignee')
            ->where('asset_id', $asset->id)
            ->latest('id')
            ->paginate(20);

        return view('assets.show', compact('asset', 'history'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'asset_name' => ['required', 'string', 'max:255'],
            'asset_id' => ['required', 'string', 'max:255', 'unique:hrm_assets,asset_id'],
            'quantity' => ['required', 'integer', 'min:0'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:4096'],
        ]);

        $imagePath = '';
        if ($request->hasFile('image')) {
            $imagePath = $this->storeImage($request);
        }

        Asset::create([
            'asset_name' => $data['asset_name'],
            'asset_id' => $data['asset_id'],
            'quantity' => $data['quantity'],
            'image' => $imagePath,
        ]);

        return back()->with('success', 'Asset created.');
    }

    public function update(Request $request, Asset $asset): RedirectResponse
    {
        $data = $request->validate([
            'asset_name' => ['required', 'string', 'max:255'],
            'asset_id' => ['required', 'string', 'max:255', 'unique:hrm_assets,asset_id,'.$asset->id],
            'quantity' => ['required', 'integer', 'min:0'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:4096'],
        ]);

        $payload = [
            'asset_name' => $data['asset_name'],
            'asset_id' => $data['asset_id'],
            'quantity' => $data['quantity'],
        ];

        if ($request->hasFile('image')) {
            $payload['image'] = $this->storeImage($request);
        }

        $asset->update($payload);

        return back()->with('success', 'Asset updated.');
    }

    public function destroy(Asset $asset): RedirectResponse
    {
        $open = $asset->assignments()->whereNull('return_date')->exists();
        if ($open) {
            return back()->withErrors(['asset' => 'Cannot delete asset with open assignment. Return it first.']);
        }

        $asset->delete();

        return back()->with('success', 'Asset deleted.');
    }

    public function assignments(Request $request): View
    {
        $q = trim((string) $request->get('q', ''));
        $status = (string) $request->get('status', 'all');

        $assignments = AssetAssignment::query()
            ->with(['asset', 'assignee'])
            ->when($status === 'open', fn ($q2) => $q2->whereNull('return_date'))
            ->when($status === 'returned', fn ($q2) => $q2->whereNotNull('return_date'))
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($inner) use ($q) {
                    $inner->where('issued_date', 'like', "%{$q}%")
                        ->orWhere('return_date', 'like', "%{$q}%")
                        ->orWhere('action', 'like', "%{$q}%")
                        ->orWhereHas('asset', function ($a) use ($q) {
                            $a->where('asset_name', 'like', "%{$q}%")
                                ->orWhere('asset_id', 'like', "%{$q}%");
                        })
                        ->orWhereHas('assignee', function ($e) use ($q) {
                            $e->where('fname', 'like', "%{$q}%")
                                ->orWhere('lname', 'like', "%{$q}%")
                                ->orWhere('emp_id', 'like', "%{$q}%");
                        });
                });
            })
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        $availableAssets = Asset::query()
            ->whereDoesntHave('assignments', fn ($q2) => $q2->whereNull('return_date'))
            ->orderBy('asset_name')
            ->get();

        $employees = Employee::query()
            ->with(['department:id,name', 'designation:id,name'])
            ->where('status', 1)
            ->where('archive_status', 0)
            ->where('id', '!=', 14)
            ->orderBy('fname')
            ->get(['id', 'fname', 'lname', 'emp_id', 'department_id', 'designation_id']);

        $openCount = AssetAssignment::query()->whereNull('return_date')->count();
        $returnedCount = AssetAssignment::query()->whereNotNull('return_date')->count();

        return view('assets.assignments', compact(
            'assignments',
            'availableAssets',
            'employees',
            'q',
            'status',
            'openCount',
            'returnedCount'
        ));
    }

    public function assign(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'asset_id' => ['required', 'integer', 'exists:hrm_assets,id'],
            'assignee_id' => ['required', 'integer', 'exists:hrm_employee,id'],
            'issued_date' => ['required', 'date'],
        ]);

        $open = AssetAssignment::query()
            ->where('asset_id', $data['asset_id'])
            ->whereNull('return_date')
            ->exists();

        if ($open) {
            return back()->withErrors(['asset_id' => 'Asset already assigned and not returned.']);
        }

        AssetAssignment::create([
            'asset_id' => $data['asset_id'],
            'assignee_id' => $data['assignee_id'],
            'issued_date' => $data['issued_date'],
            'assigned_date' => $data['issued_date'],
            'action' => 'Assigned',
        ]);

        $asset = Asset::query()->find($data['asset_id']);
        $actor = Auth::user();
        app(EmployeeNotificationService::class)->notify(
            (int) $data['assignee_id'],
            'asset_assigned',
            'Asset assigned · '.($asset?->asset_name ?: 'Item'),
            'Assigned by '.($actor?->full_name ?: 'Admin'),
            route('dashboard.employee'),
            $actor
        );

        return back()->with('success', 'Asset assigned.');
    }

    public function returnAsset(Request $request, AssetAssignment $assignment): RedirectResponse
    {
        if ($assignment->return_date) {
            return back()->withErrors(['assignment' => 'Already returned.']);
        }

        $data = $request->validate([
            'return_date' => ['nullable', 'date'],
        ]);

        $assignment->update([
            'return_date' => $data['return_date'] ?? now()->toDateString(),
            'action' => 'Returned',
        ]);

        return back()->with('success', 'Asset marked returned.');
    }

    public function destroyAssignment(AssetAssignment $assignment): RedirectResponse
    {
        $assignment->delete();

        return back()->with('success', 'Assignment deleted.');
    }

    private function storeImage(Request $request): string
    {
        $dir = public_path('assets/upload-image');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $name = uniqid('asset_', true).'.'.$request->file('image')->getClientOriginalExtension();
        $request->file('image')->move($dir, $name);

        return 'assets/upload-image/'.$name;
    }
}
