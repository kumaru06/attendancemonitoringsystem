<?php

namespace App\Http\Requests;

use App\Enums\SchoolLevel;
use Carbon\CarbonImmutable;
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
            'month' => ['nullable', 'date_format:Y-m'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'level' => ['nullable', Rule::enum(SchoolLevel::class)],
            'section_id' => ['nullable', 'integer', Rule::exists('sections', 'id')->where('school_id', $this->user()?->school_id)],
            'search' => ['nullable', 'string', 'max:100'],
        ];
    }

    /**
     * @return array{month: string, from: string, to: string, level: string|null, section_id: int|null, search: string|null}
     */
    public function filters(): array
    {
        $timezone = (string) config('attendance.timezone', 'Asia/Manila');
        $monthStart = CarbonImmutable::createFromFormat('!Y-m', (string) $this->input('month', now($timezone)->format('Y-m')), $timezone);

        if ($monthStart === false) {
            $monthStart = now($timezone)->toImmutable()->startOfMonth();
        }

        $monthStart = $monthStart->startOfMonth();
        $monthEnd = $monthStart->endOfMonth();

        return [
            'month' => $monthStart->format('Y-m'),
            'from' => $this->input('from', $monthStart->toDateString()),
            'to' => $this->input('to', $this->input('from', $monthEnd->toDateString())),
            'level' => $this->enum('level', SchoolLevel::class)?->value,
            'section_id' => $this->filled('section_id') ? (int) $this->input('section_id') : null,
            'search' => $this->input('search'),
        ];
    }

    protected function prepareForValidation(): void
    {
        foreach (['month', 'level', 'section_id', 'search'] as $field) {
            if ($this->input($field) === '') {
                $this->merge([$field => null]);
            }
        }
    }
}
