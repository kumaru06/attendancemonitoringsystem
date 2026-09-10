<?php

namespace App\Http\Controllers;

use App\Enums\SchoolLevel;
use App\Http\Requests\StoreSectionRequest;
use App\Http\Requests\UpdateSectionRequest;
use App\Models\Section;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SectionController extends Controller
{
    public function __construct(private readonly AuditLogService $auditLog) {}

    public function index(): View
    {
        $this->authorize('viewAny', Section::class);

        $sections = Section::query()->withCount('students')->ordered()->get();

        $grouped = $sections->groupBy(
            fn (Section $section): string => $section->level?->value ?? '',
        );

        $sectionsByLevel = collect(SchoolLevel::cases())
            ->mapWithKeys(fn (SchoolLevel $level) => [
                $level->value => $grouped->get($level->value, collect()),
            ]);

        return view('sections.index', [
            'sectionsByLevel' => $sectionsByLevel,
            'levels' => SchoolLevel::cases(),
            'selectedLevel' => SchoolLevel::tryFrom((string) request()->input('level')),
        ]);
    }

    public function store(StoreSectionRequest $request): RedirectResponse
    {
        $section = Section::query()->create($request->validated());
        $this->auditLog->record($request->user(), 'section.created', $section, [
            'name' => $section->name,
        ]);

        return redirect()->route('sections.index')->with('success', 'Section created.');
    }

    public function update(UpdateSectionRequest $request, Section $section): RedirectResponse
    {
        $section->update($request->validated());
        $this->auditLog->record($request->user(), 'section.updated', $section, [
            'name' => $section->name,
        ]);

        return redirect()->route('sections.index')->with('success', 'Section updated.');
    }
}
