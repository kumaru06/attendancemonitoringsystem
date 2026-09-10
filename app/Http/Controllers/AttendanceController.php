<?php

namespace App\Http\Controllers;

use App\Enums\SchoolLevel;
use App\Http\Requests\AttendanceFilterRequest;
use App\Models\Attendance;
use App\Models\Section;
use App\Services\Sf2RegisterService;
use Illuminate\View\View;

class AttendanceController extends Controller
{
    public function index(AttendanceFilterRequest $request, Sf2RegisterService $registerService): View
    {
        $this->authorize('viewAny', Attendance::class);

        $filters = $request->filters();

        $sections = Section::query()
            ->ordered()
            ->when($filters['level'], fn ($query, string $level) => $query->where('level', $level))
            ->get();

        $section = $filters['section_id']
            ? Section::query()->with('school')->find($filters['section_id'])
            : null;

        if ($section && $filters['level'] && $section->level?->value !== $filters['level']) {
            $section = null;
        }

        return view('attendances.index', [
            'sections' => $sections,
            'levels' => SchoolLevel::cases(),
            'filters' => $filters,
            'section' => $section,
            'register' => $section ? $registerService->build($section, $filters['month']) : null,
        ]);
    }
}
