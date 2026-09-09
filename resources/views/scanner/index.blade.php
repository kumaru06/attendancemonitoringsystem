@extends('layouts.app', ['title' => 'Scanner'])

@push('head')
    @vite(['resources/js/scanner.js'])
@endpush

@section('content')
    @php
        $cameraHost = request()->getHost();
        $cameraAllowedHere = request()->secure()
            || in_array($cameraHost, ['localhost', '127.0.0.1', '::1'], true)
            || str_ends_with($cameraHost, '.localhost');
    @endphp

    @unless ($cameraAllowedHere)
        <div class="mb-5 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-950" role="status">
            <p class="font-semibold">The webcam is blocked on this address</p>
            <p class="mt-1">
                Chrome and Edge allow the camera only on HTTPS or <span class="font-medium">http://localhost</span>.
                <span class="font-medium">http://{{ $cameraHost }}</span> is not treated as secure, so the camera list stays empty.
            </p>
            <p class="mt-2">
                Open the scanner at
                <a class="font-semibold text-amber-900 underline" href="http://127.0.0.1:8000/scanner">http://127.0.0.1:8000/scanner</a>
                (run <code class="rounded bg-white px-1 py-0.5">php artisan serve</code> first), or turn on Laragon Apache SSL and use HTTPS.
            </p>
        </div>
    @endunless

    <div class="grid gap-6 xl:grid-cols-5" id="scanner-app"
         data-scan-url="{{ route('scanner.scan') }}"
         data-csrf="{{ csrf_token() }}">
        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm xl:col-span-3">
            <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h2 class="text-sm font-semibold text-slate-900">Camera</h2>
                    <p id="scan-status" class="mt-1 text-sm text-slate-500">Camera is stopped.</p>
                </div>
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                    <label class="sr-only" for="camera-select">Camera</label>
                    <select id="camera-select" class="min-w-48 rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <option value="">Select a camera</option>
                    </select>
                    <button id="start-scan" type="button" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500">Start scanning</button>
                    <button id="stop-scan" type="button" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Stop</button>
                </div>
            </div>
            <div class="scanner-preview relative aspect-video overflow-hidden rounded-xl bg-slate-900 [container-type:size]">
                <div id="reader" class="absolute inset-0 h-full w-full"></div>
                <div class="pointer-events-none absolute inset-0 z-10 flex items-center justify-center" aria-hidden="true">
                    <div class="relative aspect-square h-[70%]">
                        <span class="absolute left-0 top-0 h-10 w-10 rounded-tl-lg border-l-4 border-t-4 border-white"></span>
                        <span class="absolute right-0 top-0 h-10 w-10 rounded-tr-lg border-r-4 border-t-4 border-white"></span>
                        <span class="absolute bottom-0 left-0 h-10 w-10 rounded-bl-lg border-b-4 border-l-4 border-white"></span>
                        <span class="absolute bottom-0 right-0 h-10 w-10 rounded-br-lg border-b-4 border-r-4 border-white"></span>
                    </div>
                </div>
            </div>
        </section>

        <section class="space-y-6 xl:col-span-2">
            <div id="result-card" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-semibold text-slate-900">Scan result</p>
                <div class="mt-4 flex flex-col items-center text-center">
                    <img id="result-photo" alt="" class="mb-3 hidden h-40 w-40 rounded-2xl object-cover ring-1 ring-slate-200">
                    <div id="result-photo-fallback" class="mb-3 flex h-40 w-40 items-center justify-center rounded-2xl bg-slate-100 text-slate-400">
                        <x-icon name="user" class="h-12 w-12" />
                    </div>
                    <p id="result-name" class="text-lg font-semibold text-slate-900">Waiting for a scan</p>
                    <p id="result-meta" class="text-sm text-slate-500">Present a student QR to the camera.</p>
                    <p id="result-message" class="mt-3 text-sm font-medium text-slate-600"></p>
                    <p id="result-time" class="mt-1 text-sm text-slate-500"></p>
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 px-5 py-4">
                    <h2 class="text-sm font-semibold text-slate-900">Your scans today</h2>
                    <p class="text-xs text-slate-500">{{ $today }}</p>
                </div>
                <ul id="recent-list" class="divide-y divide-slate-100">
                    @forelse ($recent as $row)
                        <li class="px-5 py-3">
                            <p class="text-sm font-medium text-slate-900">{{ $row->student?->full_name }}</p>
                            <p class="text-xs text-slate-500">{{ $row->student?->student_number }} · {{ $row->time_in }}</p>
                        </li>
                    @empty
                        <li class="px-5 py-8 text-center text-sm text-slate-500">No successful scans yet today.</li>
                    @endforelse
                </ul>
            </div>
        </section>
    </div>
@endsection
