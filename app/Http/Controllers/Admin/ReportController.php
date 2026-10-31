<?php

namespace App\Http\Controllers\Admin;

use App\Exports\AttendanceReportExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReportRequest;
use App\Services\AttendanceReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response as HttpResponse;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReportController extends Controller
{
    public function __construct(private AttendanceReportService $reports) {}

    public function index(ReportRequest $request): Response
    {
        return Inertia::render('admin/reports/index', [
            'recap' => $this->reports->monthlyRecap($request->year(), $request->month()),
            'filters' => ['year' => $request->year(), 'month' => $request->month()],
        ]);
    }

    public function excel(ReportRequest $request): BinaryFileResponse
    {
        $recap = $this->reports->monthlyRecap($request->year(), $request->month());

        return Excel::download(
            new AttendanceReportExport($recap),
            $this->reports->fileName($recap['year'], $recap['month'], 'xlsx'),
        );
    }

    public function pdf(ReportRequest $request): HttpResponse
    {
        $recap = $this->reports->monthlyRecap($request->year(), $request->month());

        return Pdf::loadView('reports.attendance', $recap)
            ->setPaper('a4', 'landscape')
            ->download($this->reports->fileName($recap['year'], $recap['month'], 'pdf'));
    }
}
