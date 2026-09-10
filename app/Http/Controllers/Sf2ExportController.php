<?php

namespace App\Http\Controllers;

use App\Http\Requests\AttendanceFilterRequest;
use App\Models\Attendance;
use App\Models\Section;
use App\Services\Sf2ExcelExporter;
use App\Services\Sf2RegisterService;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class Sf2ExportController extends Controller
{
    public function __invoke(
        AttendanceFilterRequest $request,
        Sf2RegisterService $registerService,
        Sf2ExcelExporter $exporter,
    ): StreamedResponse|RedirectResponse {
        $this->authorize('export', Attendance::class);

        $filters = $request->filters();

        if (! $filters['section_id']) {
            return redirect()
                ->route('attendances.index', $request->query())
                ->withErrors(['section_id' => 'Choose a section to export SF2.']);
        }

        $section = Section::query()->with('school')->find($filters['section_id']);

        abort_if($section === null, 404);

        return $exporter->download($registerService->build($section, $filters['month']));
    }
}
