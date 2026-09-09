<?php

namespace App\Http\Controllers;

use App\Http\Requests\AttendanceFilterRequest;
use App\Models\Attendance;
use App\Models\Section;
use App\Services\AttendanceExportService;
use Illuminate\View\View;

class AttendanceController extends Controller
{
    public function index(AttendanceFilterRequest $request, AttendanceExportService $exportService): View
    {
        $this->authorize('viewAny', Attendance::class);

        $filters = $request->filters();

        return view('attendances.index', [
            'attendances' => $exportService->filteredQuery($filters)->paginate(20)->withQueryString(),
            'sections' => Section::query()->orderBy('name')->get(),
            'filters' => $filters,
        ]);
    }
}
