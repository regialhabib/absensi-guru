<?php

namespace Tests\Feature\Attendance;

use App\Models\Attendance;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class AttendancePhotoTest extends AttendanceTestCase
{
    private Attendance $attendance;

    protected function setUp(): void
    {
        parent::setUp();

        $this->checkIn();
        $this->attendance = Attendance::query()->sole();
    }

    private function photoUrl(string $type = 'masuk'): string
    {
        return route('attendance.photo', ['attendance' => $this->attendance, 'type' => $type]);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->app['auth']->forgetGuards();

        $this->get($this->photoUrl())->assertRedirect(route('login'));
    }

    public function test_owner_can_view_the_photo_with_private_cache_header(): void
    {
        $response = $this->actingAs($this->teacher)->get($this->photoUrl())->assertOk();

        $this->assertStringStartsWith('image/', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('private', $response->headers->get('Cache-Control'));
        $this->assertStringNotContainsString('public', $response->headers->get('Cache-Control'));
    }

    public function test_admin_can_view_the_photo(): void
    {
        $this->actingAs(User::factory()->admin()->create())->get($this->photoUrl())->assertOk();
    }

    public function test_other_teacher_cannot_view_the_photo(): void
    {
        $this->actingAs(User::factory()->guru()->create())->get($this->photoUrl())->assertForbidden();
    }

    public function test_missing_check_out_photo_returns_not_found(): void
    {
        $this->actingAs($this->teacher)->get($this->photoUrl('pulang'))->assertNotFound();
    }

    public function test_check_out_photo_is_served_after_check_out(): void
    {
        $this->travelTo(self::WORKDAY.' 14:05:00');
        $this->checkOut(['photo' => UploadedFile::fake()->image('out.jpg')]);

        $this->actingAs($this->teacher)->get($this->photoUrl('pulang'))->assertOk();
    }

    public function test_photo_file_missing_from_disk_returns_not_found(): void
    {
        Storage::disk('local')->delete($this->attendance->check_in_photo);

        $this->actingAs($this->teacher)->get($this->photoUrl())->assertNotFound();
    }

    public function test_unknown_photo_type_returns_not_found(): void
    {
        $this->actingAs($this->teacher)->get($this->photoUrl('lainnya'))->assertNotFound();
    }
}
