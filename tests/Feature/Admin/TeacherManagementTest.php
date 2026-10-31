<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Models\Attendance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TeacherManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
    }

    /**
     * @return array<string, string>
     */
    private function validPayload(array $overrides = []): array
    {
        return [
            'name' => 'Ustadz Ahmad',
            'email' => 'ahmad@example.com',
            'nip' => '1234567890',
            'phone' => '081234567890',
            'password' => 'password-kuat-123',
            'password_confirmation' => 'password-kuat-123',
            ...$overrides,
        ];
    }

    public function test_guest_is_redirected_and_guru_is_forbidden(): void
    {
        $this->get(route('admin.teachers.index'))->assertRedirect(route('login'));

        $this->actingAs(User::factory()->guru()->create())
            ->get(route('admin.teachers.index'))
            ->assertForbidden();
    }

    public function test_index_lists_only_teachers_and_supports_search(): void
    {
        User::factory()->guru()->create(['name' => 'Budi Santoso', 'nip' => '111']);
        User::factory()->guru()->create(['name' => 'Citra Dewi', 'nip' => '222']);

        $this->actingAs($this->admin)
            ->get(route('admin.teachers.index', ['search' => 'Budi']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('admin/teachers/index')
                ->has('teachers.data', 1)
                ->where('teachers.data.0.name', 'Budi Santoso')
                ->missing('teachers.data.0.password'));

        $this->actingAs($this->admin)
            ->get(route('admin.teachers.index', ['search' => '222']))
            ->assertInertia(fn ($page) => $page->has('teachers.data', 1)->where('teachers.data.0.name', 'Citra Dewi'));
    }

    public function test_store_creates_teacher_and_forces_guru_role(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.teachers.store'), $this->validPayload(['role' => 'admin']))
            ->assertRedirect(route('admin.teachers.index'));

        $teacher = User::query()->where('email', 'ahmad@example.com')->firstOrFail();

        $this->assertSame(UserRole::Guru, $teacher->role);
        $this->assertTrue($teacher->is_active);
        $this->assertTrue(Hash::check('password-kuat-123', $teacher->password));
    }

    public function test_store_rejects_duplicate_email_and_nip(): void
    {
        User::factory()->guru()->create(['email' => 'ahmad@example.com', 'nip' => '1234567890']);

        $this->actingAs($this->admin)
            ->post(route('admin.teachers.store'), $this->validPayload())
            ->assertSessionHasErrors(['email', 'nip']);
    }

    public function test_update_changes_teacher_data_without_changing_password(): void
    {
        $teacher = User::factory()->guru()->create();
        $oldHash = $teacher->password;

        $this->actingAs($this->admin)
            ->put(route('admin.teachers.update', $teacher), [
                'name' => 'Nama Baru',
                'email' => $teacher->email,
                'nip' => $teacher->nip,
                'phone' => '0800000000',
            ])
            ->assertRedirect(route('admin.teachers.index'));

        $teacher->refresh();
        $this->assertSame('Nama Baru', $teacher->name);
        $this->assertSame($oldHash, $teacher->password);
    }

    public function test_toggle_flips_active_status(): void
    {
        $teacher = User::factory()->guru()->create();

        $this->actingAs($this->admin)->patch(route('admin.teachers.toggle', $teacher))->assertRedirect();
        $this->assertFalse($teacher->refresh()->is_active);

        $this->actingAs($this->admin)->patch(route('admin.teachers.toggle', $teacher))->assertRedirect();
        $this->assertTrue($teacher->refresh()->is_active);
    }

    public function test_reset_password_requires_confirmation_and_updates_hash(): void
    {
        $teacher = User::factory()->guru()->create();

        $this->actingAs($this->admin)
            ->post(route('admin.teachers.reset-password', $teacher), [
                'password' => 'password-baru-123',
                'password_confirmation' => 'beda',
            ])
            ->assertSessionHasErrors('password');

        $this->actingAs($this->admin)
            ->post(route('admin.teachers.reset-password', $teacher), [
                'password' => 'password-baru-123',
                'password_confirmation' => 'password-baru-123',
            ])
            ->assertSessionHasNoErrors();

        $this->assertTrue(Hash::check('password-baru-123', $teacher->refresh()->password));
    }

    public function test_destroy_deletes_teacher_without_attendance(): void
    {
        $teacher = User::factory()->guru()->create();

        $this->actingAs($this->admin)
            ->delete(route('admin.teachers.destroy', $teacher))
            ->assertRedirect(route('admin.teachers.index'));

        $this->assertModelMissing($teacher);
    }

    public function test_destroy_is_blocked_when_teacher_has_attendance(): void
    {
        $teacher = User::factory()->guru()->create();
        Attendance::factory()->for($teacher)->create();

        $this->actingAs($this->admin)
            ->delete(route('admin.teachers.destroy', $teacher))
            ->assertSessionHasErrors('teacher');

        $this->assertModelExists($teacher);
    }

    public function test_admin_accounts_cannot_be_managed_through_teacher_routes(): void
    {
        $otherAdmin = User::factory()->admin()->create();

        $this->actingAs($this->admin)->get(route('admin.teachers.edit', $otherAdmin))->assertNotFound();
        $this->actingAs($this->admin)->patch(route('admin.teachers.toggle', $otherAdmin))->assertNotFound();
        $this->actingAs($this->admin)->delete(route('admin.teachers.destroy', $otherAdmin))->assertNotFound();
        $this->actingAs($this->admin)
            ->post(route('admin.teachers.reset-password', $otherAdmin), [
                'password' => 'password-baru-123',
                'password_confirmation' => 'password-baru-123',
            ])
            ->assertNotFound();

        $this->assertTrue($otherAdmin->refresh()->is_active);
    }
}
