<?php

namespace App\Http\Controllers;

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

        $students = Student::query()
            ->with(['section', 'currentQrCredential'])
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search')->toString();
                $query->where(function ($inner) use ($search) {
                    $inner->where('student_number', 'like', "%{$search}%")
                        ->orWhere('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('middle_name', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('section_id'), fn ($query) => $query->where('section_id', $request->integer('section_id')))
            ->when($request->filled('status'), function ($query) use ($request) {
                $query->where('is_active', $request->input('status') === 'active');
            })
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->paginate(15)
            ->withQueryString();

        return view('students.index', [
            'students' => $students,
            'sections' => Section::query()->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Student::class);

        return view('students.create', [
            'sections' => Section::query()->orderBy('name')->get(),
        ]);
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

    public function show(Student $student): View
    {
        $this->authorize('view', $student);

        $student->load(['section', 'currentQrCredential']);

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
            'sections' => Section::query()->orderBy('name')->get(),
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
