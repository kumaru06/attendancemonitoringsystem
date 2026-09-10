<?php

namespace App\Http\Requests;

use App\Enums\SchoolLevel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100', Rule::unique('sections', 'name')->where('school_id', $this->user()?->school_id)],
            'level' => ['required', Rule::enum(SchoolLevel::class)],
        ];
    }
}
