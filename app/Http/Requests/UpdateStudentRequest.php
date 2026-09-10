<?php

namespace App\Http\Requests;

use App\Enums\StudentGender;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        $studentId = $this->route('student')?->id;

        return [
            'student_number' => ['required', 'string', 'max:50', Rule::unique('students', 'student_number')->where('school_id', $this->user()?->school_id)->ignore($studentId)],
            'first_name' => ['required', 'string', 'max:100'],
            'middle_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'gender' => ['required', Rule::enum(StudentGender::class)],
            'section_id' => ['required', Rule::exists('sections', 'id')->where('school_id', $this->user()?->school_id)],
            'photo' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:2048'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'student_number' => 'USN/ID Number',
        ];
    }
}
