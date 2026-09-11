<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class SearchScannerFacesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && $this->user()->is_active;
    }

    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:100'],
            'token' => ['nullable', 'string', 'max:255'],
            'student_id' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                if (filled($this->input('q')) || filled($this->input('token')) || filled($this->input('student_id'))) {
                    return;
                }

                $validator->errors()->add('q', 'Enter a student name or USN, or scan a QR code.');
            },
        ];
    }
}
