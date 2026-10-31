<?php

namespace Tests\Feature\Admin;

use App\Enums\LeaveStatus;
use App\Models\LeaveRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeaveReviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_and_guru_are_refused(): void
    {
        $leave = LeaveRequest::factory()->create();

        $this->get(route('admin.leaves.index'))->assertRedirect(route('login'));
        $this->post(route('admin.leaves.approve', $leave))->assertRedirect(route('login'));

        $guru = User::factory()->guru()->create();
        $this->actingAs($guru)->get(route('admin.leaves.index'))->assertForbidden();
        $this->actingAs($guru)->post(route('admin.leaves.approve', $leave))->assertForbidden();
        $this->assertSame(LeaveStatus::Pending, $leave->refresh()->status);
    }

    public function test_index_lists_pending_first_and_filters_by_status(): void
    {
        $admin = User::factory()->admin()->create();
        $approved = LeaveRequest::factory()->approved($admin)->create(['created_at' => now()->addMinute()]);
        $pending = LeaveRequest::factory()->create(['created_at' => now()->subDay()]);

        $this->actingAs($admin)
            ->get(route('admin.leaves.index'))
            ->assertInertia(fn ($page) => $page
                ->component('admin/leaves/index')
                ->where('leaveRequests.data.0.id', $pending->id)
                ->where('leaveRequests.data.1.id', $approved->id));

        $this->actingAs($admin)
            ->get(route('admin.leaves.index', ['status' => 'approved']))
            ->assertInertia(fn ($page) => $page->has('leaveRequests.data', 1));

        $this->actingAs($admin)
            ->get(route('admin.leaves.index', ['status' => 'bogus']))
            ->assertSessionHasErrors('status');
    }

    public function test_approve_records_reviewer_and_note(): void
    {
        $admin = User::factory()->admin()->create();
        $leave = LeaveRequest::factory()->create();

        $this->actingAs($admin)
            ->post(route('admin.leaves.approve', $leave), ['note' => 'Disetujui, semoga lekas sembuh.'])
            ->assertRedirect();

        $leave->refresh();
        $this->assertSame(LeaveStatus::Approved, $leave->status);
        $this->assertSame($admin->id, $leave->reviewed_by);
        $this->assertSame('Disetujui, semoga lekas sembuh.', $leave->review_note);
    }

    public function test_reject_records_reviewer(): void
    {
        $admin = User::factory()->admin()->create();
        $leave = LeaveRequest::factory()->create();

        $this->actingAs($admin)->post(route('admin.leaves.reject', $leave))->assertRedirect();

        $leave->refresh();
        $this->assertSame(LeaveStatus::Rejected, $leave->status);
        $this->assertSame($admin->id, $leave->reviewed_by);
    }

    public function test_processing_an_already_processed_request_surfaces_the_error(): void
    {
        $admin = User::factory()->admin()->create();
        $leave = LeaveRequest::factory()->approved($admin)->create();

        $this->actingAs($admin)
            ->post(route('admin.leaves.reject', $leave))
            ->assertSessionHasErrors(['status' => 'Pengajuan ini sudah diproses.']);

        $this->assertSame(LeaveStatus::Approved, $leave->refresh()->status);
    }
}
