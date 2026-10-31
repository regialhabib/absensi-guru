<?php

namespace Tests\Feature\Services;

use App\Enums\LeaveStatus;
use App\Enums\LeaveType;
use App\Models\LeaveRequest;
use App\Models\User;
use App\Services\LeaveRequestService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class LeaveRequestServiceTest extends TestCase
{
    use RefreshDatabase;

    private LeaveRequestService $service;

    private User $teacher;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new LeaveRequestService;
        $this->teacher = User::factory()->guru()->create();
        $this->admin = User::factory()->admin()->create();
    }

    /**
     * @return array{type: LeaveType, start_date: string, end_date: string, reason: string}
     */
    private function leaveData(string $start, string $end): array
    {
        return [
            'type' => LeaveType::Sakit,
            'start_date' => $start,
            'end_date' => $end,
            'reason' => 'Demam',
        ];
    }

    /**
     * @param  callable(): void  $action
     */
    private function assertRejectedOn(string $field, callable $action): void
    {
        try {
            $action();
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey($field, $exception->errors());

            return;
        }

        $this->fail('Expected a ValidationException.');
    }

    public function test_submit_creates_a_pending_request_and_stores_the_attachment_privately(): void
    {
        Storage::fake('local');
        $file = UploadedFile::fake()->create('surat-dokter.pdf', 100, 'application/pdf');

        $leave = $this->service->submit($this->teacher, $this->leaveData('2026-11-02', '2026-11-03'), $file);

        $this->assertSame($this->teacher->id, $leave->user_id);
        $this->assertSame(LeaveType::Sakit, $leave->type);
        $this->assertSame(LeaveStatus::Pending, $leave->status);
        $this->assertSame('2026-11-02', $leave->start_date->toDateString());
        $this->assertSame('2026-11-03', $leave->end_date->toDateString());
        Storage::disk('local')->assertExists($leave->attachment);
    }

    public function test_submit_works_without_an_attachment(): void
    {
        $leave = $this->service->submit($this->teacher, $this->leaveData('2026-11-02', '2026-11-02'));

        $this->assertNull($leave->attachment);
    }

    #[DataProvider('overlappingRanges')]
    public function test_submit_rejects_dates_overlapping_an_active_request(string $start, string $end): void
    {
        LeaveRequest::factory()->for($this->teacher)->forDates('2026-11-10', '2026-11-12')->create();

        $this->assertRejectedOn('start_date', fn () => $this->service->submit(
            $this->teacher,
            $this->leaveData($start, $end),
        ));

        $this->assertSame(1, LeaveRequest::query()->count());
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function overlappingRanges(): array
    {
        return [
            'ends on the first day' => ['2026-11-08', '2026-11-10'],
            'starts on the last day' => ['2026-11-12', '2026-11-14'],
            'inside' => ['2026-11-11', '2026-11-11'],
            'surrounding' => ['2026-11-09', '2026-11-13'],
        ];
    }

    public function test_submit_rejects_dates_overlapping_an_approved_request(): void
    {
        LeaveRequest::factory()->for($this->teacher)->approved()->forDates('2026-11-10', '2026-11-12')->create();

        $this->assertRejectedOn('start_date', fn () => $this->service->submit(
            $this->teacher,
            $this->leaveData('2026-11-11', '2026-11-11'),
        ));
    }

    public function test_submit_allows_dates_adjacent_to_an_active_request(): void
    {
        LeaveRequest::factory()->for($this->teacher)->forDates('2026-11-10', '2026-11-12')->create();

        $this->service->submit($this->teacher, $this->leaveData('2026-11-13', '2026-11-14'));

        $this->assertSame(2, LeaveRequest::query()->count());
    }

    public function test_submit_allows_dates_overlapping_a_rejected_request(): void
    {
        LeaveRequest::factory()->for($this->teacher)->rejected()->forDates('2026-11-10', '2026-11-12')->create();

        $this->service->submit($this->teacher, $this->leaveData('2026-11-11', '2026-11-11'));

        $this->assertSame(2, LeaveRequest::query()->count());
    }

    public function test_submit_allows_dates_overlapping_another_teachers_request(): void
    {
        LeaveRequest::factory()->forDates('2026-11-10', '2026-11-12')->create();

        $this->service->submit($this->teacher, $this->leaveData('2026-11-11', '2026-11-11'));

        $this->assertSame(2, LeaveRequest::query()->count());
    }

    public function test_cancel_removes_a_pending_request_and_its_attachment(): void
    {
        Storage::fake('local');
        $leave = $this->service->submit(
            $this->teacher,
            $this->leaveData('2026-11-02', '2026-11-02'),
            UploadedFile::fake()->create('surat.pdf', 100, 'application/pdf'),
        );

        $this->service->cancel($leave);

        $this->assertModelMissing($leave);
        Storage::disk('local')->assertMissing($leave->attachment);
    }

    public function test_cancel_rejects_a_reviewed_request_and_keeps_it(): void
    {
        $leave = LeaveRequest::factory()->for($this->teacher)->approved()->create();

        $this->assertRejectedOn('status', fn () => $this->service->cancel($leave));

        $this->assertModelExists($leave);
    }

    public function test_approve_records_the_reviewer_and_note(): void
    {
        $this->freezeSecond();
        $leave = LeaveRequest::factory()->for($this->teacher)->create();

        $this->service->approve($leave, $this->admin, 'Semoga lekas sembuh');

        $this->assertSame(LeaveStatus::Approved, $leave->status);
        $this->assertSame($this->admin->id, $leave->reviewed_by);
        $this->assertSame('Semoga lekas sembuh', $leave->review_note);
        $this->assertTrue(now()->equalTo($leave->reviewed_at));
    }

    public function test_reject_records_the_reviewer_and_note(): void
    {
        $leave = LeaveRequest::factory()->for($this->teacher)->create();

        $this->service->reject($leave, $this->admin, 'Bukti kurang');

        $this->assertSame(LeaveStatus::Rejected, $leave->status);
        $this->assertSame($this->admin->id, $leave->reviewed_by);
        $this->assertSame('Bukti kurang', $leave->review_note);
    }

    public function test_reviewing_a_processed_request_is_rejected_and_keeps_the_first_decision(): void
    {
        $leave = LeaveRequest::factory()->for($this->teacher)->approved()->create();

        $this->assertRejectedOn('status', fn () => $this->service->reject($leave, $this->admin));

        $this->assertSame(LeaveStatus::Approved, $leave->refresh()->status);
    }
}
