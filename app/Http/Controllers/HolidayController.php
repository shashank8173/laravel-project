<?php

namespace App\Http\Controllers;

use App\Models\Holiday;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HolidayController extends Controller
{
    public function index(Request $request): View
    {
        $year = $request->integer('year') ?: (int) now()->year;
        $q = trim((string) $request->get('q', ''));

        $years = Holiday::query()
            ->select('year')
            ->distinct()
            ->orderByDesc('year')
            ->pluck('year')
            ->map(fn ($y) => (int) $y)
            ->filter()
            ->values();

        if ($years->isEmpty()) {
            $years = collect([(int) now()->year]);
        } elseif (! $years->contains($year)) {
            $years = $years->prepend($year)->unique()->values();
        }

        $holidays = Holiday::query()
            ->when($year, fn ($query) => $query->where('year', $year))
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($inner) use ($q) {
                    $inner->where('name', 'like', "%{$q}%")
                        ->orWhere('date', 'like', "%{$q}%");
                });
            })
            ->orderBy('date')
            ->paginate(40)
            ->withQueryString();

        $yearHolidays = Holiday::query()->where('year', $year)->get();
        $stats = [
            'year' => $year,
            'count' => $yearHolidays->count(),
            'days' => (int) $yearHolidays->sum(fn ($h) => (int) ($h->no_of_days ?: 1)),
            'upcoming' => $yearHolidays->filter(function ($h) {
                $date = $this->parseHolidayDate($h->date);

                return $date && $date->isFuture();
            })->count(),
            'next' => $yearHolidays
                ->map(function ($h) {
                    $date = $this->parseHolidayDate($h->date);

                    return $date && $date->isFuture() ? ['name' => $h->name, 'date' => $date] : null;
                })
                ->filter()
                ->sortBy('date')
                ->first(),
        ];

        $isAdmin = (bool) auth()->user()?->isAdmin();

        return view('holidays.index', compact('holidays', 'year', 'years', 'q', 'stats', 'isAdmin'));
    }

    private function parseHolidayDate(?string $value): ?\Carbon\Carbon
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        try {
            return \Carbon\Carbon::parse($value)->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }
}
