@php
    $user = auth()->user();
    $isAdmin = $user?->isAdmin() ?? false;
    $alertCount = (int) ($notYetCheckedIn ?? 0);
    $initials = collect(preg_split('/\s+/', trim((string) $user?->name)))
        ->filter()
        ->take(2)
        ->map(fn (string $part): string => mb_strtoupper(mb_substr($part, 0, 1)))
        ->implode('');
@endphp

<header class="sticky top-0 z-20 bg-white/80 backdrop-blur">
    <div class="flex h-16 items-center gap-3 px-4 sm:px-6">
        <div class="flex min-w-0 items-center gap-3">
            <button type="button" class="rounded-md p-2 text-slate-600 hover:bg-slate-100 lg:hidden" data-sidebar-open aria-label="Open menu">
                <x-icon name="menu" class="h-5 w-5" />
            </button>
            <div class="min-w-0 {{ request()->routeIs('dashboard') ? 'lg:hidden' : '' }}">
                <h1 class="truncate text-base font-semibold text-slate-900">{{ $title ?? 'Dashboard' }}</h1>
            </div>
        </div>

        @if ($isAdmin)
            <div class="flex min-w-0 flex-1 justify-center">
                <button type="button"
                        data-search-open
                        class="flex h-11 w-full max-w-xl items-center gap-3 rounded-full bg-slate-50 px-4 text-left text-sm text-slate-400 ring-1 ring-slate-200/80 hover:bg-white hover:ring-slate-300">
                    <x-icon name="search" class="h-4 w-4 shrink-0" />
                    <span class="min-w-0 flex-1 truncate">Search student, section, or scan...</span>
                    <kbd class="hidden rounded-md bg-white px-1.5 py-0.5 text-[11px] font-medium text-slate-400 ring-1 ring-slate-200 sm:inline">Ctrl + K</kbd>
                </button>
            </div>
        @else
            <div class="flex-1"></div>
        @endif

        <div class="flex items-center gap-2">
            @if ($isAdmin)
                <details data-action-menu class="relative">
                    <summary class="relative flex h-10 w-10 cursor-pointer list-none items-center justify-center rounded-full text-slate-500 hover:bg-slate-100 [&::-webkit-details-marker]:hidden" aria-label="Today's alerts">
                        <x-icon name="bell" class="h-5 w-5" />
                        @if ($alertCount > 0)
                            <span class="absolute right-1.5 top-1.5 h-2 w-2 rounded-full bg-rose-500 ring-2 ring-white"></span>
                        @endif
                    </summary>
                    <div class="absolute right-0 z-30 mt-2 w-72 overflow-hidden rounded-2xl bg-white py-2 shadow-[0_18px_50px_rgba(15,23,42,0.12)] ring-1 ring-slate-100">
                        <p class="px-4 py-2 text-xs font-semibold uppercase tracking-wide text-slate-400">Today</p>
                        <a href="{{ route('attendances.index') }}" class="flex items-start gap-3 px-4 py-3 text-sm hover:bg-slate-50">
                            <span class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-rose-50 text-rose-600">
                                <x-icon name="clock" class="h-4 w-4" />
                            </span>
                            <span>
                                <span class="block font-medium text-slate-900">{{ $alertCount }} not yet checked in</span>
                                <span class="mt-0.5 block text-xs text-slate-500">Open attendance to review today's register.</span>
                            </span>
                        </a>
                    </div>
                </details>
            @endif

            <details data-action-menu class="relative">
                <summary class="flex cursor-pointer list-none items-center gap-2 rounded-full py-1 pl-1 pr-2 hover:bg-slate-50 [&::-webkit-details-marker]:hidden">
                    <span class="flex h-9 w-9 items-center justify-center rounded-full bg-violet-100 text-xs font-semibold text-violet-700">{{ $initials ?: 'U' }}</span>
                    <span class="hidden text-left sm:block">
                        <span class="block text-sm font-medium text-slate-900">{{ $user?->name }}</span>
                        <span class="block text-xs text-slate-500">{{ $user?->role->label() }}</span>
                    </span>
                    <x-icon name="chevron-down" class="hidden h-4 w-4 text-slate-400 sm:block" />
                </summary>
                <div class="absolute right-0 z-30 mt-2 w-48 overflow-hidden rounded-2xl bg-white py-1 shadow-[0_18px_50px_rgba(15,23,42,0.12)] ring-1 ring-slate-100">
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="flex w-full items-center gap-2 px-4 py-2.5 text-left text-sm text-slate-700 hover:bg-slate-50">
                            <x-icon name="logout" class="h-4 w-4" />
                            Sign out
                        </button>
                    </form>
                </div>
            </details>
        </div>
    </div>
</header>

@if ($isAdmin)
    <div id="command-palette" class="fixed inset-0 z-50 hidden" role="dialog" aria-modal="true" aria-labelledby="command-palette-title" data-search-url="{{ route('search') }}">
        <div class="absolute inset-0 bg-slate-900/40" data-search-close></div>
        <div class="relative mx-auto mt-24 w-full max-w-lg px-4">
            <div class="overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-slate-100">
                <p id="command-palette-title" class="sr-only">Search</p>
                <div class="flex items-center gap-3 border-b border-slate-100 px-4">
                    <x-icon name="search" class="h-4 w-4 text-slate-400" />
                    <input id="command-palette-input" type="search" placeholder="Search student, section, or page"
                           class="h-12 w-full border-0 bg-transparent text-sm text-slate-800 outline-none placeholder:text-slate-400" autocomplete="off">
                    <button type="button" class="text-xs font-medium text-slate-400 hover:text-slate-600" data-search-close>Esc</button>
                </div>
                <div id="command-palette-results" class="max-h-80 overflow-y-auto py-2 text-sm"></div>
            </div>
        </div>
    </div>
@endif
