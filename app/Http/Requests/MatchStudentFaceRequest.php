<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MatchStudentFaceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && $this->user()->is_active;
    }

    public function rules(): array
    {
        $length = (int) config('attendance.face_descriptor_length', 128);

        return [
            'descriptor' => ['required', 'array', 'size:'.$length],
            'descriptor.*' => ['required', 'numeric'],
        ];
    }
}
