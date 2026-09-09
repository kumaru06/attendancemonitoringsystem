@php
    $attendanceRows = $attendances;
    $attendanceCount = method_exists($attendanceRows, 'total')
        ? $attendanceRows->total()
        : $attendanceRows->count();
    $presentStatus = (string) config('attendance.status_present');
@endphp

<div class="grid gap-5 lg:grid-cols-[19rem_minmax(0,1fr)]">
    <aside class="rounded-[1.75rem] bg-white p-6 shadow-[0_18px_40px_rgba(15,23,42,0.05)] sm:p-7">
        <div class="flex flex-col items-center text-center">
            @if ($student->photo_path)
                <img src="{{ route('students.photo', $student) }}" alt="{{ $student->full_name }}" class="h-28 w-28 rounded-full object-cover shadow-[0_16px_40px_rgba(15,23,42,0.12)]">
            @else
                <div class="flex h-28 w-28 items-center justify-center rounded-full bg-slate-100 text-2xl font-semibold tracking-[0.2em] text-slate-500">
                    {{ $student->initials }}
                </div>
            @endif

            <h2 class="mt-5 text-[1.35rem] font-semibold tracking-tight text-slate-900">{{ $student->full_name }}</h2>
            <p class="mt-1 font-mono text-[13px] tracking-wide text-slate-400">{{ $student->student_number }}</p>
            <p class="mt-3 text-sm text-slate-500">{{ $student->section?->labeledName() }}</p>
            <p class="mt-3">
                <span class="inline-flex rounded-full px-2.5 py-1 text-[11px] font-medium {{ $student->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">
                    {{ $student->is_active ? 'Active' : 'Inactive' }}
                </span>
            </p>
        </div>

        <div class="mt-8 flex flex-col gap-3">
            <a href="{{ route('students.edit', $student) }}" class="rounded-2xl bg-slate-900 px-4 py-2.5 text-center text-sm font-medium text-white hover:bg-slate-800">Edit student</a>

            <div class="grid grid-cols-3 gap-1.5 rounded-[1.35rem] bg-slate-50 p-1.5">
                <a href="{{ route('students.qr', $student) }}" target="_blank" class="inline-flex flex-col items-center gap-1.5 rounded-2xl px-2 py-3 text-[11px] font-medium text-slate-600 hover:bg-white hover:text-slate-900">
                    <x-icon name="print" class="h-4 w-4" />
                    Print
                </a>
                <a href="{{ route('students.qr.download', $student) }}" class="inline-flex flex-col items-center gap-1.5 rounded-2xl px-2 py-3 text-[11px] font-medium text-slate-600 hover:bg-white hover:text-slate-900">
                    <x-icon name="download" class="h-4 w-4" />
                    Download
                </a>
                <button type="button" class="inline-flex flex-col items-center gap-1.5 rounded-2xl px-2 py-3 text-[11px] font-medium text-amber-800 hover:bg-white" data-open-modal="replace-qr-modal">
                    <x-icon name="refresh" class="h-4 w-4" />
                    Replace
                </button>
            </div>

            <button type="button" class="rounded-2xl px-4 py-2.5 text-sm font-medium {{ $student->is_active ? 'bg-rose-50 text-rose-800 hover:bg-rose-100' : 'bg-emerald-50 text-emerald-800 hover:bg-emerald-100' }}" data-open-modal="status-modal">
                {{ $student->is_active ? 'Deactivate student' : 'Activate student' }}
            </button>
        </div>
    </aside>

    <section class="min-w-0 rounded-[1.75rem] bg-white p-6 shadow-[0_18px_40px_rgba(15,23,42,0.05)] sm:p-7">
        <div class="flex items-end justify-between gap-3">
            <div>
                <h3 class="text-base font-semibold tracking-tight text-slate-900">Attendance history</h3>
                <p class="mt-1 text-sm text-slate-400">School-gate time-in records</p>
            </div>
            @if ($attendanceCount > 0)
                <p class="text-xs font-medium text-slate-400">{{ $attendanceCount }} {{ $attendanceCount === 1 ? 'day' : 'days' }}</p>
            @endif
        </div>

        @if ($attendanceRows->isEmpty())
            <div class="mt-10 flex flex-col items-center justify-center rounded-[1.5rem] bg-slate-50 px-6 py-14 text-center">
                <p class="text-sm font-medium text-slate-600">No attendance yet</p>
                <p class="mt-1 text-sm text-slate-400">Time-ins from the scanner will appear here.</p>
            </div>
        @else
            <div class="student-history mt-5 max-h-[min(32rem,54vh)] overflow-y-auto pr-1">
                <div class="sticky top-0 z-10 grid grid-cols-4 gap-3 rounded-2xl bg-slate-50 px-4 py-2.5 text-[11px] font-medium uppercase tracking-[0.16em] text-slate-400">
                    <span>Date</span>
                    <span>Time-in</span>
                    <span>Status</span>
                    <span>Recorded by</span>
                </div>
                <ul class="mt-2 flex flex-col gap-1">
                    @foreach ($attendanceRows as $row)
                        <li class="grid grid-cols-4 items-center gap-3 rounded-2xl px-4 py-3 text-sm {{ $loop->even ? 'bg-slate-50/80' : '' }} hover:bg-slate-50">
                            <time class="font-medium text-slate-800" datetime="{{ optional($row->attendance_date)->toDateString() }}">
                                {{ optional($row->attendance_date)->format('M j, Y') }}
                            </time>
                            <span class="tabular-nums text-slate-500">
                                {{ $row->time_in ? \Illuminate\Support\Carbon::parse($row->time_in)->format('g:i A') : '—' }}
                            </span>
                            <span>
                                <span class="inline-flex rounded-full px-2.5 py-0.5 text-[11px] font-medium {{ $row->status === $presentStatus ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">
                                    {{ $row->status }}
                                </span>
                            </span>
                            <span class="text-slate-500">{{ $row->recorder?->recorderLabel() }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
            @if (method_exists($attendanceRows, 'hasPages') && $attendanceRows->hasPages())
                <div class="mt-4">{{ $attendanceRows->links() }}</div>
            @endif
        @endif
    </section>
</div>

<x-modal id="replace-qr-modal" title="Replace QR credential">
    <p>This will immediately revoke the current QR. The previous card will no longer record attendance.</p>
    <form method="POST" action="{{ route('students.qr.replace', $student) }}" class="mt-5 flex justify-end gap-2">
        @csrf
        <input type="hidden" name="confirm" value="1">
        <button type="button" class="rounded-xl bg-slate-100 px-4 py-2 text-sm font-medium text-slate-700" data-modal-close>Cancel</button>
        <button type="submit" class="rounded-xl bg-amber-600 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-700">Replace QR</button>
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
        <button type="button" class="rounded-xl bg-slate-100 px-4 py-2 text-sm font-medium text-slate-700" data-modal-close>Cancel</button>
        <button type="submit" class="rounded-xl px-4 py-2 text-sm font-semibold text-white {{ $student->is_active ? 'bg-rose-600 hover:bg-rose-700' : 'bg-emerald-600 hover:bg-emerald-700' }}">
            {{ $student->is_active ? 'Deactivate' : 'Activate' }}
        </button>
    </form>
</x-modal>
