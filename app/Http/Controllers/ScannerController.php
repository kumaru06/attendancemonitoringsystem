<?php

namespace App\Http\Controllers;

use App\Http\Requests\EnrollStudentFaceRequest;
use App\Http\Requests\MatchStudentFaceRequest;
use App\Http\Requests\ScanAttendanceRequest;
use App\Http\Requests\SearchScannerFacesRequest;
use App\Models\Attendance;
use App\Models\Student;
use App\Services\AttendanceScanResult;
use App\Services\AttendanceService;
use App\Services\FaceRecognitionService;
use App\Services\StudentQrService;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class ScannerController extends Controller
{
    public function index(AttendanceService $attendanceService): View
    {
        $this->authorize('scan', Attendance::class);

        $recent = Attendance::query()
            ->with(['student.section'])
            ->where('recorded_by', request()->user()->id)
            ->whereDate('attendance_date', $attendanceService->today())
            ->latest('id')
            ->limit(40)
            ->get();

        return view('scanner.index', [
            'recent' => $recent,
            'today' => $attendanceService->today(),
        ]);
    }

    public function scan(ScanAttendanceRequest $request, AttendanceService $attendanceService): JsonResponse
    {
        $this->authorize('scan', Attendance::class);

        $result = $attendanceService->recordFromToken($request->validated('token'), $request->user());

        return $this->scanResponse($result);
    }

    public function faces(
        SearchScannerFacesRequest $request,
        StudentQrService $qrService,
    ): JsonResponse {
        $this->authorize('scan', Attendance::class);

        if (filled($request->validated('token'))) {
            $student = $this->studentFromToken($qrService, (string) $request->validated('token'), $request->user()?->school_id);

            return response()->json([
                'students' => $student ? [$this->studentPayload($student)] : [],
            ]);
        }

        if (filled($request->validated('student_id'))) {
            $student = Student::query()->with('section')->find($request->integer('student_id'));

            return response()->json([
                'students' => $student ? [$this->studentPayload($student)] : [],
            ]);
        }

        $search = trim((string) $request->validated('q'));

        $students = Student::query()
            ->with('section')
            ->where(function ($query) use ($search) {
                $query->where('student_number', 'like', "%{$search}%")
                    ->orWhere('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('middle_name', 'like', "%{$search}%");
            })
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->limit(15)
            ->get();

        return response()->json([
            'students' => $students->map(fn (Student $student): array => $this->studentPayload($student))->values(),
        ]);
    }

    public function enrollFace(
        EnrollStudentFaceRequest $request,
        FaceRecognitionService $faceRecognition,
    ): JsonResponse {
        $this->authorize('scan', Attendance::class);

        $student = Student::query()->find($request->integer('student_id'));

        if (! $student) {
            abort(404);
        }

        $this->authorize('enrollFace', $student);

        if (! $student->is_active) {
            return response()->json([
                'code' => 'inactive',
                'message' => 'Student account is inactive',
                'student' => $this->studentPayload($student),
            ], 403);
        }

        $descriptor = $faceRecognition->normalize($request->validated('descriptor'));

        if ($descriptor === null) {
            return response()->json([
                'code' => 'invalid',
                'message' => 'Face capture could not be saved. Try again.',
                'student' => $this->studentPayload($student),
            ], 422);
        }

        $wasEnrolled = $student->hasEnrolledFace();
        $student = $faceRecognition->enroll($student, $descriptor);

        return response()->json([
            'code' => 'enrolled',
            'message' => 'Face registered',
            'student' => $this->studentPayload($student),
        ], $wasEnrolled ? 200 : 201);
    }

    public function matchFace(MatchStudentFaceRequest $request, AttendanceService $attendanceService): JsonResponse
    {
        $this->authorize('scan', Attendance::class);

        $result = $attendanceService->recordFromFace($request->validated('descriptor'), $request->user());

        return $this->scanResponse($result);
    }

    private function scanResponse(AttendanceScanResult $result): JsonResponse
    {
        $student = $result->student;

        return response()->json([
            'code' => $result->code,
            'message' => $result->message,
            'time_in' => $result->attendance?->time_in,
            'attendance_date' => optional($result->attendance?->attendance_date)->toDateString(),
            'student' => $student?->id ? $this->studentPayload($student) : null,
        ], $result->httpStatus());
    }

    /**
     * @return array{
     *     id: int,
     *     student_number: string,
     *     name: string,
     *     initials: string,
     *     section: ?string,
     *     level: ?string,
     *     photo_url: ?string,
     *     face_enrolled: bool
     * }
     */
    private function studentPayload(Student $student): array
    {
        $student->loadMissing('section');

        return [
            'id' => $student->id,
            'student_number' => $student->student_number,
            'name' => $student->full_name,
            'initials' => $student->initials,
            'section' => $student->section?->name,
            'level' => $student->section?->level?->label(),
            'photo_url' => $student->photo_path ? route('students.photo', $student) : null,
            'face_enrolled' => $student->hasEnrolledFace(),
        ];
    }

    private function studentFromToken(StudentQrService $qrService, string $token, ?int $schoolId): ?Student
    {
        $credential = $qrService->findCurrentByToken($token);

        if (! $credential) {
            return null;
        }

        $student = $credential->student()->withoutGlobalScopes()->with('section')->first();

        if (! $student || ($schoolId && (int) $student->school_id !== (int) $schoolId)) {
            return null;
        }

        return $student;
    }
}
