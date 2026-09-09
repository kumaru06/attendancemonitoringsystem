@extends('layouts.app', ['title' => 'Dashboard'])

@section('content')
    <div class="mb-6 rounded-2xl border border-slate-200 bg-white px-5 py-4 shadow-sm">
        <p class="text-sm font-medium text-slate-500">Current Philippine date and time</p>
        <p id="ph-clock" class="mt-1 text-xl font-semibold text-slate-900" data-iso="{{ $now->toIso8601String() }}">
            {{ $now->format('l, F j, Y g:i A') }}
        </p>
        <p class="text-xs text-slate-500">{{ $timezone }} (server time)</p>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        <x-stat-card label="Active students" :value="$totalActive" icon="users" hint="Current enrolled population" />
        <x-stat-card label="Present today" :value="$presentToday" icon="check" hint="Active students with a time-in" />
        <x-stat-card label="Not yet checked in" :value="$notYetCheckedIn" icon="clock" hint="Active students without a scan today" />
    </div>

    <div class="mt-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ($levelStats as $stat)
            <x-stat-card
                :label="$stat['level']->label()"
                :value="$stat['present'] . ' / ' . $stat['total']"
                icon="clipboard"
                hint="Present today / active students"
            />
        @endforeach
    </div>

    <div class="mt-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 px-5 py-4">
            <h2 class="text-sm font-semibold text-slate-900">Recent attendance</h2>
        </div>
        @if ($recent->isEmpty())
            <p class="px-5 py-10 text-center text-sm text-slate-500">No attendance has been recorded yet today.</p>
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
