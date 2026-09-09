@extends('layouts.app', ['title' => 'Sections'])

@section('content')
    <form method="POST" action="{{ route('sections.store') }}" class="mb-6 grid gap-3 rounded-3xl bg-white p-5 shadow-[0_12px_40px_rgba(15,23,42,0.06)] md:grid-cols-[13rem_minmax(0,1fr)_auto] md:items-end">
        @csrf
        <div>
            <label for="level" class="mb-1 block text-xs font-medium text-slate-500">Level</label>
            <select id="level" name="level" required class="w-full rounded-xl bg-slate-50 px-3 py-2.5 text-sm text-slate-800 outline-none focus:bg-white focus:shadow-sm">
                @foreach ($levels as $level)
                    <option value="{{ $level->value }}" @selected(old('level') === $level->value)>{{ $level->label() }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="name" class="mb-1 block text-xs font-medium text-slate-500">Name</label>
            <input id="name" name="name" type="text" required value="{{ old('name') }}" placeholder="Grade 11-A or BSIT 1-A"
                   class="w-full rounded-xl bg-slate-50 px-3 py-2.5 text-sm text-slate-800 outline-none focus:bg-white focus:shadow-sm">
        </div>
        <button type="submit" class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800">Save section</button>
    </form>

    <div class="flex flex-col gap-5">
        @foreach ($levels as $level)
            @php
                $group = $sectionsByLevel[$level->value];
                $studentTotal = $group->sum('students_count');
            @endphp
            <section class="rounded-3xl bg-white p-5 shadow-[0_12px_40px_rgba(15,23,42,0.06)] sm:p-6" aria-labelledby="section-level-{{ $level->value }}">
                <div class="mb-3 flex items-end justify-between gap-3">
                    <div>
                        <h2 id="section-level-{{ $level->value }}" class="text-base font-semibold tracking-tight text-slate-900">{{ $level->label() }}</h2>
                        <p class="mt-1 text-sm text-slate-400">{{ $group->count() }} {{ $group->count() === 1 ? 'section' : 'sections' }}</p>
                    </div>
                    <p class="text-xs font-medium text-slate-400">{{ $studentTotal }} {{ $studentTotal === 1 ? 'student' : 'students' }}</p>
                </div>

                @if ($group->isEmpty())
                    <p class="rounded-2xl bg-slate-50 px-4 py-10 text-center text-sm text-slate-400">No {{ $level->label() }} sections yet.</p>
                @else
                    <ul class="flex flex-col gap-1">
                        @foreach ($group as $section)
                            <li class="flex items-center justify-between gap-3 rounded-2xl px-3 py-3 hover:bg-slate-50">
                                <div class="min-w-0">
                                    <p class="truncate font-medium text-slate-900">{{ $section->name }}</p>
                                    <p class="mt-0.5 text-xs text-slate-400">{{ $section->students_count }} {{ $section->students_count === 1 ? 'student' : 'students' }}</p>
                                </div>
                                <button type="button"
                                        class="shrink-0 rounded-full bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-200"
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
                @endif
            </section>
        @endforeach
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
                <button type="submit" class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800">Save changes</button>
            </form>
        </div>
    </div>
@endsection
