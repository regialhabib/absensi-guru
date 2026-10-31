<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AttendanceController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'date' => ['nullable', 'date_format:Y-m-d'],
            'user_id' => ['nullable', 'integer'],
        ]);

        $date = $filters['date'] ?? today()->toDateString();
        $userId = $filters['user_id'] ?? null;

        $attendances = Attendance::query()
            ->with('user:id,name,nip')
            ->whereDate('date', $date)
            ->when($userId, fn ($query, $userId) => $query->where('user_id', $userId))
            ->orderBy('check_in_at')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('admin/attendances/index', [
            'attendances' => $attendances,
            'filters' => ['date' => $date, 'user_id' => $userId],
            'teachers' => User::query()
                ->where('role', UserRole::Guru)
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }
}
