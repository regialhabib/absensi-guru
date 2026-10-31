<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ResetTeacherPasswordRequest;
use App\Http\Requests\Admin\TeacherRequest;
use App\Models\User;
use App\Services\TeacherService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TeacherController extends Controller
{
    public function __construct(private TeacherService $teachers) {}

    public function index(Request $request): Response
    {
        $search = $request->string('search')->trim()->toString();

        return Inertia::render('admin/teachers/index', [
            'teachers' => $this->teachers->paginate($search),
            'filters' => ['search' => $search],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/teachers/create');
    }

    public function store(TeacherRequest $request): RedirectResponse
    {
        $this->teachers->create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Guru berhasil ditambahkan.']);

        return to_route('admin.teachers.index');
    }

    public function edit(User $teacher): Response
    {
        $this->ensureTeacher($teacher);

        return Inertia::render('admin/teachers/edit', [
            'teacher' => $teacher->only(['id', 'name', 'email', 'nip', 'phone', 'is_active']),
        ]);
    }

    public function update(TeacherRequest $request, User $teacher): RedirectResponse
    {
        $this->ensureTeacher($teacher);

        $this->teachers->update($teacher, $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Data guru berhasil diperbarui.']);

        return to_route('admin.teachers.index');
    }

    public function destroy(User $teacher): RedirectResponse
    {
        $this->ensureTeacher($teacher);

        $this->teachers->delete($teacher);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Guru berhasil dihapus.']);

        return to_route('admin.teachers.index');
    }

    public function toggle(User $teacher): RedirectResponse
    {
        $this->ensureTeacher($teacher);

        $teacher = $this->teachers->toggleActive($teacher);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $teacher->is_active ? 'Akun guru diaktifkan.' : 'Akun guru dinonaktifkan.',
        ]);

        return back();
    }

    public function resetPassword(ResetTeacherPasswordRequest $request, User $teacher): RedirectResponse
    {
        $this->ensureTeacher($teacher);

        $this->teachers->resetPassword($teacher, $request->validated('password'));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Kata sandi guru berhasil direset.']);

        return back();
    }

    /**
     * Only teacher accounts are manageable here; admins respond with 404.
     */
    private function ensureTeacher(User $teacher): void
    {
        abort_if($teacher->isAdmin(), 404);
    }
}
