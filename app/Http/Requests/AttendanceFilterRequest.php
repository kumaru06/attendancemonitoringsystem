<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

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
            'section_id' => $this->filled('section_id') ? (int) $this->input('section_id') : null,
            'search' => $this->input('search'),
        ];
    }
}
