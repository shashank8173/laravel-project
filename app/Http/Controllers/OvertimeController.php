<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\OfficeTiming;
use App\Models\UserAttendance;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OvertimeController extends Controller
{
    public function index(Request $request): View
    {
        $month = (int) $request->get('month', now()->month);
        $year = (int) $request->get('year', now()->year);
        $timing = OfficeTiming::query()->first();
        $logout = $timing?->logout_time ? Carbon::parse($timing->logout_time) : Carbon::createFromTime(18, 0);

        $records = UserAttendance::query()
            ->with('employee:id,fname,lname')
            ->whereMonth('clock_in_time', $month)
            ->whereYear('clock_in_time', $year)
            ->whereNotNull('clock_out_time')
            ->orderByDesc('clock_out_time')
            ->get()
            ->map(function ($row) use ($logout) {
                $out = Carbon::parse($row->clock_out_time);
                $expectedOut = $out->copy()->setTimeFromTimeString($logout->format('H:i:s'));
                $extraMins = $out->gt($expectedOut) ? $expectedOut->diffInMinutes($out) : 0;

                return [
                    'employee' => $row->employee?->full_name ?? ('#'.$row->user_id),
                    'date' => optional($row->clock_in_time)->format('d M Y'),
                    'in' => optional($row->clock_in_time)->format('H:i'),
                    'out' => optional($row->clock_out_time)->format('H:i'),
                    'extra_mins' => $extraMins,
                    'extra_label' => sprintf('%dh %02dm', intdiv($extraMins, 60), $extraMins % 60),
                ];
            })
            ->filter(fn ($r) => $r['extra_mins'] > 0)
            ->values();

        $stats = [
            'employees' => $records->pluck('employee')->unique()->count(),
            'hours' => round($records->sum('extra_mins') / 60, 1),
            'entries' => $records->count(),
            'avg_mins' => $records->count() > 0 ? (int) round($records->avg('extra_mins')) : 0,
        ];

        $topEmployees = $records
            ->groupBy('employee')
            ->map(fn ($rows, $name) => [
                'employee' => $name,
                'mins' => (int) $rows->sum('extra_mins'),
                'entries' => $rows->count(),
            ])
            ->sortByDesc('mins')
            ->take(5)
            ->values();

        $officeLogout = $logout->format('h:i A');
        $monthLabel = Carbon::create($year, $month, 1)->format('F Y');
        $q = trim((string) $request->get('q', ''));

        if ($q !== '') {
            $records = $records
                ->filter(fn ($r) => str_contains(strtolower($r['employee']), strtolower($q)))
                ->values();
        }

        return view('overtime.index', compact(
            'records',
            'stats',
            'month',
            'year',
            'topEmployees',
            'officeLogout',
            'monthLabel',
            'q'
        ));
    }
}
