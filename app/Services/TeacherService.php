<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

class TeacherService
{
    /**
     * Paginated teachers, optionally filtered by name or NIP.
     *
     * @return LengthAwarePaginator<int, User>
     */
    public function paginate(?string $search = null, int $perPage = 15): LengthAwarePaginator
    {
        return User::query()
            ->where('role', UserRole::Guru)
            ->when($search, function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('nip', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->select(['id', 'name', 'email', 'role', 'nip', 'phone', 'is_active', 'created_at', 'updated_at'])
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * @param  array<string, mixed>  $data  validated name, email, nip, phone, and password
     */
    public function create(array $data): User
    {
        $teacher = new User($data);
        $teacher->role = UserRole::Guru;
        $teacher->is_active = true;
        $teacher->save();

        return $teacher;
    }

    /**
     * @param  array<string, mixed>  $data  validated name, email, nip, and phone
     */
    public function update(User $teacher, array $data): User
    {
        $teacher->update($data);

        return $teacher;
    }

    public function toggleActive(User $teacher): User
    {
        $teacher->update(['is_active' => ! $teacher->is_active]);

        return $teacher;
    }

    public function resetPassword(User $teacher, string $password): void
    {
        $teacher->update(['password' => $password]);
    }

    /**
     * @throws ValidationException when the teacher already has attendance records
     */
    public function delete(User $teacher): void
    {
        if ($teacher->attendances()->exists()) {
            throw ValidationException::withMessages([
                'teacher' => 'Guru ini sudah memiliki data absensi dan tidak dapat dihapus. Nonaktifkan akun guru sebagai gantinya.',
            ]);
        }

        $teacher->delete();
    }
}
