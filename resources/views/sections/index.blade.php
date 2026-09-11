@extends('layouts.app', ['title' => 'Sections'])

@php
    $openSectionForm = $errors->hasAny(['name', 'level']);
    $visibleLevels = $selectedLevel ? collect([$selectedLevel]) : collect($levels);
    $hasAnySection = collect($sectionsByLevel)->contains(fn ($group) => $group->isNotEmpty());
    $totalSections = collect($sectionsByLevel)->sum(fn ($group) => $group->count());
@endphp

@section('content')
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <nav class="flex flex-wrap gap-2" aria-label="School levels">
            <a href="{{ route('sections.index') }}"
               aria-label="All, {{ $totalSections }} {{ $totalSections === 1 ? 'section' : 'sections' }}"
               class="inline-flex items-center gap-1.5 rounded-full px-3.5 py-1.5 text-sm font-medium {{ $selectedLevel ? 'bg-white text-slate-600 shadow-[0_8px_24px_rgba(15,23,42,0.06)] hover:bg-slate-50' : 'bg-slate-900 text-white' }}">
                All
                <span class="rounded-full px-1.5 py-0.5 text-[11px] font-semibold tabular-nums {{ $selectedLevel ? 'bg-slate-100 text-slate-600' : 'bg-white/15' }}">{{ $totalSections }}</span>
            </a>
            @foreach ($levels as $level)
                @php
                    $levelSectionCount = $sectionsByLevel[$level->value]->count();
                @endphp
                <a href="{{ route('sections.index', ['level' => $level->value]) }}"
                   aria-label="{{ $level->label() }}, {{ $levelSectionCount }} {{ $levelSectionCount === 1 ? 'section' : 'sections' }}"
                   class="inline-flex items-center gap-1.5 rounded-full px-3.5 py-1.5 text-sm font-medium {{ $selectedLevel === $level ? 'bg-slate-900 text-white' : 'bg-white text-slate-600 shadow-[0_8px_24px_rgba(15,23,42,0.06)] hover:bg-slate-50' }}">
                    {{ $level->label() }}
                    <span class="rounded-full px-1.5 py-0.5 text-[11px] font-semibold tabular-nums {{ $selectedLevel === $level ? 'bg-white/15' : 'bg-slate-100 text-slate-600' }}">{{ $levelSectionCount }}</span>
                </a>
            @endforeach
        </nav>
        <button type="button" data-section-form-panel class="inline-flex items-center justify-center gap-2 rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-400">
            <x-icon name="plus" class="h-4 w-4" /> Add section
        </button>
    </div>

    @if (! $hasAnySection && ! $selectedLevel)
        <div class="rounded-3xl bg-white px-5 py-16 text-center shadow-[0_12px_40px_rgba(15,23,42,0.06)]">
            <p class="text-sm font-medium text-slate-900">No sections yet</p>
            <p class="mt-1 text-sm text-slate-400">Add a section to start grouping students by level.</p>
        </div>
    @else
        <div class="grid items-stretch gap-5 {{ $selectedLevel ? 'grid-cols-1' : 'lg:grid-cols-2' }}">
            @foreach ($visibleLevels as $level)
                @php
                    $group = $sectionsByLevel[$level->value];
                    $studentTotal = $group->sum('students_count');
                    $header = $level->headerTone();
                @endphp

                @if ($group->isEmpty() && ! $selectedLevel)
                    @continue
                @endif

                <section class="flex h-[22.5rem] flex-col rounded-3xl bg-white p-5 shadow-[0_12px_40px_rgba(15,23,42,0.06)] sm:p-6 {{ $selectedLevel ? 'lg:h-[32rem]' : '' }}" aria-labelledby="section-level-{{ $level->value }}">
                    <div class="mb-4 flex shrink-0 items-center justify-between gap-3 rounded-2xl px-4 py-3 {{ $header['wrap'] }}">
                        <div class="min-w-0">
                            <h2 id="section-level-{{ $level->value }}" class="flex items-center gap-2 text-base font-semibold tracking-tight {{ $header['title'] }}">
                                <span class="h-2.5 w-2.5 shrink-0 rounded-full {{ $header['dot'] }}"></span>
                                {{ $level->label() }}
                            </h2>
                            <p class="mt-1 text-sm {{ $header['meta'] }}">{{ $group->count() }} {{ $group->count() === 1 ? 'section' : 'sections' }}</p>
                        </div>
                        <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium {{ $header['badge'] }}">
                            {{ $studentTotal }} {{ $studentTotal === 1 ? 'student' : 'students' }}
                        </span>
                    </div>

                    @if ($group->isEmpty())
                        <p class="flex flex-1 items-center justify-center rounded-2xl bg-slate-50 px-4 text-center text-sm text-slate-400">No {{ $level->label() }} sections yet.</p>
                    @else
                        <div class="section-list min-h-0 flex-1 overflow-x-auto overflow-y-auto rounded-2xl bg-slate-50/80">
                            <ul class="divide-y divide-slate-100">
                                @foreach ($group as $section)
                                    <li class="flex items-center justify-between gap-3 px-4 py-3.5 hover:bg-white">
                                        <div class="min-w-0">
                                            <p class="truncate font-medium text-slate-900">{{ $section->name }}</p>
                                            <p class="mt-0.5 text-xs text-slate-400">{{ $section->students_count }} {{ $section->students_count === 1 ? 'student' : 'students' }}</p>
                                        </div>
                                        <button type="button"
                                                class="shrink-0 rounded-full bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 shadow-[0_8px_18px_rgba(15,23,42,0.06)] hover:bg-slate-100"
                                                data-section-panel
                                                data-action="{{ route('sections.update', $section) }}"
                                                data-name="{{ $section->name }}"
                                                data-level="{{ $section->level?->value }}"
                                                data-students="{{ $section->students_count }}">
                                            Edit
                                        </button>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </section>
            @endforeach
        </div>
    @endif

    <div id="section-form-panel" class="section-panel {{ $openSectionForm ? 'is-open' : '' }}" aria-hidden="{{ $openSectionForm ? 'false' : 'true' }}">
        <div class="section-panel__backdrop" data-section-form-panel-close></div>
        <div class="section-panel__dialog" role="dialog" aria-modal="true" aria-labelledby="section-form-panel-title">
            <div class="mb-5 flex items-start justify-between gap-3">
                <div>
                    <p id="section-form-panel-title" class="text-base font-semibold tracking-tight text-slate-900">Add section</p>
                    <p class="mt-1 text-sm text-slate-400">Create a class or course for this school.</p>
                </div>
                <button type="button" class="rounded-full bg-slate-100 p-2 text-slate-500 hover:bg-slate-200 hover:text-slate-800" data-section-form-panel-close aria-label="Close">
                    <x-icon name="x" class="h-4 w-4" />
                </button>
            </div>
            <form method="POST" action="{{ route('sections.store') }}" class="flex flex-col gap-4">
                @csrf
                @if ($openSectionForm)
                    <div class="rounded-xl bg-rose-50 px-4 py-3 text-sm text-rose-800" role="alert">
                        <p class="font-medium">Please correct the highlighted fields.</p>
                        <ul class="mt-2 list-disc space-y-1 pl-5">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                <div>
                    <label for="create-section-level" class="mb-1 block text-xs font-medium text-slate-500">Level</label>
                    <select id="create-section-level" name="level" required class="w-full rounded-xl bg-slate-50 px-3 py-2.5 text-sm text-slate-800 outline-none focus:bg-white focus:shadow-sm">
                        @foreach ($levels as $level)
                            <option value="{{ $level->value }}" @selected(old('level') === $level->value)>{{ $level->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="create-section-name" class="mb-1 block text-xs font-medium text-slate-500">Name</label>
                    <input id="create-section-name" name="name" type="text" required value="{{ old('name') }}" placeholder="Grade 11-A or BSIT 1-A"
                           class="w-full rounded-xl bg-slate-50 px-3 py-2.5 text-sm text-slate-800 outline-none focus:bg-white focus:shadow-sm">
                </div>
                <div class="flex justify-end gap-3">
                    <button type="button" class="rounded-xl bg-slate-100 px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-200" data-section-form-panel-close>Cancel</button>
                    <button type="submit" class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800">Save section</button>
                </div>
            </form>
        </div>
    </div>

    <div id="section-panel" class="section-panel" aria-hidden="true">
        <div class="section-panel__backdrop" data-section-panel-close></div>
        <div class="section-panel__dialog" role="dialog" aria-modal="true" aria-labelledby="section-panel-title">
            <div class="mb-5 flex items-start justify-between gap-3">
                <div>
                    <p id="section-panel-title" class="text-base font-semibold tracking-tight text-slate-900">Edit section</p>
                    <p data-section-panel-meta class="mt-1 text-sm text-slate-400"></p>
                </div>
                <button type="button" class="rounded-full bg-slate-100 p-2 text-slate-500 hover:bg-slate-200 hover:text-slate-800" data-section-panel-close aria-label="Close">
                    <x-icon name="x" class="h-4 w-4" />
                </button>
            </div>
            <form method="POST" data-section-panel-form class="flex flex-col gap-4">
                @csrf
                @method('PUT')
                <div>
                    <label for="section-panel-level" class="mb-1 block text-xs font-medium text-slate-500">Level</label>
                    <select id="section-panel-level" name="level" required class="w-full rounded-xl bg-slate-50 px-3 py-2.5 text-sm text-slate-800 outline-none focus:bg-white focus:shadow-sm">
                        @foreach ($levels as $level)
                            <option value="{{ $level->value }}">{{ $level->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="section-panel-name" class="mb-1 block text-xs font-medium text-slate-500">Name</label>
                    <input id="section-panel-name" name="name" type="text" required class="w-full rounded-xl bg-slate-50 px-3 py-2.5 text-sm text-slate-800 outline-none focus:bg-white focus:shadow-sm">
                </div>
                <div class="flex justify-end gap-3">
                    <button type="button" class="rounded-xl bg-slate-100 px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-200" data-section-panel-close>Cancel</button>
                    <button type="submit" class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800">Save changes</button>
                </div>
            </form>
        </div>
    </div>
@endsection
