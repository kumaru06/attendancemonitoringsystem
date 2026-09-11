@extends('layouts.app', ['title' => 'Scanner', 'fillViewport' => true])

@push('head')
    @vite(['resources/js/scanner.js'])
@endpush

@section('content')
    @php
        $cameraHost = request()->getHost();
        $cameraAllowedHere = request()->secure()
            || in_array($cameraHost, ['localhost', '127.0.0.1', '::1'], true)
            || str_ends_with($cameraHost, '.localhost');
        $schoolName = auth()->user()?->school?->name;
    @endphp

    <div class="flex min-h-0 flex-1 flex-col gap-4">
        @unless ($cameraAllowedHere)
            <div class="shrink-0 rounded-2xl border border-amber-200/80 bg-amber-50 px-5 py-4 text-sm text-amber-950" role="status">
                <p class="font-semibold">The webcam is blocked on this address</p>
                <p class="mt-1 text-amber-900/80">
                    Chrome and Edge allow the camera only on HTTPS or <span class="font-medium">http://localhost</span>.
                    <span class="font-medium">http://{{ $cameraHost }}</span> is not treated as secure, so the camera list stays empty.
                </p>
                <p class="mt-2 text-amber-900/80">
                    On this computer, open
                    @if (str_ends_with($cameraHost, '.test'))
                        <a class="font-semibold text-amber-950 underline underline-offset-2" href="https://{{ $cameraHost }}/scanner">https://{{ $cameraHost }}/scanner</a>
                        so the browser treats this site as secure.
                    @else
                        <a class="font-semibold text-amber-950 underline underline-offset-2" href="http://127.0.0.1:8000/scanner">http://127.0.0.1:8000/scanner</a>
                        after <code class="rounded bg-white/80 px-1 py-0.5">php artisan serve</code>. On Serv00, turn on Let's Encrypt and open the HTTPS URL instead.
                    @endif
                </p>
            </div>
        @endunless

        <div class="grid min-h-0 flex-1 gap-4 lg:grid-cols-[minmax(0,1.55fr)_24.5rem] lg:grid-rows-1" id="scanner-app"
             data-scan-url="{{ route('scanner.scan') }}"
             data-face-url="{{ route('scanner.face') }}"
             data-faces-url="{{ route('scanner.faces') }}"
             data-enroll-url="{{ route('scanner.faces.enroll') }}"
             data-models-url="{{ asset('models/face-api') }}"
             data-csrf="{{ csrf_token() }}"
             data-mode="qr"
             data-scanning="false">
            <section class="scanner-stage flex min-h-[22rem] flex-col overflow-hidden rounded-[2rem] bg-slate-950 text-white shadow-[0_32px_80px_-36px_rgb(15_23_42_/_0.75)] ring-1 ring-white/10 lg:min-h-0">
                <div class="flex shrink-0 items-start justify-between gap-4 px-5 py-4 sm:px-6">
                    <div class="min-w-0">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-indigo-300/80">Gate camera</p>
                        <p id="scan-status" class="mt-1 truncate text-sm font-medium text-slate-100">Camera is stopped.</p>
                        @if ($schoolName)
                            <p class="mt-1 truncate text-xs text-slate-400">{{ $schoolName }}</p>
                        @endif
                    </div>
                    <span id="scan-live-pill" class="inline-flex shrink-0 items-center gap-1.5 rounded-full bg-white/10 px-2.5 py-1 text-[11px] font-semibold uppercase tracking-wide text-slate-300 ring-1 ring-white/10">
                        <span id="scan-live-dot" class="h-1.5 w-1.5 rounded-full bg-slate-400"></span>
                        <span id="scan-live-label">Idle</span>
                    </span>
                </div>

                <div class="scanner-toolbar flex shrink-0 flex-wrap items-center gap-2 px-5 pb-4 sm:px-6">
                    <div class="inline-flex rounded-xl bg-white/10 p-1 ring-1 ring-white/10" role="tablist" aria-label="Scanner mode">
                        <button type="button" data-scanner-mode="qr" class="scanner-mode-button rounded-lg px-3 py-1.5 text-xs font-semibold text-white" aria-pressed="true">QR</button>
                        <button type="button" data-scanner-mode="face" class="scanner-mode-button rounded-lg px-3 py-1.5 text-xs font-semibold text-slate-300" aria-pressed="false">Face</button>
                        <button type="button" data-scanner-mode="enroll" class="scanner-mode-button rounded-lg px-3 py-1.5 text-xs font-semibold text-slate-300" aria-pressed="false">Enroll</button>
                    </div>
                    <div class="camera-picker relative w-72 max-w-full shrink-0">
                        <label class="sr-only" for="camera-select">Camera</label>
                        <select id="camera-select" class="sr-only" tabindex="-1" aria-hidden="true">
                            <option value="">Select a camera</option>
                        </select>
                        <button id="camera-picker-button" type="button" class="flex h-10 w-full items-center justify-between gap-2 rounded-xl border border-white/10 bg-white/10 px-3 text-left text-sm text-slate-100 hover:bg-white/15 focus:outline-none focus:ring-2 focus:ring-indigo-400/40" aria-haspopup="listbox" aria-expanded="false" aria-controls="camera-picker-menu">
                            <span id="camera-picker-label" class="min-w-0 truncate">Select a camera</span>
                            <svg class="h-4 w-4 shrink-0 text-slate-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 10.94l3.71-3.71a.75.75 0 1 1 1.06 1.06l-4.24 4.24a.75.75 0 0 1-1.06 0L5.21 8.29a.75.75 0 0 1 .02-1.08Z" clip-rule="evenodd" />
                            </svg>
                        </button>
                        <ul id="camera-picker-menu" class="camera-picker-menu absolute left-0 top-[calc(100%+0.4rem)] z-30 hidden max-h-56 w-full overflow-y-auto rounded-xl border border-white/10 bg-slate-900 py-1 shadow-[0_18px_40px_-16px_rgb(2_6_23_/_0.8)]" role="listbox" hidden></ul>
                    </div>
                    <button id="start-scan" type="button" class="inline-flex h-10 w-auto shrink-0 items-center justify-center whitespace-nowrap rounded-xl bg-indigo-500 px-4 text-sm font-semibold text-white shadow-lg shadow-indigo-500/25 hover:bg-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-300">
                        Start scanning
                    </button>
                    <button id="stop-scan" type="button" class="inline-flex h-10 w-auto shrink-0 items-center justify-center whitespace-nowrap rounded-xl border border-white/15 bg-white/5 px-4 text-sm font-semibold text-slate-200 hover:bg-white/10 focus:outline-none focus:ring-2 focus:ring-white/20">
                        Stop
                    </button>
                </div>

                <div class="flex min-h-0 flex-1 px-5 pb-5 sm:px-6">
                    <div class="scanner-preview relative min-h-[16rem] w-full flex-1 overflow-hidden rounded-[1.5rem] bg-black [container-type:size] ring-1 ring-white/10">
                        <div id="reader" class="absolute inset-0 h-full w-full"></div>
                        <video id="face-video" class="absolute inset-0 h-full w-full object-cover" playsinline muted></video>
                        <div class="pointer-events-none absolute inset-0 z-10 bg-[radial-gradient(circle_at_center,transparent_42%,rgb(2_6_23_/_0.45)_100%)]"></div>
                        <div class="scanner-scanline pointer-events-none absolute inset-x-16 top-[18%] z-10 hidden h-px bg-gradient-to-r from-transparent via-indigo-300 to-transparent opacity-80"></div>
                        <div class="pointer-events-none absolute inset-0 z-10 flex items-center justify-center" aria-hidden="true">
                            <div class="relative aspect-square h-[58%]">
                                <span class="absolute left-0 top-0 h-9 w-9 rounded-tl-xl border-l-[3px] border-t-[3px] border-white/90"></span>
                                <span class="absolute right-0 top-0 h-9 w-9 rounded-tr-xl border-r-[3px] border-t-[3px] border-white/90"></span>
                                <span class="absolute bottom-0 left-0 h-9 w-9 rounded-bl-xl border-b-[3px] border-l-[3px] border-white/90"></span>
                                <span class="absolute bottom-0 right-0 h-9 w-9 rounded-br-xl border-b-[3px] border-r-[3px] border-white/90"></span>
                            </div>
                        </div>
                        <div id="face-guide" class="pointer-events-none absolute inset-x-0 bottom-4 z-20 hidden px-5">
                            <div class="mx-auto flex max-w-md items-center gap-3 rounded-2xl bg-slate-950/80 px-4 py-3 text-white ring-1 ring-white/15 backdrop-blur">
                                <span id="face-guide-arrow" class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-indigo-500 text-2xl font-semibold">←</span>
                                <div class="min-w-0">
                                    <p id="face-guide-label" class="text-sm font-semibold">Look left</p>
                                    <p id="face-guide-step" class="text-xs text-slate-300">1/5 · 0/2</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <aside class="flex min-h-0 flex-col gap-4 lg:h-full">
                <div id="enroll-panel" class="hidden shrink-0 overflow-hidden rounded-[2rem] border border-slate-200/80 bg-white p-5 shadow-[0_18px_50px_-28px_rgb(15_23_42_/_0.35)]">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-400">Register a face</p>
                    <label for="enroll-search" class="mt-3 mb-1 block text-xs font-medium text-slate-500">Student name or USN</label>
                    <input id="enroll-search" type="search" autocomplete="off" placeholder="Search, or start the camera to scan a QR"
                           class="w-full rounded-xl bg-slate-50 px-3 py-2.5 text-sm text-slate-800 outline-none focus:bg-white focus:shadow-sm">
                    <ul id="enroll-results" class="mt-2 hidden max-h-48 overflow-y-auto rounded-xl border border-slate-100 bg-white"></ul>
                    <div id="enroll-selected" class="mt-3 hidden rounded-2xl bg-slate-50 px-3 py-3">
                        <p class="text-xs font-medium text-slate-500">Selected student</p>
                        <p id="enroll-selected-name" class="mt-0.5 text-sm font-semibold text-slate-900"></p>
                        <p id="enroll-selected-meta" class="text-xs text-slate-500"></p>
                        <button id="enroll-clear" type="button" class="mt-2 text-xs font-semibold text-indigo-600 hover:text-indigo-500">Choose another student</button>
                    </div>
                </div>
                <div id="result-card" class="shrink-0 overflow-hidden rounded-[2rem] border border-slate-200/80 bg-white p-5 shadow-[0_18px_50px_-28px_rgb(15_23_42_/_0.35)]" data-result="idle">
                    <div class="flex items-center gap-4">
                        <div class="relative shrink-0">
                            <img id="result-photo" alt="" class="hidden h-20 w-20 rounded-2xl object-cover ring-4 ring-slate-100">
                            <div id="result-photo-fallback" class="flex h-20 w-20 items-center justify-center rounded-2xl bg-slate-50 text-slate-300 ring-4 ring-slate-100">
                                <x-icon name="user" id="result-photo-icon" class="h-8 w-8" />
                                <span id="result-initials" class="hidden text-xl font-semibold tracking-tight text-slate-500"></span>
                            </div>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-400">Scan result</p>
                            <p id="result-name" class="mt-1 truncate text-lg font-semibold tracking-tight text-slate-900">Waiting for a scan</p>
                            <p id="result-meta" class="mt-0.5 truncate text-sm text-slate-500">Present a student QR to the camera.</p>
                            <div class="mt-2 flex flex-wrap items-center gap-2">
                                <p id="result-badge" class="hidden rounded-full px-2.5 py-1 text-[11px] font-semibold"></p>
                                <p id="result-time" class="text-xs font-medium tabular-nums text-slate-400"></p>
                            </div>
                            <p id="result-message" class="mt-1 text-sm font-medium text-slate-600"></p>
                        </div>
                    </div>
                </div>

                <section class="flex h-[22rem] min-h-0 flex-col overflow-hidden rounded-[2rem] border border-slate-200/80 bg-white shadow-[0_18px_50px_-28px_rgb(15_23_42_/_0.35)] lg:h-auto lg:flex-1">
                    <div class="flex shrink-0 items-end justify-between gap-3 border-b border-slate-100 px-5 py-4">
                        <div class="min-w-0">
                            <h2 class="text-sm font-semibold text-slate-900">Today's arrivals</h2>
                            <p class="mt-0.5 text-xs text-slate-500">{{ $today }}</p>
                        </div>
                        <p class="shrink-0 text-xs font-medium tabular-nums text-slate-400">
                            <span id="recent-count">{{ $recent->count() }}</span> recorded
                        </p>
                    </div>
                    <ul id="recent-list" class="scan-feed min-h-0 flex-1 divide-y divide-slate-100 overflow-y-auto">
                        @forelse ($recent as $row)
                            <li class="flex items-center gap-3 px-4 py-3">
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-slate-900 text-[11px] font-semibold text-white">
                                    {{ $row->student?->initials ?: '—' }}
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-medium text-slate-900">{{ $row->student?->full_name }}</p>
                                    <p class="truncate text-xs text-slate-500">{{ $row->student?->student_number }}</p>
                                </div>
                                <div class="flex shrink-0 flex-col items-end gap-1">
                                    <span class="rounded-full px-2 py-0.5 text-[10px] font-semibold {{ $row->student?->section?->level?->chipClass() ?? 'bg-slate-100 text-slate-600' }}">
                                        {{ $row->student?->section?->level?->label() }}
                                    </span>
                                    <p class="text-xs font-medium tabular-nums text-slate-400">{{ $row->time_in }}</p>
                                </div>
                            </li>
                        @empty
                            <li class="px-5 py-10 text-center text-sm text-slate-500">No successful scans yet today.</li>
                        @endforelse
                    </ul>
                </section>
            </aside>
        </div>
    </div>
@endsection
