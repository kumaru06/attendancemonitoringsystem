@extends('layouts.guest', ['title' => 'Sign in'])

@section('content')
    <div class="w-full max-w-md">
        <div class="mb-6 text-center">
            <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-900 text-white">
                <x-icon name="qr" class="h-6 w-6" />
            </div>
            <h1 class="text-2xl font-semibold text-slate-900">{{ config('app.name') }}</h1>
            <p class="mt-1 text-sm text-slate-500">Sign in to record or manage daily attendance.</p>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <form method="POST" action="{{ route('login.store') }}" class="space-y-4">
                @csrf

                <div>
                    <label for="username" class="mb-1 block text-sm font-medium text-slate-700">Username</label>
                    <input id="username" name="username" type="text" value="{{ old('username') }}" required autofocus autocomplete="username"
                           class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    @error('username')
                        <p class="mt-1 text-sm text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password" class="mb-1 block text-sm font-medium text-slate-700">Password</label>
                    <input id="password" name="password" type="password" required autocomplete="current-password"
                           class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>

                <label class="flex items-center gap-2 text-sm text-slate-600">
                    <input type="checkbox" name="remember" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                    Remember me on this computer
                </label>

                <button type="submit" class="w-full rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                    Sign in
                </button>
            </form>
        </div>
    </div>
@endsection
