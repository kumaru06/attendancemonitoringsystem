<?php

namespace App\Http\Controllers;

use App\Http\Requests\AttendanceFilterRequest;
use App\Models\Attendance;
use App\Services\AttendanceExportService;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttendanceExportController extends Controller
{
    public function __invoke(AttendanceFilterRequest $request, AttendanceExportService $exportService): StreamedResponse
    {
        $this->authorize('export', Attendance::class);

        return $exportService->download($request->filters());
    }
}
