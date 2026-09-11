@props(['label', 'value', 'icon' => 'clipboard', 'hint' => null, 'tone' => 'indigo', 'delta' => null])

@php
    $wells = [
        'indigo' => 'bg-indigo-50 text-indigo-600',
        'violet' => 'bg-violet-50 text-violet-600',
        'emerald' => 'bg-emerald-50 text-emerald-600',
        'rose' => 'bg-rose-50 text-rose-500',
        'sky' => 'bg-sky-50 text-sky-600',
    ];
    $well = $wells[$tone] ?? $wells['indigo'];
    $deltaUp = is_int($delta) && $delta >= 0;
@endphp

<div {{ $attributes->merge(['class' => 'rounded-3xl bg-white p-5 shadow-[0_12px_40px_rgba(15,23,42,0.05)]']) }}>
    <div class="flex items-start justify-between gap-3">
        <div class="min-w-0">
            <div class="flex items-center gap-2">
                <p class="text-sm font-medium text-slate-500">{{ $label }}</p>
                @if (is_int($delta))
                    <span class="rounded-full px-1.5 py-0.5 text-[11px] font-semibold {{ $deltaUp ? 'bg-emerald-50 text-emerald-600' : 'bg-rose-50 text-rose-600' }}">
                        {{ $deltaUp ? '+' : '' }}{{ $delta }}%
                    </span>
                @endif
            </div>
            <p class="mt-2 text-3xl font-semibold tracking-tight text-slate-900">{{ $value }}</p>
            @if ($hint)
                <p class="mt-1 text-xs text-slate-400">{{ $hint }}</p>
            @endif
        </div>
        <span class="flex h-11 w-11 items-center justify-center rounded-2xl {{ $well }}">
            <x-icon :name="$icon" class="h-5 w-5" />
        </span>
    </div>
</div>
