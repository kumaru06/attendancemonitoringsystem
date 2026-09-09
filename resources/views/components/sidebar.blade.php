@php
    $links = auth()->user()?->isAdmin()
        ? [
            ['route' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'home', 'match' => 'dashboard'],
            ['route' => 'scanner.index', 'label' => 'Scanner', 'icon' => 'camera', 'match' => 'scanner.*'],
            ['route' => 'students.index', 'label' => 'Students', 'icon' => 'users', 'match' => 'students.*'],
            ['route' => 'sections.index', 'label' => 'Sections', 'icon' => 'clipboard', 'match' => 'sections.*'],
            ['route' => 'attendances.index', 'label' => 'Attendance', 'icon' => 'clock', 'match' => 'attendances.*'],
            ['route' => 'users.index', 'label' => 'Accounts', 'icon' => 'cog', 'match' => 'users.*'],
        ]
        : [
            ['route' => 'scanner.index', 'label' => 'Scanner', 'icon' => 'camera', 'match' => 'scanner.*'],
        ];
@endphp

<aside id="app-sidebar" class="fixed inset-y-0 left-0 z-40 w-72 -translate-x-full border-r border-slate-800 bg-slate-900 text-slate-100 transition-transform lg:static lg:translate-x-0">
    <div class="flex h-16 items-center gap-3 border-b border-slate-800 px-5">
        <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-indigo-500 text-white">
            <x-icon name="qr" class="h-5 w-5" />
        </span>
        <div class="min-w-0">
            <p class="truncate text-sm font-semibold tracking-wide">{{ config('app.name') }}</p>
            <p class="truncate text-xs text-slate-400">Daily entrance attendance</p>
        </div>
        <button type="button" class="ml-auto rounded-md p-1 text-slate-400 hover:bg-slate-800 lg:hidden" data-sidebar-close aria-label="Close menu">
            <x-icon name="x" class="h-5 w-5" />
        </button>
    </div>

    <nav class="space-y-1 p-3" aria-label="Primary">
        @foreach ($links as $link)
            @php $active = request()->routeIs($link['match']); @endphp
            <a href="{{ route($link['route']) }}"
               class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition {{ $active ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                <x-icon :name="$link['icon']" class="h-5 w-5 shrink-0" />
                <span>{{ $link['label'] }}</span>
            </a>
        @endforeach
    </nav>
</aside>

<div id="sidebar-backdrop" class="fixed inset-0 z-30 hidden bg-slate-900/50 lg:hidden" data-sidebar-close></div>
