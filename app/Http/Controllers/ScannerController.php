<?php

namespace App\Http\Controllers;

use App\Http\Requests\ScanAttendanceRequest;
use App\Models\Attendance;
use App\Services\AttendanceService;
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
            ->limit(12)
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
        $student = $result->student;

        return response()->json([
            'code' => $result->code,
            'message' => $result->message,
            'time_in' => $result->attendance?->time_in,
            'attendance_date' => optional($result->attendance?->attendance_date)->toDateString(),
            'student' => $student?->id ? [
                'id' => $student->id,
                'student_number' => $student->student_number,
                'name' => $student->full_name,
                'section' => $student->section?->name,
                'level' => $student->section?->level?->label(),
                'photo_url' => $student->photo_path ? route('students.photo', $student) : null,
            ] : null,
        ], $result->httpStatus());
    }
}
