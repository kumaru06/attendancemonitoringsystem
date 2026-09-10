@props(['sections', 'selected' => null])

@foreach (\App\Enums\SchoolLevel::cases() as $level)
    @php
        $levelSections = $sections->filter(
            fn (\App\Models\Section $section): bool => $section->level === $level,
        );
    @endphp
    @continue($levelSections->isEmpty())
    <optgroup label="{{ $level->label() }}">
        @foreach ($levelSections as $section)
            <option value="{{ $section->id }}" @selected((string) $selected === (string) $section->id)>{{ $section->name }}</option>
        @endforeach
    </optgroup>
@endforeach
