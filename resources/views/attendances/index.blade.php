@extends('layouts.app', ['title' => 'Attendance', 'wide' => true])

@section('content')
    <form method="GET" action="{{ route('attendances.index') }}" data-register-filter class="mb-5 grid gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm md:grid-cols-6">
        <div>
            <label for="month" class="mb-1 block text-xs font-medium text-slate-500">Month</label>
            <input id="month" type="month" name="month" value="{{ $filters['month'] }}" data-register-refresh class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500">
        </div>
        <div>
            <label for="level" class="mb-1 block text-xs font-medium text-slate-500">Level</label>
            <select id="level" name="level" data-register-refresh class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <option value="">All levels</option>
                @foreach ($levels as $level)
                    <option value="{{ $level->value }}" @selected(($filters['level'] ?? null) === $level->value)>{{ $level->label() }}</option>
                @endforeach
            </select>
        </div>
        <div class="md:col-span-2">
            <label for="section_id" class="mb-1 block text-xs font-medium text-slate-500">Section</label>
            <select id="section_id" name="section_id" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <option value="" disabled @selected(! ($filters['section_id'] ?? null))>Choose a section</option>
                <x-section-options :sections="$sections" :selected="$filters['section_id'] ?? null" />
            </select>
            @error('section_id')
                <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
            @enderror
        </div>
        <div class="flex flex-wrap items-end gap-2 md:col-span-2">
            <button type="submit" @disabled($sections->isEmpty()) class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700 disabled:cursor-not-allowed disabled:bg-slate-300">Open register</button>
            @if ($register)
                <a href="{{ route('attendances.sf2', request()->query()) }}" class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700">
                    <x-icon name="download" class="h-4 w-4" /> Export SF2
                </a>
            @endif
            <a href="{{ route('attendances.export', request()->query()) }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                <x-icon name="download" class="h-4 w-4" /> Export CSV
            </a>
        </div>
    </form>

    @if (! $register)
        <div class="rounded-2xl border border-dashed border-slate-300 bg-white px-5 py-16 text-center shadow-sm">
            @if ($sections->isEmpty())
                <p class="text-sm font-medium text-slate-700">No sections yet.</p>
                <p class="mt-1 text-sm text-slate-500">Add a section first, then open the monthly register from here.</p>
                <a href="{{ route('sections.index') }}" class="mt-4 inline-flex rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">Add section</a>
            @else
                <p class="text-sm font-medium text-slate-700">Choose a section to open SF2.</p>
                <p class="mt-1 text-sm text-slate-500">Pick a section in the list above. The register opens as soon as you select it.</p>
            @endif
        </div>
    @else
        <div class="mb-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                <p class="text-xs font-medium text-slate-500">School year</p>
                <p class="mt-1 text-sm font-semibold text-slate-900">{{ $register->schoolYear }} · {{ $register->monthLabel }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                <p class="text-xs font-medium text-slate-500">Enrolment (M / F)</p>
                <p class="mt-1 text-sm font-semibold text-slate-900">{{ $register->enrolment }} ({{ $register->maleEnrolment }} / {{ $register->femaleEnrolment }})</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                <p class="text-xs font-medium text-slate-500">Average daily attendance</p>
                <p class="mt-1 text-sm font-semibold text-slate-900">{{ number_format($register->averageDailyAttendance, 2) }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                <p class="text-xs font-medium text-slate-500">% attendance</p>
                <p class="mt-1 text-sm font-semibold text-slate-900">{{ number_format($register->attendancePercentage, 2) }}%</p>
            </div>
        </div>

        @if (count($register->ungendered) > 0)
            <div class="mb-4 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                <p class="font-medium">{{ count($register->ungendered) }} {{ count($register->ungendered) === 1 ? 'learner has' : 'learners have' }} no gender and {{ count($register->ungendered) === 1 ? 'is' : 'are' }} omitted from Male/Female totals until edited.</p>
                <p class="mt-1 text-amber-800">{{ collect($register->ungendered)->pluck('name')->implode('; ') }}</p>
            </div>
        @endif

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full border-collapse text-left text-xs">
                    <thead>
                        <tr class="bg-slate-50 text-slate-500">
                            <th class="sticky left-0 z-20 min-w-56 border-b border-slate-200 bg-slate-50 px-3 py-2 text-[11px] font-medium uppercase tracking-wide">LEARNER'S NAME</th>
                            @foreach ($register->days as $day)
                                <th class="min-w-8 border-b border-slate-200 px-1 py-1 text-center font-semibold {{ $day['is_weekend'] ? 'bg-slate-100 text-slate-400' : '' }} {{ $day['is_today'] ? 'bg-indigo-50 text-indigo-700' : '' }}">{{ $day['number'] }}</th>
                            @endforeach
                            <th class="min-w-14 border-b border-slate-200 px-2 py-2 text-center text-[11px] font-medium uppercase tracking-wide">Absent</th>
                            <th class="min-w-14 border-b border-slate-200 px-2 py-2 text-center text-[11px] font-medium uppercase tracking-wide">Tardy</th>
                            <th class="min-w-24 border-b border-slate-200 px-3 py-2 text-[11px] font-medium uppercase tracking-wide">Remarks</th>
                        </tr>
                        <tr class="bg-white text-slate-400">
                            <th class="sticky left-0 z-20 border-b border-slate-200 bg-white px-3 py-1 text-[10px] font-medium uppercase tracking-wide text-slate-400">Last, First, Middle</th>
                            @foreach ($register->days as $day)
                                <th class="border-b border-slate-200 px-1 py-1 text-center text-[10px] font-semibold {{ $day['is_weekend'] ? 'bg-slate-100 text-slate-300' : '' }} {{ $day['is_today'] ? 'bg-indigo-50 text-indigo-500' : '' }}">{{ $day['weekday_code'] }}</th>
                            @endforeach
                            <th class="border-b border-slate-200"></th>
                            <th class="border-b border-slate-200"></th>
                            <th class="border-b border-slate-200"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($register->males as $row)
                            @include('attendances._register-row', ['row' => $row, 'days' => $register->days])
                        @endforeach
                        <tr class="bg-sky-50 font-semibold text-sky-950">
                            <th class="sticky left-0 z-10 bg-sky-50 px-3 py-2 text-left">MALE | TOTAL Per Day</th>
                            @foreach ($register->days as $day)
                                <td class="px-1 py-2 text-center {{ $day['is_weekend'] ? 'text-slate-400' : '' }}">{{ $register->maleDailyPresent[$day['number']] }}</td>
                            @endforeach
                            <td class="px-2 py-2 text-center">{{ collect($register->males)->sum('absent_count') }}</td>
                            <td class="px-2 py-2 text-center"></td>
                            <td class="px-3 py-2"></td>
                        </tr>
                        @foreach ($register->females as $row)
                            @include('attendances._register-row', ['row' => $row, 'days' => $register->days])
                        @endforeach
                        <tr class="bg-rose-50 font-semibold text-rose-950">
                            <th class="sticky left-0 z-10 bg-rose-50 px-3 py-2 text-left">FEMALE | TOTAL Per Day</th>
                            @foreach ($register->days as $day)
                                <td class="px-1 py-2 text-center {{ $day['is_weekend'] ? 'text-slate-400' : '' }}">{{ $register->femaleDailyPresent[$day['number']] }}</td>
                            @endforeach
                            <td class="px-2 py-2 text-center">{{ collect($register->females)->sum('absent_count') }}</td>
                            <td class="px-2 py-2 text-center"></td>
                            <td class="px-3 py-2"></td>
                        </tr>
                        <tr class="bg-slate-800 font-semibold text-white">
                            <th class="sticky left-0 z-10 bg-slate-800 px-3 py-2 text-left">Combined TOTAL Per Day</th>
                            @foreach ($register->days as $day)
                                <td class="px-1 py-2 text-center">{{ $register->combinedDailyPresent[$day['number']] }}</td>
                            @endforeach
                            <td class="px-2 py-2 text-center">{{ collect($register->males)->sum('absent_count') + collect($register->females)->sum('absent_count') }}</td>
                            <td class="px-2 py-2 text-center"></td>
                            <td class="px-3 py-2"></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    @endif
@endsection

@push('scripts')
    <script>
        (() => {
            const form = document.querySelector('[data-register-filter]');

            if (! form) {
                return;
            }

            const section = form.querySelector('#section_id');

            form.querySelectorAll('[data-register-refresh]').forEach((field) => {
                field.addEventListener('change', () => {
                    section?.removeAttribute('required');
                    form.submit();
                });
            });

            section?.addEventListener('change', () => {
                form.requestSubmit();
            });
        })();
    </script>
@endpush
