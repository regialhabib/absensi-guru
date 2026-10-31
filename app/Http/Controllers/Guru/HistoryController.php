<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Services\Attendance\DailyStatusResolver;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HistoryController extends Controller
{
    public function index(Request $request, DailyStatusResolver $resolver): Response
    {
        $validated = $request->validate([
            'year' => ['nullable', 'integer', 'between:2000,2100'],
            'month' => ['nullable', 'integer', 'between:1,12'],
        ], [
            'year.integer' => 'Tahun tidak valid.',
            'year.between' => 'Tahun tidak valid.',
            'month.integer' => 'Bulan tidak valid.',
            'month.between' => 'Bulan tidak valid.',
        ]);

        $year = (int) ($validated['year'] ?? today()->year);
        $month = (int) ($validated['month'] ?? today()->month);

        $user = $request->user();

        $attendances = $user->attendances()
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->get()
            ->keyBy(fn (Attendance $attendance): string => $attendance->date->toDateString());

        $days = collect($resolver->resolveMonth($user, $year, $month))
            ->map(function ($status, string $date) use ($attendances): array {
                $attendance = $attendances->get($date);

                return [
                    'date' => $date,
                    'status' => $status->value,
                    'check_in_at' => $attendance?->check_in_at->toIso8601String(),
                    'check_out_at' => $attendance?->check_out_at?->toIso8601String(),
                    'late_minutes' => $attendance === null ? 0 : $attendance->late_minutes,
                    'early_leave_minutes' => $attendance === null ? 0 : $attendance->early_leave_minutes,
                ];
            })
            ->values();

        return Inertia::render('attendance/history', [
            'year' => $year,
            'month' => $month,
            'days' => $days,
            'summary' => $resolver->summarize($user, $year, $month),
        ]);
    }
}
