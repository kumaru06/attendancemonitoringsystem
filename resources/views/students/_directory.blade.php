@php
    $search = $search ?? '';
@endphp

<div id="student-directory" class="rounded-[2rem] bg-white shadow-[0_12px_40px_rgba(15,23,42,0.06)]">
    <div class="flex items-center justify-between gap-3 border-b border-slate-100 px-5 py-4 sm:px-6">
        <div>
            <h2 class="text-sm font-semibold text-slate-900">{{ $selectedLevel?->label() ?? 'All students' }}</h2>
            <p class="mt-0.5 text-xs text-slate-400">
                {{ $students->total() }} {{ $students->total() === 1 ? 'student' : 'students' }}
                @if ($search !== '')
                    matching “{{ $search }}”
                @endif
            </p>
        </div>
    </div>

    @if ($students->isEmpty())
        <p class="px-5 py-16 text-center text-sm text-slate-500">
            {{ $search !== '' ? 'No students match this search.' : ($selectedLevel ? 'No students in this level yet.' : 'No students registered yet.') }}
        </p>
    @else
        <div class="overflow-x-auto overflow-y-visible">
            <table class="w-full min-w-[64rem] text-left text-sm">
                <thead>
                    <tr class="text-[11px] font-medium uppercase tracking-wider text-slate-400">
                        <th class="px-5 py-3 text-left font-medium">Student</th>
                        <th class="px-5 py-3 text-left font-medium whitespace-nowrap">USN/ID Number</th>
                        <th class="px-5 py-3 text-left font-medium">Gender</th>
                        <th class="px-5 py-3 text-left font-medium">Level</th>
                        <th class="px-5 py-3 text-left font-medium">Section</th>
                        <th class="px-5 py-3 text-left font-medium">Status</th>
                        <th class="px-5 py-3 text-left font-medium">Face</th>
                        <th class="w-16 px-5 py-3 text-right font-medium">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($students as $student)
                        <tr class="text-slate-600 transition hover:bg-slate-50/80">
                            <td class="px-5 py-3.5 align-middle">
                                <div class="flex min-w-0 items-center gap-3">
                                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-slate-900 text-[11px] font-semibold text-white">
                                        {{ $student->initials }}
                                    </div>
                                    <p class="min-w-0 truncate font-medium text-slate-900">{{ $student->full_name }}</p>
                                </div>
                            </td>
                            <td class="px-5 py-3.5 align-middle whitespace-nowrap font-mono text-[13px]">{{ $student->student_number }}</td>
                            <td class="px-5 py-3.5 align-middle whitespace-nowrap">
                                @if ($student->gender)
                                    <span class="inline-flex rounded-full px-2.5 py-0.5 text-[11px] font-semibold {{ $student->gender === \App\Enums\StudentGender::Male ? 'bg-sky-50 text-sky-800' : 'bg-rose-50 text-rose-800' }}">
                                        {{ $student->gender->sf2Code() }}
                                    </span>
                                @else
                                    <span class="text-xs text-slate-400">—</span>
                                @endif
                            </td>
                            <td class="px-5 py-3.5 align-middle whitespace-nowrap">
                                <span class="inline-flex rounded-full px-2.5 py-0.5 text-[11px] font-semibold {{ $student->section?->level?->chipClass() ?? 'bg-slate-100 text-slate-600' }}">
                                    {{ $student->section?->level?->label() }}
                                </span>
                            </td>
                            <td class="px-5 py-3.5 align-middle whitespace-nowrap">{{ $student->section?->name }}</td>
                            <td class="px-5 py-3.5 align-middle whitespace-nowrap">
                                <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium {{ $student->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">
                                    {{ $student->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="px-5 py-3.5 align-middle whitespace-nowrap">
                                <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium {{ $student->hasEnrolledFace() ? 'bg-indigo-50 text-indigo-700' : 'bg-amber-50 text-amber-800' }}">
                                    {{ $student->hasEnrolledFace() ? 'Enrolled' : 'Not enrolled' }}
                                </span>
                            </td>
                            <td class="w-16 px-5 py-3.5 align-middle text-right">
                                <details data-action-menu class="relative inline-block text-left">
                                    <summary class="inline-flex cursor-pointer list-none items-center rounded-full bg-slate-100 p-2 text-slate-600 hover:bg-slate-200 focus:outline-none focus:ring-2 focus:ring-slate-300 [&::-webkit-details-marker]:hidden">
                                        <span class="sr-only">More actions for {{ $student->full_name }}</span>
                                        <x-icon name="ellipsis" class="h-4 w-4" />
                                    </summary>
                                    <div class="absolute right-0 z-20 mt-1 w-44 rounded-xl bg-white py-1 text-left shadow-[0_16px_40px_rgba(15,23,42,0.12)]">
                                        <a href="{{ route('students.show', $student) }}" data-student-panel class="block px-3 py-2 text-sm text-slate-700 hover:bg-slate-50">View</a>
                                        <a href="{{ route('students.edit', $student) }}" class="block px-3 py-2 text-sm text-slate-700 hover:bg-slate-50">Edit</a>
                                        @if ($student->hasEnrolledFace())
                                            <button type="button" class="block w-full px-3 py-2 text-left text-sm text-amber-800 hover:bg-amber-50" data-open-modal="reset-face-modal-{{ $student->id }}">Reset face</button>
                                        @endif
                                        <button type="button" class="block w-full px-3 py-2 text-left text-sm text-rose-700 hover:bg-rose-50" data-open-modal="delete-student-modal-{{ $student->id }}">Delete</button>
                                    </div>
                                </details>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="px-5 py-3" data-student-directory-pager>{{ $students->links() }}</div>
        @foreach ($students as $student)
            @if ($student->hasEnrolledFace())
                <x-modal id="reset-face-modal-{{ $student->id }}" title="Reset face recognition">
                    <p>This removes the enrolled face for {{ $student->full_name }}. They will not be accepted in Face mode until they enroll again on the scanner.</p>
                    <form method="POST" action="{{ route('students.face.reset', $student) }}" class="mt-5 flex justify-end gap-2">
                        @csrf
                        <input type="hidden" name="confirm" value="1">
                        <button type="button" class="rounded-xl bg-slate-100 px-4 py-2 text-sm font-medium text-slate-700" data-modal-close>Cancel</button>
                        <button type="submit" class="rounded-xl bg-amber-600 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-700">Reset face</button>
                    </form>
                </x-modal>
            @endif
            <x-modal id="delete-student-modal-{{ $student->id }}" title="Delete student">
                <p>This permanently deletes {{ $student->full_name }}, including their QR, enrolled face, photo, and attendance history.</p>
                <form method="POST" action="{{ route('students.destroy', $student) }}" class="mt-5 flex justify-end gap-2">
                    @csrf
                    @method('DELETE')
                    <button type="button" class="rounded-xl bg-slate-100 px-4 py-2 text-sm font-medium text-slate-700" data-modal-close>Cancel</button>
                    <button type="submit" class="rounded-xl bg-rose-600 px-4 py-2 text-sm font-semibold text-white hover:bg-rose-700">Delete student</button>
                </form>
            </x-modal>
        @endforeach
    @endif
</div>
