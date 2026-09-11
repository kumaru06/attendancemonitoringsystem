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
<body class="min-h-screen bg-[#F4F7FB] font-sans text-slate-800 antialiased {{ ! empty($fillViewport) ? 'lg:h-dvh lg:overflow-hidden' : '' }}">
    @include('components.sidebar')

    <div class="flex min-h-screen min-w-0 flex-col lg:pl-72 {{ ! empty($fillViewport) ? 'lg:h-dvh' : '' }}">
        @include('components.topbar')

        <main class="mx-auto w-full flex-1 px-4 py-6 sm:px-6 lg:px-8 {{ ! empty($fillViewport) ? 'flex min-h-0 max-w-[110rem] flex-col py-5 lg:overflow-hidden' : (! empty($wide) ? 'max-w-[110rem]' : 'max-w-7xl') }}">
            @include('components.flash-message')
            @yield('content')
        </main>
    </div>

    @stack('scripts')
</body>
</html>
