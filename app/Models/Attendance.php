<?php

namespace App\Models;

use App\Enums\AttendanceStatus;
use Database\Factories\AttendanceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property Carbon $date
 * @property Carbon $check_in_at
 * @property string $check_in_latitude
 * @property string $check_in_longitude
 * @property string $check_in_accuracy
 * @property string $check_in_distance
 * @property string $check_in_photo
 * @property bool $check_in_face_detected
 * @property AttendanceStatus $status
 * @property int $late_minutes
 * @property Carbon|null $check_out_at
 * @property string|null $check_out_latitude
 * @property string|null $check_out_longitude
 * @property string|null $check_out_accuracy
 * @property string|null $check_out_distance
 * @property string|null $check_out_photo
 * @property bool|null $check_out_face_detected
 * @property int $early_leave_minutes
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Attendance extends Model
{
    /** @use HasFactory<AttendanceFactory> */
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'date' => 'date:Y-m-d',
            'check_in_at' => 'datetime',
            'check_out_at' => 'datetime',
            'check_in_latitude' => 'decimal:7',
            'check_in_longitude' => 'decimal:7',
            'check_in_accuracy' => 'decimal:2',
            'check_in_distance' => 'decimal:2',
            'check_out_latitude' => 'decimal:7',
            'check_out_longitude' => 'decimal:7',
            'check_out_accuracy' => 'decimal:2',
            'check_out_distance' => 'decimal:2',
            'check_in_face_detected' => 'boolean',
            'check_out_face_detected' => 'boolean',
            'status' => AttendanceStatus::class,
            'late_minutes' => 'integer',
            'early_leave_minutes' => 'integer',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
