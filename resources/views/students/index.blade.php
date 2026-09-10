@extends('layouts.app', ['title' => 'Students'])

@php
    $openStudentForm = request()->boolean('add')
        || $errors->hasAny(['student_number', 'first_name', 'middle_name', 'last_name', 'gender', 'section_id', 'photo', 'is_active']);

    $allActive = 'inline-flex items-center gap-2 rounded-full bg-slate-900 px-4 py-2 text-sm font-semibold text-white shadow-sm';
    $allIdle = 'inline-flex items-center gap-2 rounded-full bg-white px-4 py-2 text-sm font-medium text-slate-600 shadow-[0_8px_24px_rgba(15,23,42,0.06)] hover:bg-slate-50';
@endphp

@section('content')
    <div class="mb-6 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
        <div class="flex min-w-0 flex-1 flex-wrap items-center gap-2">
            <nav class="flex flex-wrap gap-2" aria-label="School levels" data-student-levels>
                <a href="{{ route('students.index', array_filter(['search' => $search ?: null])) }}"
                   data-student-level
                   data-active-class="{{ $allActive }}"
                   data-idle-class="{{ $allIdle }}"
                   aria-current="{{ $selectedLevel ? 'false' : 'page' }}"
                   class="{{ $selectedLevel ? $allIdle : $allActive }}">
                    All
                    <span class="rounded-full px-1.5 py-0.5 text-[11px] font-semibold tabular-nums {{ $selectedLevel ? 'bg-slate-100 text-slate-600' : 'bg-white/15' }}">{{ $levelCounts->sum() }}</span>
                </a>
                @foreach ($levels as $level)
                    @php
                    $active = $selectedLevel === $level;
                    $classes = $level->filterButtonClasses();
                    @endphp
                    <a href="{{ route('students.index', array_filter(['level' => $level->value, 'search' => $search ?: null])) }}"
                       data-student-level="{{ $level->value }}"
                       data-active-class="{{ $classes['active'] }}"
                       data-idle-class="{{ $classes['idle'] }}"
                       aria-current="{{ $active ? 'page' : 'false' }}"
                       class="{{ $active ? $classes['active'] : $classes['idle'] }}">
                        {{ $level->label() }}
                        <span class="rounded-full bg-black/10 px-1.5 py-0.5 text-[11px] font-semibold tabular-nums {{ $active ? 'bg-white/20' : '' }}">{{ $levelCounts[$level->value] ?? 0 }}</span>
                    </a>
                @endforeach
            </nav>
            <label class="relative min-w-[16rem] flex-1 lg:max-w-xs">
                <span class="sr-only">Search students</span>
                <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-slate-400">
                    <x-icon name="search" class="h-4 w-4" />
                </span>
                <input id="student-search" type="search" value="{{ $search }}" placeholder="Search name or USN/ID"
                       class="h-10 w-full rounded-full border-0 bg-white pl-9 pr-4 text-sm text-slate-800 shadow-[0_8px_24px_rgba(15,23,42,0.06)] outline-none placeholder:text-slate-400 focus:ring-2 focus:ring-slate-200">
            </label>
        </div>
        <button type="button" data-student-form-panel class="inline-flex items-center justify-center gap-2 rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-400">
            <x-icon name="plus" class="h-4 w-4" /> Add student
        </button>
    </div>

    <div id="student-directory-host">
        @include('students._directory')
    </div>

    <div id="student-form-panel" class="student-form-panel {{ $openStudentForm ? 'is-open' : '' }}" aria-hidden="{{ $openStudentForm ? 'false' : 'true' }}">
        <div class="student-form-panel__backdrop" data-student-form-panel-close></div>
        <div class="student-form-panel__dialog" role="dialog" aria-modal="true" aria-labelledby="student-form-panel-title">
            <div class="mb-5 flex items-start justify-between gap-3">
                <div>
                    <p id="student-form-panel-title" class="text-base font-semibold tracking-tight text-slate-900">Add student</p>
                    <p class="mt-1 text-sm text-slate-400">Register a student for this school.</p>
                </div>
                <button type="button" class="rounded-full bg-slate-100 p-2 text-slate-500 hover:bg-slate-200 hover:text-slate-800" data-student-form-panel-close aria-label="Close">
                    <x-icon name="x" class="h-4 w-4" />
                </button>
            </div>
            <form method="POST" action="{{ route('students.store') }}" enctype="multipart/form-data" class="flex flex-col gap-5">
                @csrf
                @if ($errors->hasAny(['student_number', 'first_name', 'middle_name', 'last_name', 'gender', 'section_id', 'photo', 'is_active']))
                    <div class="rounded-xl bg-rose-50 px-4 py-3 text-sm text-rose-800" role="alert">
                        <p class="font-medium">Please correct the highlighted fields.</p>
                        <ul class="mt-2 list-disc space-y-1 pl-5">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                @include('students._form', ['idPrefix' => 'create-'])
                <div class="flex justify-end gap-3">
                    <button type="button" class="rounded-xl bg-slate-100 px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-200" data-student-form-panel-close>Cancel</button>
                    <button type="submit" class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800">Register student</button>
                </div>
            </form>
        </div>
    </div>

    <div id="student-panel" class="student-panel" aria-hidden="true">
        <div class="student-panel__backdrop" data-student-panel-close></div>
        <div class="student-panel__dialog" role="dialog" aria-modal="true" aria-labelledby="student-panel-title">
            <div class="mb-3 flex items-center justify-end">
                <p id="student-panel-title" class="sr-only">Student profile</p>
                <button type="button" class="rounded-full bg-white p-2.5 text-slate-500 shadow-[0_10px_24px_rgba(15,23,42,0.06)] hover:bg-slate-50 hover:text-slate-800" data-student-panel-close aria-label="Close">
                    <x-icon name="x" class="h-4 w-4" />
                </button>
            </div>
            <div data-student-panel-body>
                <div class="grid gap-5 lg:grid-cols-[19rem_minmax(0,1fr)]">
                    <div class="h-80 animate-pulse rounded-[1.75rem] bg-white/80"></div>
                    <div class="h-80 animate-pulse rounded-[1.75rem] bg-white/80"></div>
                </div>
            </div>
        </div>
    </div>
@endsection
