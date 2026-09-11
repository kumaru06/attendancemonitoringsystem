@php
    $user = auth()->user();
    $links = match (true) {
        $user?->isSuperAdmin() => [
            ['route' => 'users.index', 'label' => 'Accounts', 'icon' => 'cog', 'match' => 'users.*'],
        ],
        $user?->isAdmin() => [
            ['route' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'home', 'match' => 'dashboard'],
            ['route' => 'scanner.index', 'label' => 'Scanner', 'icon' => 'qr', 'match' => 'scanner.*'],
            ['route' => 'students.index', 'label' => 'Students', 'icon' => 'users', 'match' => 'students.*'],
            ['route' => 'sections.index', 'label' => 'Sections', 'icon' => 'clipboard', 'match' => 'sections.*'],
            ['route' => 'attendances.index', 'label' => 'Attendance', 'icon' => 'clock', 'match' => 'attendances.*'],
            ['route' => 'settings.edit', 'label' => 'Settings', 'icon' => 'cog', 'match' => 'settings.*'],
        ],
        default => [
            ['route' => 'scanner.index', 'label' => 'Scanner', 'icon' => 'qr', 'match' => 'scanner.*'],
        ],
    };
@endphp

<aside id="app-sidebar" class="fixed inset-y-0 left-0 z-40 flex w-72 -translate-x-full flex-col bg-[#0B1224] text-slate-100 transition-transform lg:translate-x-0">
    <div class="flex shrink-0 items-center gap-3 px-5 pb-5 pt-6">
        <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-[#6D5EF6] text-white">
            <x-icon name="id-card" class="h-5 w-5" />
        </span>
        <div class="min-w-0">
            <p class="truncate text-[15px] font-semibold leading-tight">{{ config('app.name') }}</p>
            <p class="truncate text-[11px] text-slate-400">Daily Entrance Monitoring</p>
        </div>
        <button type="button" class="ml-auto rounded-md p-1 text-slate-400 hover:bg-white/10 lg:hidden" data-sidebar-close aria-label="Close menu">
            <x-icon name="x" class="h-5 w-5" />
        </button>
    </div>

    <nav class="min-h-0 flex-1 space-y-1 overflow-y-auto px-3 pb-4" aria-label="Primary">
        @foreach ($links as $link)
            @php $active = request()->routeIs($link['match']); @endphp
            <a href="{{ route($link['route']) }}"
               class="flex items-center gap-3 rounded-2xl px-4 py-3 text-sm font-medium transition {{ $active ? 'bg-[#6D5EF6] text-white shadow-[0_10px_24px_rgba(109,94,246,0.35)]' : 'text-slate-400 hover:bg-white/5 hover:text-white' }}">
                <x-icon :name="$link['icon']" class="h-5 w-5 shrink-0" />
                <span>{{ $link['label'] }}</span>
            </a>
        @endforeach
    </nav>

    <div class="shrink-0 space-y-4 px-4 pb-5 pt-2">
        <blockquote class="rounded-2xl bg-white/5 px-4 py-3 text-sm leading-relaxed text-slate-300">
            <p>“Consistent attendance builds brighter futures.”</p>
        </blockquote>
        <p class="px-1 text-[11px] leading-5 text-slate-500">
            v1.0.0<br>
            © {{ now()->year }} {{ config('app.name') }}
        </p>
    </div>
</aside>

<div id="sidebar-backdrop" class="fixed inset-0 z-30 hidden bg-slate-900/50 lg:hidden" data-sidebar-close></div>
