<tr class="border-t border-slate-100">
    <th class="sticky left-0 z-10 bg-white px-3 py-1.5 text-left text-[13px] font-medium text-slate-900" scope="row">{{ $row['name'] }}</th>
    @foreach ($days as $day)
        @php
            $mark = $row['marks'][$day['number']] ?? '';
            $dataMark = $mark === '(x)' ? 'absent' : ($mark === 'present' ? 'present' : 'blank');
            $display = $mark === '(x)' ? '(x)' : '';
        @endphp
        <td data-student="{{ $row['student_id'] }}" data-day="{{ $day['number'] }}" data-mark="{{ $dataMark }}" class="px-1 py-1.5 text-center font-medium {{ $day['is_weekend'] ? 'bg-slate-50' : '' }} {{ $day['is_today'] ? 'bg-indigo-50' : '' }} {{ $dataMark === 'absent' ? 'text-rose-600' : ($day['is_weekend'] ? 'text-slate-300' : 'text-slate-500') }}">{{ $display }}</td>
    @endforeach
    <td class="px-2 py-1.5 text-center text-slate-700">{{ $row['absent_count'] ?: '' }}</td>
    <td class="px-2 py-1.5 text-center text-slate-400"></td>
    <td class="px-3 py-1.5 text-slate-400"></td>
</tr>
