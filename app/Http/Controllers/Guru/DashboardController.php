<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Services\Attendance\DailyStatusResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request, DailyStatusResolver $resolver): Response|RedirectResponse
    {
        $user = $request->user();

        if ($user->isAdmin()) {
            return redirect()->route('admin.dashboard');
        }

        $today = today();
        $attendance = $user->attendances()->whereDate('date', $today)->first();

        return Inertia::render('dashboard', [
            'today' => [
                'date' => $today->toDateString(),
                'status' => $resolver->resolve($user, $today)->value,
                'check_in_at' => $attendance?->check_in_at->toIso8601String(),
                'check_out_at' => $attendance?->check_out_at?->toIso8601String(),
            ],
            'month' => [
                'year' => $today->year,
                'month' => $today->month,
            ],
            'summary' => $resolver->summarize($user, $today->year, $today->month),
        ]);
    }
}
