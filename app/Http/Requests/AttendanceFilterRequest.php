<?php

namespace App\Http\Requests;

use App\Enums\SchoolLevel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AttendanceFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'level' => ['nullable', Rule::enum(SchoolLevel::class)],
            'section_id' => ['nullable', 'exists:sections,id'],
            'search' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function filters(): array
    {
        $timezone = config('attendance.timezone');
        $today = now($timezone)->toDateString();

        return [
            'from' => $this->input('from', $today),
            'to' => $this->input('to', $this->input('from', $today)),
            'level' => $this->enum('level', SchoolLevel::class)?->value,
            'section_id' => $this->filled('section_id') ? (int) $this->input('section_id') : null,
            'search' => $this->input('search'),
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->input('level') === '') {
            $this->merge(['level' => null]);
        }
    }
}
