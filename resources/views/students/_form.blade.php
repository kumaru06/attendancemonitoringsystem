@php $student = $student ?? null; @endphp

<div class="grid gap-4 sm:grid-cols-2">
    <div class="sm:col-span-2">
        <label for="student_number" class="mb-1 block text-sm font-medium text-slate-700">Student number</label>
        <input id="student_number" name="student_number" type="text" required value="{{ old('student_number', $student?->student_number) }}"
               class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500">
    </div>
    <div>
        <label for="first_name" class="mb-1 block text-sm font-medium text-slate-700">First name</label>
        <input id="first_name" name="first_name" type="text" required value="{{ old('first_name', $student?->first_name) }}"
               class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500">
    </div>
    <div>
        <label for="middle_name" class="mb-1 block text-sm font-medium text-slate-700">Middle name <span class="font-normal text-slate-400">(optional)</span></label>
        <input id="middle_name" name="middle_name" type="text" value="{{ old('middle_name', $student?->middle_name) }}"
               class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500">
    </div>
    <div>
        <label for="last_name" class="mb-1 block text-sm font-medium text-slate-700">Last name</label>
        <input id="last_name" name="last_name" type="text" required value="{{ old('last_name', $student?->last_name) }}"
               class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500">
    </div>
    <div>
        <label for="section_id" class="mb-1 block text-sm font-medium text-slate-700">Section / course</label>
        <select id="section_id" name="section_id" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500">
            <option value="">Select a section</option>
            @foreach ($sections as $section)
                <option value="{{ $section->id }}" @selected(old('section_id', $student?->section_id) == $section->id)>{{ $section->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="sm:col-span-2">
        <label for="photo" class="mb-1 block text-sm font-medium text-slate-700">Student photo</label>
        <input id="photo" name="photo" type="file" accept="image/jpeg,image/png,image/webp" data-photo-input
               class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm file:mr-3 file:rounded-md file:border-0 file:bg-slate-100 file:px-3 file:py-1.5 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500">
        <p class="mt-1 text-xs text-slate-500">JPEG, PNG, or WebP. Maximum 2 MB.</p>
        <img id="photo-preview" alt="Photo preview" class="mt-3 hidden h-28 w-28 rounded-xl object-cover ring-1 ring-slate-200"
             @if ($student?->photo_path) src="{{ route('students.photo', $student) }}" style="display:block" @endif>
    </div>
    <div class="sm:col-span-2">
        <label class="inline-flex items-center gap-2 text-sm text-slate-700">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $student?->is_active ?? true)) class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
            Active student
        </label>
    </div>
</div>
