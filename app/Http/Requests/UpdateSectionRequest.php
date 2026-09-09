<?php

namespace App\Http\Requests;

use App\Enums\SchoolLevel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        $sectionId = $this->route('section')?->id;

        return [
            'name' => ['required', 'string', 'max:100', Rule::unique('sections', 'name')->ignore($sectionId)],
            'level' => ['required', Rule::enum(SchoolLevel::class)],
        ];
    }
}
