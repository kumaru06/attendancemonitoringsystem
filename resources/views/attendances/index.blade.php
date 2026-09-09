@extends('layouts.app', ['title' => 'Attendance'])

@section('content')
    <form method="GET" class="mb-5 grid gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm md:grid-cols-5">
        <div>
            <label for="from" class="mb-1 block text-xs font-medium text-slate-500">From</label>
            <input id="from" type="date" name="from" value="{{ $filters['from'] }}" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500">
        </div>
        <div>
            <label for="to" class="mb-1 block text-xs font-medium text-slate-500">To</label>
            <input id="to" type="date" name="to" value="{{ $filters['to'] }}" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500">
        </div>
        <div>
            <label for="section_id" class="mb-1 block text-xs font-medium text-slate-500">Section</label>
            <select id="section_id" name="section_id" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <option value="">All sections</option>
                @foreach ($sections as $section)
                    <option value="{{ $section->id }}" @selected(($filters['section_id'] ?? null) == $section->id)>{{ $section->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="md:col-span-2">
            <label for="search" class="mb-1 block text-xs font-medium text-slate-500">Search</label>
            <input id="search" name="search" value="{{ $filters['search'] }}" placeholder="Name or student number" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500">
        </div>
        <div class="flex flex-wrap gap-2 md:col-span-5">
            <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Apply filters</button>
            <a href="{{ route('attendances.export', request()->query()) }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                <x-icon name="download" class="h-4 w-4" /> Export CSV
            </a>
        </div>
    </form>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        @if ($attendances->isEmpty())
            <p class="px-5 py-12 text-center text-sm text-slate-500">No attendance records match the current filters.</p>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-5 py-3 font-medium">Student number</th>
                            <th class="px-5 py-3 font-medium">Student name</th>
                            <th class="px-5 py-3 font-medium">Section</th>
                            <th class="px-5 py-3 font-medium">Date</th>
                            <th class="px-5 py-3 font-medium">Time-in</th>
                            <th class="px-5 py-3 font-medium">Status</th>
                            <th class="px-5 py-3 font-medium">Recorded by</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($attendances as $row)
                            <tr>
                                <td class="px-5 py-3">{{ $row->student?->student_number }}</td>
                                <td class="px-5 py-3 font-medium text-slate-900">{{ $row->student?->full_name }}</td>
                                <td class="px-5 py-3">{{ $row->student?->section?->name }}</td>
                                <td class="px-5 py-3">{{ optional($row->attendance_date)->toDateString() }}</td>
                                <td class="px-5 py-3">{{ $row->time_in }}</td>
                                <td class="px-5 py-3">{{ $row->status }}</td>
                                <td class="px-5 py-3">{{ $row->recorder?->name }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-200 px-5 py-3">{{ $attendances->links() }}</div>
        @endif
    </div>
@endsection
