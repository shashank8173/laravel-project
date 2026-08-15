<?php

namespace App\Http\Controllers;

use App\Models\AdminLoginLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminLogController extends Controller
{
    public function index(Request $request): View
    {
        $action = $request->get('action') ?: null;
        $start = $request->get('start') ?: null;
        $end = $request->get('end') ?: null;
        $q = trim((string) $request->get('q', ''));

        $base = AdminLoginLog::query()
            ->when($action, fn ($query) => $query->where('action', $action))
            ->when($start, fn ($query) => $query->whereDate('timestamp', '>=', $start))
            ->when($end, fn ($query) => $query->whereDate('timestamp', '<=', $end))
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($inner) use ($q) {
                    $inner->where('email', 'like', "%{$q}%")
                        ->orWhere('admin_id', 'like', "%{$q}%")
                        ->orWhere('ip_address', 'like', "%{$q}%");
                });
            });

        $logs = (clone $base)
            ->orderByDesc('timestamp')
            ->orderByDesc('id')
            ->paginate(40)
            ->withQueryString();

        $counts = [
            'all' => AdminLoginLog::query()->count(),
            'login' => AdminLoginLog::query()->where('action', 'login')->count(),
            'logout' => AdminLoginLog::query()->where('action', 'logout')->count(),
            'filtered' => (clone $base)->count(),
        ];

        return view('logs.admin', compact('logs', 'action', 'start', 'end', 'q', 'counts'));
    }
}
