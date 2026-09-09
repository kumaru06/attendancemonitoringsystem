<?php

namespace App\Http\Controllers;

use App\Models\Student;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class StudentPhotoController extends Controller
{
    public function __invoke(Student $student): Response
    {
        $this->authorize('viewPhoto', $student);

        if (! $student->photo_path || ! Storage::disk('local')->exists($student->photo_path)) {
            abort(404);
        }

        return Storage::disk('local')->response($student->photo_path);
    }
}
