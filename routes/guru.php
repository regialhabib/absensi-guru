<?php

use App\Http\Controllers\Guru\AttendanceController;
use App\Http\Controllers\Guru\AttendancePhotoController;
use App\Http\Controllers\Guru\DashboardController;
use App\Http\Controllers\Guru\HistoryController;
use App\Http\Controllers\Guru\LeaveRequestController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'active'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::middleware('guru')->group(function () {
        Route::get('absen', [AttendanceController::class, 'show'])->name('attendance.show');
        Route::post('absen/masuk', [AttendanceController::class, 'checkIn'])
            ->middleware('throttle:10,1')
            ->name('attendance.check-in');
        Route::post('absen/pulang', [AttendanceController::class, 'checkOut'])
            ->middleware('throttle:10,1')
            ->name('attendance.check-out');

        Route::get('riwayat', [HistoryController::class, 'index'])->name('attendance.history');

        Route::get('izin', [LeaveRequestController::class, 'index'])->name('leaves.index');
        Route::get('izin/buat', [LeaveRequestController::class, 'create'])->name('leaves.create');
        Route::post('izin', [LeaveRequestController::class, 'store'])->name('leaves.store');
        Route::delete('izin/{leaveRequest}', [LeaveRequestController::class, 'destroy'])->name('leaves.destroy');
    });

    // Evidence files stay reachable by admins; their policies decide access.
    Route::get('absen/{attendance}/foto/{type}', [AttendancePhotoController::class, 'show'])
        ->whereIn('type', ['masuk', 'pulang'])
        ->name('attendance.photo');
    Route::get('izin/{leaveRequest}/lampiran', [LeaveRequestController::class, 'attachment'])->name('leaves.attachment');
});
