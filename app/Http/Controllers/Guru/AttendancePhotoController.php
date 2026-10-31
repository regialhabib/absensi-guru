<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttendancePhotoController extends Controller
{
    public function show(Attendance $attendance, string $type): StreamedResponse
    {
        Gate::authorize('view', $attendance);

        $path = $type === 'masuk' ? $attendance->check_in_photo : $attendance->check_out_photo;
        $disk = Storage::disk('local');

        abort_if($path === null || ! $disk->exists($path), 404);

        return $disk->response($path, null, ['Cache-Control' => 'private, max-age=3600']);
    }
}
