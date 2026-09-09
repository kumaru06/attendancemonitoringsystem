@extends('layouts.app', ['title' => $student->full_name])

@section('content')
    <div class="grid gap-6 lg:grid-cols-3">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm lg:col-span-1">
            <div class="flex flex-col items-center text-center">
                @if ($student->photo_path)
                    <img src="{{ route('students.photo', $student) }}" alt="{{ $student->full_name }}" class="h-36 w-36 rounded-2xl object-cover ring-1 ring-slate-200">
                @else
                    <div class="flex h-36 w-36 items-center justify-center rounded-2xl bg-slate-100 text-slate-400">
                        <x-icon name="user" class="h-12 w-12" />
                    </div>
                @endif
                <h2 class="mt-4 text-lg font-semibold text-slate-900">{{ $student->full_name }}</h2>
                <p class="text-sm text-slate-500">{{ $student->student_number }}</p>
                <p class="mt-1 text-sm text-slate-600">{{ $student->section?->name }}</p>
                <span class="mt-3 inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium {{ $student->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">
                    {{ $student->is_active ? 'Active' : 'Inactive' }}
                </span>
            </div>

            <div class="mt-6 grid gap-2">
                <a href="{{ route('students.edit', $student) }}" class="rounded-lg border border-slate-300 px-4 py-2 text-center text-sm font-medium text-slate-700 hover:bg-slate-50">Edit student</a>
                <a href="{{ route('students.qr', $student) }}" target="_blank" class="inline-flex items-center justify-center gap-2 rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                    <x-icon name="print" class="h-4 w-4" /> Print QR card
                </a>
                <a href="{{ route('students.qr.download', $student) }}" class="inline-flex items-center justify-center gap-2 rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                    <x-icon name="download" class="h-4 w-4" /> Download QR
                </a>
                <button type="button" class="inline-flex items-center justify-center gap-2 rounded-lg bg-amber-50 px-4 py-2 text-sm font-medium text-amber-800 hover:bg-amber-100" data-open-modal="replace-qr-modal">
                    <x-icon name="refresh" class="h-4 w-4" /> Replace QR
                </button>
                <button type="button" class="inline-flex items-center justify-center gap-2 rounded-lg px-4 py-2 text-sm font-medium {{ $student->is_active ? 'bg-rose-50 text-rose-800 hover:bg-rose-100' : 'bg-emerald-50 text-emerald-800 hover:bg-emerald-100' }}" data-open-modal="status-modal">
                    {{ $student->is_active ? 'Deactivate student' : 'Activate student' }}
                </button>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm lg:col-span-2">
            <div class="border-b border-slate-200 px-5 py-4">
                <h3 class="text-sm font-semibold text-slate-900">Attendance history</h3>
            </div>
            @if ($attendances->isEmpty())
                <p class="px-5 py-10 text-center text-sm text-slate-500">No attendance records yet.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full text-left text-sm">
                        <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-5 py-3 font-medium">Date</th>
                                <th class="px-5 py-3 font-medium">Time-in</th>
                                <th class="px-5 py-3 font-medium">Status</th>
                                <th class="px-5 py-3 font-medium">Recorded by</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($attendances as $row)
                                <tr>
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
    </div>

    <x-modal id="replace-qr-modal" title="Replace QR credential">
        <p>This will immediately revoke the current QR. The previous card will no longer record attendance.</p>
        <form method="POST" action="{{ route('students.qr.replace', $student) }}" class="mt-5 flex justify-end gap-2">
            @csrf
            <input type="hidden" name="confirm" value="1">
            <button type="button" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium" data-modal-close>Cancel</button>
            <button type="submit" class="rounded-lg bg-amber-600 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-700">Replace QR</button>
        </form>
    </x-modal>

    <x-modal id="status-modal" title="{{ $student->is_active ? 'Deactivate student' : 'Activate student' }}">
        <p>
            @if ($student->is_active)
                Attendance history stays on file. This student will no longer be accepted at the scanner.
            @else
                This student will be accepted again at the scanner.
            @endif
        </p>
        <form method="POST" action="{{ route('students.update', $student) }}" class="mt-5 flex justify-end gap-2">
            @csrf
            @method('PUT')
            <input type="hidden" name="student_number" value="{{ $student->student_number }}">
            <input type="hidden" name="first_name" value="{{ $student->first_name }}">
            <input type="hidden" name="middle_name" value="{{ $student->middle_name }}">
            <input type="hidden" name="last_name" value="{{ $student->last_name }}">
            <input type="hidden" name="section_id" value="{{ $student->section_id }}">
            @unless ($student->is_active)
                <input type="hidden" name="is_active" value="1">
            @endunless
            <button type="button" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium" data-modal-close>Cancel</button>
            <button type="submit" class="rounded-lg px-4 py-2 text-sm font-semibold text-white {{ $student->is_active ? 'bg-rose-600 hover:bg-rose-700' : 'bg-emerald-600 hover:bg-emerald-700' }}">
                {{ $student->is_active ? 'Deactivate' : 'Activate' }}
            </button>
        </form>
    </x-modal>
@endsection
