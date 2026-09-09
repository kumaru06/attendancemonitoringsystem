<header class="sticky top-0 z-20 border-b border-slate-200 bg-white/90 backdrop-blur">
    <div class="flex h-16 items-center justify-between gap-3 px-4 sm:px-6">
        <div class="flex min-w-0 items-center gap-3">
            <button type="button" class="rounded-md p-2 text-slate-600 hover:bg-slate-100 lg:hidden" data-sidebar-open aria-label="Open menu">
                <x-icon name="menu" class="h-5 w-5" />
            </button>
            <div class="min-w-0">
                <h1 class="truncate text-base font-semibold text-slate-900">{{ $title ?? 'Dashboard' }}</h1>
                <p class="hidden truncate text-xs text-slate-500 sm:block">{{ now(config('attendance.timezone'))->format('l, F j, Y') }}</p>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <div class="hidden text-right sm:block">
                <p class="text-sm font-medium text-slate-900">{{ auth()->user()->name }}</p>
                <p class="text-xs text-slate-500">{{ auth()->user()->role->label() }}</p>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <x-icon name="logout" class="h-4 w-4" />
                    <span class="hidden sm:inline">Sign out</span>
                </button>
            </form>
        </div>
    </div>
</header>
