@if (session('success'))
    <div class="mb-4 flex items-start gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800" role="status">
        <x-icon name="check" class="mt-0.5 h-5 w-5 shrink-0" />
        <p>{{ session('success') }}</p>
    </div>
@endif

@if (session('error'))
    <div class="mb-4 flex items-start gap-3 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800" role="alert">
        <x-icon name="alert" class="mt-0.5 h-5 w-5 shrink-0" />
        <p>{{ session('error') }}</p>
    </div>
@endif

@if ($errors->any() && ! request()->routeIs('login'))
    <div class="mb-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800" role="alert">
        <p class="font-medium">Please correct the highlighted fields.</p>
        <ul class="mt-2 list-disc space-y-1 pl-5">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
