<?php

namespace Tests\Feature\Admin;

use App\Exports\AttendanceReportExport;
use App\Models\Attendance;
use App\Models\SchoolSetting;
use App\Models\User;
use App\Models\WorkSchedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(now()->setDate(2026, 3, 20)->setTime(10, 0));

        $this->admin = User::factory()->admin()->create();
        SchoolSetting::current()->update(['name' => 'Pondok Pesantren Riyadhul Amien']);

        foreach (range(0, 6) as $day) {
            WorkSchedule::query()->create(['day_of_week' => $day, 'is_workday' => $day !== 0, 'starts_at' => '07:00', 'ends_at' => '14:00']);
        }
    }

    public function test_guest_and_guru_are_refused(): void
    {
        $this->get(route('admin.reports.index'))->assertRedirect(route('login'));
        $this->get(route('admin.reports.excel'))->assertRedirect(route('login'));

        $guru = User::factory()->guru()->create();
        $this->actingAs($guru)->get(route('admin.reports.pdf'))->assertForbidden();
    }

    public function test_recap_counts_per_active_teacher(): void
    {
        $teacher = User::factory()->guru()->create(['name' => 'Aminah', 'created_at' => '2026-01-01']);
        User::factory()->guru()->inactive()->create(['created_at' => '2026-01-01']);

        Attendance::factory()->for($teacher)->onDate('2026-03-02')->create();
        Attendance::factory()->for($teacher)->onDate('2026-03-03')->late()->create();

        $this->actingAs($this->admin)
            ->get(route('admin.reports.index', ['year' => 2026, 'month' => 3]))
            ->assertInertia(fn ($page) => $page
                ->component('admin/reports/index')
                ->where('recap.institution', 'Pondok Pesantren Riyadhul Amien')
                ->where('recap.period', 'Maret 2026')
                ->has('recap.rows', 1)
                ->where('recap.rows.0.name', 'Aminah')
                ->where('recap.rows.0.hadir', 1)
                ->where('recap.rows.0.terlambat', 1)
                ->where('recap.rows.0.alpa', 14));
    }

    public function test_year_and_month_are_validated(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.reports.index', ['year' => 1999, 'month' => 13]))
            ->assertSessionHasErrors(['year', 'month']);

        $this->actingAs($this->admin)
            ->get(route('admin.reports.excel', ['month' => 0]))
            ->assertSessionHasErrors('month');
    }

    public function test_excel_export_is_downloaded_with_period_filename(): void
    {
        Excel::fake();

        $this->actingAs($this->admin)
            ->get(route('admin.reports.excel', ['year' => 2026, 'month' => 3]))
            ->assertOk();

        Excel::assertDownloaded('rekap-absensi-2026-03.xlsx', function (AttendanceReportExport $export): bool {
            return $export->headings()[0] === ['Pondok Pesantren Riyadhul Amien']
                && $export->headings()[1] === ['Rekap Absensi Guru - Maret 2026'];
        });
    }

    public function test_pdf_report_returns_a_pdf_download(): void
    {
        User::factory()->guru()->create();

        $response = $this->actingAs($this->admin)
            ->get(route('admin.reports.pdf', ['year' => 2026, 'month' => 3]))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $this->assertStringContainsString('rekap-absensi-2026-03.pdf', $response->headers->get('content-disposition'));
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }
}
