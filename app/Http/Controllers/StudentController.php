<?php

namespace App\Http\Controllers;

use App\Enums\SchoolLevel;
use App\Http\Requests\StoreStudentRequest;
use App\Http\Requests\UpdateStudentRequest;
use App\Models\Section;
use App\Models\Student;
use App\Services\AuditLogService;
use App\Services\StudentQrService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class StudentController extends Controller
{
    public function __construct(
        private readonly StudentQrService $qrService,
        private readonly AuditLogService $auditLog,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Student::class);

        $level = SchoolLevel::tryFrom((string) $request->input('level'));
        $search = $request->string('search')->trim()->toString();

        $students = Student::query()
            ->with(['section'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('student_number', 'like', "%{$search}%")
                        ->orWhere('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('middle_name', 'like', "%{$search}%");
                });
            })
            ->when($level, fn ($query) => $query->whereHas('section', fn ($section) => $section->where('level', $level)))
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->paginate(15)
            ->withQueryString();

        $levelCounts = Student::query()
            ->join('sections', 'sections.id', '=', 'students.section_id')
            ->selectRaw('sections.level as level, count(*) as total')
            ->groupBy('sections.level')
            ->pluck('total', 'level');

        if ($request->boolean('directory')) {
            return view('students._directory', [
                'students' => $students,
                'selectedLevel' => $level,
                'search' => $search,
            ]);
        }

        return view('students.index', [
            'students' => $students,
            'sections' => Section::query()->ordered()->get(),
            'levels' => SchoolLevel::cases(),
            'levelCounts' => $levelCounts,
            'selectedLevel' => $level,
            'search' => $search,
        ]);
    }

    public function create(): RedirectResponse
    {
        $this->authorize('create', Student::class);

        return redirect()->route('students.index', ['add' => 1]);
    }

    public function store(StoreStudentRequest $request): RedirectResponse
    {
        $this->authorize('create', Student::class);

        $data = $request->safe()->except('photo');
        $data['is_active'] = $request->boolean('is_active', true);

        if ($request->hasFile('photo')) {
            $data['photo_path'] = $request->file('photo')->store('student-photos', 'local');
        }

        $student = Student::query()->create($data);
        $this->qrService->issue($student);
        $this->auditLog->record($request->user(), 'student.created', $student, [
            'student_number' => $student->student_number,
        ]);

        return redirect()->route('students.show', $student)->with('success', 'Student registered and QR credential issued.');
    }

    public function show(Request $request, Student $student): View
    {
        $this->authorize('view', $student);

        $student->load(['section', 'currentQrCredential']);

        if ($request->boolean('panel')) {
            $attendances = $student->attendances()
                ->with('recorder')
                ->orderByDesc('attendance_date')
                ->limit(50)
                ->get();

            return view('students.panel', compact('student', 'attendances'));
        }

        $attendances = $student->attendances()
            ->with('recorder')
            ->orderByDesc('attendance_date')
            ->paginate(15);

        return view('students.show', compact('student', 'attendances'));
    }

    public function edit(Student $student): View
    {
        $this->authorize('update', $student);

        return view('students.edit', [
            'student' => $student,
            'sections' => Section::query()->ordered()->get(),
        ]);
    }

    public function update(UpdateStudentRequest $request, Student $student): RedirectResponse
    {
        $this->authorize('update', $student);

        $data = $request->safe()->except('photo');
        $data['is_active'] = $request->boolean('is_active');
        $wasActive = $student->is_active;

        if ($request->hasFile('photo')) {
            if ($student->photo_path) {
                Storage::disk('local')->delete($student->photo_path);
            }
            $data['photo_path'] = $request->file('photo')->store('student-photos', 'local');
        }

        $student->update($data);

        $action = 'student.updated';
        if ($wasActive && ! $student->is_active) {
            $action = 'student.deactivated';
        } elseif (! $wasActive && $student->is_active) {
            $action = 'student.activated';
        }

        $this->auditLog->record($request->user(), $action, $student, [
            'student_number' => $student->student_number,
            'is_active' => $student->is_active,
        ]);

        return redirect()->route('students.show', $student)->with('success', 'Student updated.');
    }
}
