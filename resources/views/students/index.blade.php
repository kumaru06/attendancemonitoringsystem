@extends('layouts.app', ['title' => 'Students'])

@section('content')
    <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <p class="text-sm text-slate-500">Search, filter, and manage registered students.</p>
        <a href="{{ route('students.create') }}" class="inline-flex items-center justify-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500">
            <x-icon name="plus" class="h-4 w-4" /> Add student
        </a>
    </div>

    <form method="GET" class="mb-5 grid gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm md:grid-cols-4">
        <div class="md:col-span-2">
            <label for="search" class="mb-1 block text-xs font-medium text-slate-500">Search</label>
            <input id="search" name="search" value="{{ request('search') }}" placeholder="Name or student number"
                   class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500">
        </div>
        <div>
            <label for="section_id" class="mb-1 block text-xs font-medium text-slate-500">Section</label>
            <select id="section_id" name="section_id" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <option value="">All sections</option>
                @foreach ($sections as $section)
                    <option value="{{ $section->id }}" @selected(request('section_id') == $section->id)>{{ $section->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="status" class="mb-1 block text-xs font-medium text-slate-500">Status</label>
            <select id="status" name="status" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <option value="">All</option>
                <option value="active" @selected(request('status') === 'active')>Active</option>
                <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
            </select>
        </div>
        <div class="md:col-span-4">
            <button type="submit" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Apply filters</button>
        </div>
    </form>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        @if ($students->isEmpty())
            <p class="px-5 py-12 text-center text-sm text-slate-500">No students match the current filters.</p>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-5 py-3 font-medium">Student</th>
                            <th class="px-5 py-3 font-medium">Number</th>
                            <th class="px-5 py-3 font-medium">Section</th>
                            <th class="px-5 py-3 font-medium">Status</th>
                            <th class="px-5 py-3 font-medium"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($students as $student)
                            <tr>
                                <td class="px-5 py-3 font-medium text-slate-900">{{ $student->full_name }}</td>
                                <td class="px-5 py-3 text-slate-600">{{ $student->student_number }}</td>
                                <td class="px-5 py-3 text-slate-600">{{ $student->section?->name }}</td>
                                <td class="px-5 py-3">
                                    <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium {{ $student->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">
                                        {{ $student->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="px-5 py-3 text-right">
                                    <a href="{{ route('students.show', $student) }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-700">View</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-200 px-5 py-3">{{ $students->links() }}</div>
        @endif
    </div>
@endsection
