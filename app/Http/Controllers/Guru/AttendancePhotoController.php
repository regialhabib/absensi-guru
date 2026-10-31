<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\Attendance;

class AttendancePhotoController extends Controller
{
    public function show(Attendance $attendance, string $type): never
    {
        abort(501);
    }
}
