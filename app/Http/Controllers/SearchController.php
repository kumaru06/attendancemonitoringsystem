<?php

namespace App\Http\Controllers;

use App\Models\Section;
use App\Models\Student;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Student::class);

        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
        ]);

        $query = trim((string) ($validated['q'] ?? ''));

        $jumps = collect([
            ['label' => 'Scanner', 'hint' => 'Open the entrance scanner', 'url' => route('scanner.index')],
            ['label' => 'Students', 'hint' => 'Browse the student directory', 'url' => route('students.index')],
            ['label' => 'Sections', 'hint' => 'Manage class sections', 'url' => route('sections.index')],
            ['label' => 'Attendance', 'hint' => 'Open the daily register', 'url' => route('attendances.index')],
            ['label' => 'Settings', 'hint' => 'Update school and account details', 'url' => route('settings.edit')],
        ]);

        if ($query !== '') {
            $needle = mb_strtolower($query);
            $jumps = $jumps
                ->filter(fn (array $jump): bool => str_contains(mb_strtolower($jump['label']), $needle))
                ->values();
        }

        if ($query === '') {
            return response()->json([
                'students' => [],
                'sections' => [],
                'jumps' => $jumps->all(),
            ]);
        }

        $students = Student::query()
            ->with('section')
            ->where(function ($inner) use ($query) {
                $inner->where('student_number', 'like', '%'.$query.'%')
                    ->orWhere('first_name', 'like', '%'.$query.'%')
                    ->orWhere('last_name', 'like', '%'.$query.'%')
                    ->orWhere('middle_name', 'like', '%'.$query.'%');
            })
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->limit(8)
            ->get()
            ->map(fn (Student $student): array => [
                'id' => $student->id,
                'name' => $student->full_name,
                'hint' => trim($student->student_number.' · '.($student->section?->labeledName() ?? '')),
                'url' => route('students.show', $student),
            ])
            ->values();

        $sections = Section::query()
            ->where('name', 'like', '%'.$query.'%')
            ->ordered()
            ->limit(8)
            ->get()
            ->map(fn (Section $section): array => [
                'id' => $section->id,
                'name' => $section->name,
                'hint' => $section->level?->label() ?? 'Section',
                'url' => route('sections.index', ['level' => $section->level?->value]),
            ])
            ->values();

        return response()->json([
            'students' => $students->all(),
            'sections' => $sections->all(),
            'jumps' => $jumps->all(),
        ]);
    }
}
