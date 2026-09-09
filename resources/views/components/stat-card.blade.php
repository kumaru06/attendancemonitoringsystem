@props(['label', 'value', 'icon' => 'clipboard', 'hint' => null])

<div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
    <div class="flex items-start justify-between gap-3">
        <div>
            <p class="text-sm font-medium text-slate-500">{{ $label }}</p>
            <p class="mt-2 text-3xl font-semibold tracking-tight text-slate-900">{{ $value }}</p>
            @if ($hint)
                <p class="mt-1 text-xs text-slate-500">{{ $hint }}</p>
            @endif
        </div>
        <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600">
            <x-icon :name="$icon" class="h-5 w-5" />
        </span>
    </div>
</div>
