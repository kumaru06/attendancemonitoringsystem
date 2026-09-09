<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Dashboard' }} | {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="min-h-screen bg-slate-100 font-sans text-slate-800 antialiased">
    <div class="min-h-screen lg:flex">
        @include('components.sidebar')

        <div class="flex min-w-0 flex-1 flex-col">
            @include('components.topbar')

            <main class="mx-auto w-full max-w-7xl flex-1 px-4 py-6 sm:px-6 lg:px-8">
                @include('components.flash-message')
                @yield('content')
            </main>
        </div>
    </div>

    @stack('scripts')
</body>
</html>
