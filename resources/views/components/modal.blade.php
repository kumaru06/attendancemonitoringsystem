@props(['id', 'title'])

<div id="{{ $id }}" class="fixed inset-0 z-50 hidden items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="{{ $id }}-title">
    <div class="absolute inset-0 bg-slate-900/50" data-modal-close></div>
    <div class="relative w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">
        <h2 id="{{ $id }}-title" class="text-lg font-semibold text-slate-900">{{ $title }}</h2>
        <div class="mt-3 text-sm text-slate-600">
            {{ $slot }}
        </div>
    </div>
</div>
