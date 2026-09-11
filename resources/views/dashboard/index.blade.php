@extends('layouts.app', ['title' => 'Dashboard'])

@section('content')
    <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-slate-900">Dashboard</h1>
            <p class="mt-1 text-sm text-slate-500">Welcome back, {{ $firstName }}! Here's what's happening today.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <div class="inline-flex items-center gap-2 rounded-2xl bg-white px-3 py-2 text-sm text-slate-600 shadow-[0_8px_24px_rgba(15,23,42,0.05)]">
                <x-icon name="calendar" class="h-4 w-4 text-indigo-500" />
                <span id="ph-clock" data-ph-clock data-clock-format="date" data-iso="{{ $now->toIso8601String() }}">{{ $now->format('l, F j, Y') }}</span>
            </div>
            <div class="inline-flex items-center gap-2 rounded-2xl bg-white px-3 py-2 text-sm text-slate-600 shadow-[0_8px_24px_rgba(15,23,42,0.05)]">
                <span data-ph-clock data-clock-format="time" data-iso="{{ $now->toIso8601String() }}">{{ $now->format('g:i A') }}</span>
                <span class="text-slate-400">| {{ $timezone }} ({{ $gmtLabel }})</span>
            </div>
            @if ($weather)
                <div class="inline-flex items-center gap-2 rounded-2xl bg-white px-3 py-2 text-sm text-slate-600 shadow-[0_8px_24px_rgba(15,23,42,0.05)]">
                    <x-icon name="sun" class="h-4 w-4 text-amber-500" />
                    <span class="font-medium text-slate-800">{{ $weather['temperature'] }}°C</span>
                    <span>{{ $weather['summary'] }}</span>
                </div>
            @endif
        </div>
    </div>

    <section class="relative overflow-hidden rounded-[1.75rem] bg-slate-900 text-white shadow-[0_20px_50px_rgba(15,23,42,0.18)]">
        @if ($bannerUrl)
            <img src="{{ $bannerUrl }}" alt="" class="absolute inset-0 h-full w-full object-cover">
            <div class="absolute inset-0 bg-slate-950/45"></div>
        @else
            <div class="absolute inset-0 bg-[radial-gradient(circle_at_20%_20%,rgba(99,102,241,0.45),transparent_42%),radial-gradient(circle_at_80%_10%,rgba(14,165,233,0.35),transparent_36%),linear-gradient(135deg,#0f172a,#1e3a8a_58%,#0f766e)]"></div>
        @endif
        <div class="relative grid gap-6 px-6 py-8 sm:px-8 lg:grid-cols-[1.2fr_0.8fr] lg:items-end">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-white/70">{{ $greeting }}</p>
                <h2 class="mt-2 max-w-xl text-3xl font-semibold tracking-tight sm:text-4xl">Keep Tracking, Keep Growing.</h2>
                <p class="mt-3 max-w-lg text-sm text-white/75">Accurate attendance today, a better tomorrow for every student.</p>
                <a href="{{ route('scanner.index') }}" class="mt-5 inline-flex items-center gap-2 rounded-full bg-indigo-500 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-400">
                    Scan Student
                    <x-icon name="chevron" class="h-4 w-4" />
                </a>
            </div>
            @if ($schoolName)
                <p class="max-w-xs justify-self-end text-right text-sm font-medium italic text-white/80">{{ $schoolName }}</p>
            @endif
        </div>
    </section>

    <div class="mt-5 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        <x-stat-card label="Active Students" :value="$totalActive" icon="users" tone="violet" hint="Current enrolled population" />
        <x-stat-card label="Present Today" :value="$presentToday" icon="calendar" tone="emerald" :delta="$presentDelta" hint="Active students with a time-in" />
        <x-stat-card label="Not Yet Checked In" :value="$notYetCheckedIn" icon="clock" tone="rose" hint="Active students without a scan today" />
    </div>

    <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5">
        @foreach ($levelStats as $stat)
            @php $tone = $stat['level']->dashboardCardTone(); @endphp
            <a href="{{ route('students.index', ['level' => $stat['level']->value]) }}"
               class="flex items-center justify-between gap-3 rounded-3xl bg-white p-4 shadow-[0_12px_40px_rgba(15,23,42,0.05)] hover:bg-slate-50">
                <span class="flex min-w-0 items-center gap-3">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl {{ $tone['icon'] }}">
                        <x-icon :name="$stat['level']->dashboardIcon()" class="h-5 w-5" />
                    </span>
                    <span class="min-w-0">
                        <span class="block text-sm font-semibold text-slate-900">{{ $stat['level']->label() }}</span>
                        <span class="block text-lg font-semibold tabular-nums text-slate-900">{{ $stat['present'] }} / {{ $stat['total'] }}</span>
                        <span class="block text-[11px] text-slate-400">Present today / active students</span>
                    </span>
                </span>
                <x-icon name="chevron" class="h-4 w-4 shrink-0 text-slate-300" />
            </a>
        @endforeach
    </div>

    <div class="mt-6 overflow-hidden rounded-3xl bg-white shadow-[0_12px_40px_rgba(15,23,42,0.05)]">
        <div class="flex items-center justify-between gap-3 px-5 py-4">
            <h2 class="flex items-center gap-2 text-sm font-semibold text-slate-900">
                <x-icon name="clock" class="h-4 w-4 text-indigo-500" />
                Recent Attendance
            </h2>
            <a href="{{ route('attendances.index') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-500">View All →</a>
        </div>
        @if ($recent->isEmpty())
            <div class="px-5 pb-12 pt-4 text-center">
                <span class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-indigo-50 text-indigo-400">
                    <x-icon name="search" class="h-7 w-7" />
                </span>
                <p class="mt-4 text-sm font-medium text-slate-700">No attendance has been recorded yet today.</p>
                <p class="mt-1 text-sm text-slate-400">Student scan records will appear here once available.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-5 py-3 font-medium">Student</th>
                            <th class="px-5 py-3 font-medium">Level</th>
                            <th class="px-5 py-3 font-medium">Section</th>
                            <th class="px-5 py-3 font-medium">Time-in</th>
                            <th class="px-5 py-3 font-medium">Recorded by</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($recent as $row)
                            <tr>
                                <td class="px-5 py-3">
                                    <div class="font-medium text-slate-900">{{ $row->student?->full_name }}</div>
                                    <div class="text-xs text-slate-500">{{ $row->student?->student_number }}</div>
                                </td>
                                <td class="px-5 py-3 text-slate-600">{{ $row->student?->section?->level?->label() }}</td>
                                <td class="px-5 py-3 text-slate-600">{{ $row->student?->section?->name }}</td>
                                <td class="px-5 py-3 text-slate-600">{{ $row->time_in }}</td>
                                <td class="px-5 py-3 text-slate-600">{{ $row->recorder?->recorderLabel() }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endsection
