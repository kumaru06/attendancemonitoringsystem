<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Services\AuditLogService;
use App\Services\StudentQrService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class StudentQrController extends Controller
{
    public function __construct(
        private readonly StudentQrService $qrService,
        private readonly AuditLogService $auditLog,
    ) {}

    public function show(Student $student): View
    {
        $this->authorize('manageQr', $student);

        $credential = $student->currentQrCredential;

        abort_unless($credential, 404);

        return view('students.qr-card', [
            'student' => $student->load('section'),
            'svg' => $this->qrService->svg($this->qrService->decryptToken($credential)),
        ]);
    }

    public function download(Student $student): Response
    {
        $this->authorize('manageQr', $student);

        $credential = $student->currentQrCredential;

        abort_unless($credential, 404);

        $svg = $this->qrService->svg($this->qrService->decryptToken($credential));
        $filename = 'qr-'.$student->student_number.'.svg';

        return response($svg, 200, [
            'Content-Type' => 'image/svg+xml',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    public function replace(Request $request, Student $student): RedirectResponse
    {
        $this->authorize('manageQr', $student);

        $request->validate([
            'confirm' => ['accepted'],
        ]);

        $this->qrService->replace($student);
        $this->auditLog->record($request->user(), 'qr.replaced', $student, [
            'student_number' => $student->student_number,
        ]);

        return redirect()->route('students.show', $student)->with('success', 'Previous QR revoked. A new credential was issued.');
    }
}
