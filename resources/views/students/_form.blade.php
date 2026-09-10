@php
    $student = $student ?? null;
    $idPrefix = $idPrefix ?? '';
    $fieldClass = 'w-full rounded-xl bg-slate-50 px-3 py-2.5 text-sm text-slate-800 outline-none focus:bg-white focus:shadow-sm';
@endphp

<div class="grid gap-4 sm:grid-cols-2">
    <div class="sm:col-span-2">
        <label for="{{ $idPrefix }}student_number" class="mb-1 block text-xs font-medium text-slate-500">USN/ID Number</label>
        <input id="{{ $idPrefix }}student_number" name="student_number" type="text" required value="{{ old('student_number', $student?->student_number) }}"
               class="{{ $fieldClass }}">
    </div>
    <div>
        <label for="{{ $idPrefix }}first_name" class="mb-1 block text-xs font-medium text-slate-500">First name</label>
        <input id="{{ $idPrefix }}first_name" name="first_name" type="text" required value="{{ old('first_name', $student?->first_name) }}"
               class="{{ $fieldClass }}">
    </div>
    <div>
        <label for="{{ $idPrefix }}middle_name" class="mb-1 block text-xs font-medium text-slate-500">Middle name <span class="font-normal text-slate-400">(optional)</span></label>
        <input id="{{ $idPrefix }}middle_name" name="middle_name" type="text" value="{{ old('middle_name', $student?->middle_name) }}"
               class="{{ $fieldClass }}">
    </div>
    <div>
        <label for="{{ $idPrefix }}last_name" class="mb-1 block text-xs font-medium text-slate-500">Last name</label>
        <input id="{{ $idPrefix }}last_name" name="last_name" type="text" required value="{{ old('last_name', $student?->last_name) }}"
               class="{{ $fieldClass }}">
    </div>
    <div>
        <label for="{{ $idPrefix }}gender" class="mb-1 block text-xs font-medium text-slate-500">Gender</label>
        <select id="{{ $idPrefix }}gender" name="gender" required class="{{ $fieldClass }}">
            <option value="">Select gender</option>
            @foreach (\App\Enums\StudentGender::cases() as $gender)
                <option value="{{ $gender->value }}" @selected((string) old('gender', $student?->gender?->value) === $gender->value)>{{ $gender->label() }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label for="{{ $idPrefix }}section_id" class="mb-1 block text-xs font-medium text-slate-500">Section / course</label>
        <select id="{{ $idPrefix }}section_id" name="section_id" required class="{{ $fieldClass }}">
            <option value="">Select a section</option>
            <x-section-options :sections="$sections" :selected="old('section_id', $student?->section_id)" />
        </select>
    </div>
    <div class="sm:col-span-2">
        <label for="{{ $idPrefix }}photo" class="mb-1 block text-xs font-medium text-slate-500">Student photo</label>
        <input id="{{ $idPrefix }}photo" name="photo" type="file" accept="image/jpeg,image/png,image/webp" data-photo-input
               class="{{ $fieldClass }} file:mr-3 file:rounded-lg file:border-0 file:bg-white file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-slate-700">
        <p class="mt-1 text-xs text-slate-400">JPEG, PNG, or WebP. Maximum 2 MB.</p>
        <img id="{{ $idPrefix }}photo-preview" data-photo-preview alt="Photo preview" class="mt-3 hidden h-28 w-28 rounded-2xl object-cover shadow-[0_12px_28px_rgba(15,23,42,0.08)]"
             @if ($student?->photo_path) src="{{ route('students.photo', $student) }}" style="display:block" @endif>
    </div>
    <div class="sm:col-span-2">
        <label class="inline-flex items-center gap-2 text-sm text-slate-700">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $student?->is_active ?? true)) class="rounded border-slate-300 text-slate-900 focus:ring-slate-400">
            Active student
        </label>
    </div>
</div>
