<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Services\AuditLogService;
use App\Services\FaceRecognitionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class StudentFaceController extends Controller
{
    public function __construct(
        private readonly FaceRecognitionService $faceRecognition,
        private readonly AuditLogService $auditLog,
    ) {}

    public function reset(Request $request, Student $student): RedirectResponse
    {
        $this->authorize('resetFace', $student);

        $request->validate([
            'confirm' => ['accepted'],
        ]);

        $this->faceRecognition->reset($student);
        $this->auditLog->record($request->user(), 'face.reset', $student, [
            'student_number' => $student->student_number,
        ]);

        return back()->with('success', 'Face recognition was reset. Enroll the student again on the scanner.');
    }
}
