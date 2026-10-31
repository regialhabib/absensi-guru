<?php

namespace Tests\Feature\Guru;

use App\Enums\LeaveStatus;
use App\Models\LeaveRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class LeaveRequestTest extends TestCase
{
    use RefreshDatabase;

    private User $teacher;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        $this->travelTo('2026-10-15 10:00:00');

        $this->teacher = User::factory()->guru()->create();
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return [
            'type' => 'sakit',
            'start_date' => '2026-10-16',
            'end_date' => '2026-10-17',
            'reason' => 'Demam tinggi.',
            ...$overrides,
        ];
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $leave = LeaveRequest::factory()->create();

        $this->get(route('leaves.index'))->assertRedirect(route('login'));
        $this->get(route('leaves.create'))->assertRedirect(route('login'));
        $this->post(route('leaves.store'), $this->payload())->assertRedirect(route('login'));
        $this->delete(route('leaves.destroy', $leave))->assertRedirect(route('login'));
        $this->get(route('leaves.attachment', $leave))->assertRedirect(route('login'));
    }

    public function test_index_lists_only_own_requests_newest_first(): void
    {
        $older = LeaveRequest::factory()->for($this->teacher)->create(['created_at' => '2026-10-10 08:00:00']);
        $newer = LeaveRequest::factory()->for($this->teacher)->create(['created_at' => '2026-10-12 08:00:00']);
        LeaveRequest::factory()->create();

        $this->actingAs($this->teacher)
            ->get(route('leaves.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('leaves/index')
                ->has('leaves.data', 2)
                ->where('leaves.data.0.id', $newer->id)
                ->where('leaves.data.1.id', $older->id));
    }

    public function test_create_page_lists_leave_types(): void
    {
        $this->actingAs($this->teacher)
            ->get(route('leaves.create'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('leaves/create')
                ->has('types', 3));
    }

    public function test_teacher_can_submit_a_leave_request_with_attachment(): void
    {
        $file = UploadedFile::fake()->create('surat.pdf', 100, 'application/pdf');

        $this->actingAs($this->teacher)
            ->post(route('leaves.store'), $this->payload(['attachment' => $file]))
            ->assertRedirect(route('leaves.index'));

        $leave = $this->teacher->leaveRequests()->sole();
        $this->assertSame(LeaveStatus::Pending, $leave->status);
        $this->assertSame('Demam tinggi.', $leave->reason);
        Storage::disk('local')->assertExists($leave->attachment);
    }

    public function test_attachment_is_optional(): void
    {
        $this->actingAs($this->teacher)
            ->post(route('leaves.store'), $this->payload())
            ->assertRedirect(route('leaves.index'));

        $this->assertNull($this->teacher->leaveRequests()->sole()->attachment);
    }

    /**
     * @return array<string, array{array<string, mixed>, string}>
     */
    public static function invalidInputs(): array
    {
        return [
            'missing type' => [['type' => ''], 'type'],
            'unknown type' => [['type' => 'liburan'], 'type'],
            'start in the past' => [['start_date' => '2026-10-14', 'end_date' => '2026-10-16'], 'start_date'],
            'end before start' => [['start_date' => '2026-10-18', 'end_date' => '2026-10-17'], 'end_date'],
            'missing reason' => [['reason' => ''], 'reason'],
            'reason too long' => [['reason' => str_repeat('a', 1001)], 'reason'],
            'wrong file type' => [['attachment' => UploadedFile::fake()->create('a.exe', 10, 'application/x-msdownload')], 'attachment'],
            'file too large' => [['attachment' => UploadedFile::fake()->create('a.pdf', 5121, 'application/pdf')], 'attachment'],
        ];
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    #[DataProvider('invalidInputs')]
    public function test_store_validates_input(array $overrides, string $field): void
    {
        $this->actingAs($this->teacher)
            ->post(route('leaves.store'), $this->payload($overrides))
            ->assertSessionHasErrors($field);

        $this->assertDatabaseCount('leave_requests', 0);
    }

    public function test_overlapping_request_surfaces_an_error_on_the_form(): void
    {
        LeaveRequest::factory()->for($this->teacher)->forDates('2026-10-17', '2026-10-19')->create();

        $this->actingAs($this->teacher)
            ->post(route('leaves.store'), $this->payload())
            ->assertSessionHasErrors('start_date');

        $this->assertDatabaseCount('leave_requests', 1);
    }

    public function test_owner_can_cancel_a_pending_request(): void
    {
        $leave = LeaveRequest::factory()->for($this->teacher)->create();

        $this->actingAs($this->teacher)
            ->delete(route('leaves.destroy', $leave))
            ->assertRedirect(route('leaves.index'));

        $this->assertModelMissing($leave);
    }

    public function test_another_teacher_cannot_cancel_the_request(): void
    {
        $leave = LeaveRequest::factory()->for($this->teacher)->create();

        $this->actingAs(User::factory()->guru()->create())
            ->delete(route('leaves.destroy', $leave))
            ->assertForbidden();

        $this->assertModelExists($leave);
    }

    public function test_processed_request_cannot_be_cancelled(): void
    {
        $leave = LeaveRequest::factory()->for($this->teacher)->approved()->create();

        $this->actingAs($this->teacher)
            ->delete(route('leaves.destroy', $leave))
            ->assertSessionHasErrors('status');

        $this->assertModelExists($leave);
    }

    public function test_owner_and_admin_can_view_the_attachment(): void
    {
        Storage::disk('local')->put('leave-attachments/surat.pdf', 'isi');
        $leave = LeaveRequest::factory()->for($this->teacher)->create(['attachment' => 'leave-attachments/surat.pdf']);

        $this->actingAs($this->teacher)
            ->get(route('leaves.attachment', $leave))
            ->assertOk()
            ->assertHeader('Cache-Control', 'max-age=3600, private');

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('leaves.attachment', $leave))
            ->assertOk();
    }

    public function test_another_teacher_cannot_view_the_attachment(): void
    {
        Storage::disk('local')->put('leave-attachments/surat.pdf', 'isi');
        $leave = LeaveRequest::factory()->for($this->teacher)->create(['attachment' => 'leave-attachments/surat.pdf']);

        $this->actingAs(User::factory()->guru()->create())
            ->get(route('leaves.attachment', $leave))
            ->assertForbidden();
    }

    public function test_missing_attachment_returns_not_found(): void
    {
        $withoutFile = LeaveRequest::factory()->for($this->teacher)->create();
        $missingOnDisk = LeaveRequest::factory()->for($this->teacher)->create(['attachment' => 'leave-attachments/gone.pdf']);

        $this->actingAs($this->teacher);

        $this->get(route('leaves.attachment', $withoutFile))->assertNotFound();
        $this->get(route('leaves.attachment', $missingOnDisk))->assertNotFound();
    }
}
