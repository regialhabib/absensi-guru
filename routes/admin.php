<?php

use App\Http\Controllers\Admin\AttendanceController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\LeaveRequestController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\TeacherController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'active', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::resource('guru', TeacherController::class)
        ->except('show')
        ->parameters(['guru' => 'teacher'])
        ->names('teachers');
    Route::patch('guru/{teacher}/status', [TeacherController::class, 'toggle'])->name('teachers.toggle');
    Route::post('guru/{teacher}/reset-password', [TeacherController::class, 'resetPassword'])->name('teachers.reset-password');

    Route::get('absensi', [AttendanceController::class, 'index'])->name('attendances.index');

    Route::get('izin', [LeaveRequestController::class, 'index'])->name('leaves.index');
    Route::post('izin/{leaveRequest}/setujui', [LeaveRequestController::class, 'approve'])->name('leaves.approve');
    Route::post('izin/{leaveRequest}/tolak', [LeaveRequestController::class, 'reject'])->name('leaves.reject');

    Route::get('laporan', [ReportController::class, 'index'])->name('reports.index');
    Route::get('laporan/excel', [ReportController::class, 'excel'])->name('reports.excel');
    Route::get('laporan/pdf', [ReportController::class, 'pdf'])->name('reports.pdf');

    Route::get('pengaturan', [SettingController::class, 'edit'])->name('settings.edit');
    Route::put('pengaturan', [SettingController::class, 'update'])->name('settings.update');
});
