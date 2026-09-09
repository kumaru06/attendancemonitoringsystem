@extends('layouts.app', ['title' => 'Students'])

@section('content')
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <nav class="flex flex-wrap gap-2" aria-label="School levels">
            <a href="{{ route('students.index', request()->except(['level', 'page'])) }}"
               class="rounded-full px-3.5 py-1.5 text-sm font-medium {{ request()->filled('level') ? 'bg-white text-slate-600 shadow-[0_8px_24px_rgba(15,23,42,0.06)] hover:bg-slate-50' : 'bg-slate-900 text-white' }}">
                All
            </a>
            @foreach ($levels as $level)
                <a href="{{ route('students.index', array_merge(request()->except('page'), ['level' => $level->value])) }}"
                   class="rounded-full px-3.5 py-1.5 text-sm font-medium {{ request('level') === $level->value ? 'bg-slate-900 text-white' : 'bg-white text-slate-600 shadow-[0_8px_24px_rgba(15,23,42,0.06)] hover:bg-slate-50' }}">
                    {{ $level->label() }}
                </a>
            @endforeach
        </nav>
        <a href="{{ route('students.create') }}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-400">
            <x-icon name="plus" class="h-4 w-4" /> Add student
        </a>
    </div>

    <form method="GET" class="mb-6 grid gap-3 rounded-3xl bg-white p-5 shadow-[0_12px_40px_rgba(15,23,42,0.06)] md:grid-cols-4">
        @if (request()->filled('level'))
            <input type="hidden" name="level" value="{{ request('level') }}">
        @endif
        <div class="md:col-span-2">
            <label for="search" class="mb-1 block text-xs font-medium text-slate-500">Search</label>
            <input id="search" name="search" value="{{ request('search') }}" placeholder="Name or student number"
                   class="w-full rounded-xl bg-slate-50 px-3 py-2.5 text-sm text-slate-800 outline-none ring-0 focus:bg-white focus:shadow-sm">
        </div>
        <div>
            <label for="section_id" class="mb-1 block text-xs font-medium text-slate-500">Section</label>
            <select id="section_id" name="section_id" class="w-full rounded-xl bg-slate-50 px-3 py-2.5 text-sm text-slate-800 outline-none focus:bg-white focus:shadow-sm">
                <option value="">All sections</option>
                <x-section-options :sections="$sections" :selected="request('section_id')" />
            </select>
        </div>
        <div>
            <label for="status" class="mb-1 block text-xs font-medium text-slate-500">Status</label>
            <select id="status" name="status" class="w-full rounded-xl bg-slate-50 px-3 py-2.5 text-sm text-slate-800 outline-none focus:bg-white focus:shadow-sm">
                <option value="">All</option>
                <option value="active" @selected(request('status') === 'active')>Active</option>
                <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
            </select>
        </div>
        <div class="md:col-span-4">
            <button type="submit" class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-medium text-white hover:bg-slate-800">Apply filters</button>
        </div>
    </form>

    <div class="rounded-3xl bg-white p-2 shadow-[0_12px_40px_rgba(15,23,42,0.06)] sm:p-3">
        @if ($students->isEmpty())
            <p class="px-5 py-16 text-center text-sm text-slate-500">No students match the current filters.</p>
        @else
            <div class="overflow-x-auto overflow-y-visible">
                <table class="min-w-full text-left text-sm">
                    <thead>
                        <tr class="text-[11px] font-medium uppercase tracking-wider text-slate-400">
                            <th class="px-4 py-3 font-medium">Student</th>
                            <th class="px-4 py-3 font-medium">Number</th>
                            <th class="px-4 py-3 font-medium">Level</th>
                            <th class="px-4 py-3 font-medium">Section</th>
                            <th class="px-4 py-3 font-medium">Status</th>
                            <th class="px-4 py-3 text-right font-medium">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($students as $student)
                            <tr class="text-slate-600 hover:bg-slate-50">
                                <td class="px-4 py-3.5 font-medium text-slate-900">{{ $student->full_name }}</td>
                                <td class="px-4 py-3.5 font-mono text-[13px]">{{ $student->student_number }}</td>
                                <td class="px-4 py-3.5">{{ $student->section?->level?->label() }}</td>
                                <td class="px-4 py-3.5">{{ $student->section?->name }}</td>
                                <td class="px-4 py-3.5">
                                    <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium {{ $student->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">
                                        {{ $student->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="relative px-4 py-3.5 text-right">
                                    <div class="inline-flex items-center justify-end gap-1.5">
                                        <a href="{{ route('students.show', $student) }}" data-student-panel class="inline-flex rounded-full bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-200">
                                            View
                                        </a>
                                        <details data-action-menu class="relative inline-block text-left">
                                            <summary class="inline-flex cursor-pointer list-none items-center rounded-full bg-slate-100 p-2 text-slate-600 hover:bg-slate-200 focus:outline-none focus:ring-2 focus:ring-slate-300 [&::-webkit-details-marker]:hidden">
                                                <span class="sr-only">More actions for {{ $student->full_name }}</span>
                                                <x-icon name="ellipsis" class="h-4 w-4" />
                                            </summary>
                                            <div class="absolute right-0 z-20 mt-1 w-36 rounded-xl bg-white py-1 text-left shadow-[0_16px_40px_rgba(15,23,42,0.12)]">
                                                <a href="{{ route('students.edit', $student) }}" class="block px-3 py-2 text-sm text-slate-700 hover:bg-slate-50">Edit</a>
                                            </div>
                                        </details>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="px-4 py-3">{{ $students->links() }}</div>
        @endif
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
