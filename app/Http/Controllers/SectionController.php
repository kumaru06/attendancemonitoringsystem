<?php

namespace App\Http\Controllers;

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

        return view('sections.index', [
            'sections' => Section::query()->withCount('students')->orderBy('name')->paginate(20),
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
