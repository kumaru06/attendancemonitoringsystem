@extends('layouts.app', ['title' => 'Settings'])

@section('content')
    <div class="mx-auto max-w-xl rounded-3xl bg-white p-6 shadow-[0_12px_40px_rgba(15,23,42,0.05)]">
        <h2 class="text-base font-semibold text-slate-900">School and account</h2>
        <p class="mt-1 text-sm text-slate-500">Update the school name shown on the dashboard and your sign-in profile.</p>

        <form method="POST" action="{{ route('settings.update') }}" class="mt-6 space-y-4">
            @csrf
            @method('PUT')
            <div>
                <label for="school_name" class="mb-1 block text-sm font-medium text-slate-700">School name</label>
                <input id="school_name" name="school_name" required value="{{ old('school_name', $user->school?->name) }}"
                       class="w-full rounded-xl bg-slate-50 px-3 py-2.5 text-sm text-slate-800 outline-none focus:bg-white focus:shadow-sm">
            </div>
            <div>
                <label for="name" class="mb-1 block text-sm font-medium text-slate-700">Your name</label>
                <input id="name" name="name" required value="{{ old('name', $user->name) }}"
                       class="w-full rounded-xl bg-slate-50 px-3 py-2.5 text-sm text-slate-800 outline-none focus:bg-white focus:shadow-sm">
            </div>
            <div>
                <label for="username" class="mb-1 block text-sm font-medium text-slate-700">Username</label>
                <input id="username" value="{{ $user->username }}" disabled
                       class="w-full rounded-xl bg-slate-100 px-3 py-2.5 text-sm text-slate-500">
            </div>
            <div>
                <label for="password" class="mb-1 block text-sm font-medium text-slate-700">Password <span class="font-normal text-slate-400">(leave blank to keep)</span></label>
                <input id="password" name="password" type="password" autocomplete="new-password"
                       class="w-full rounded-xl bg-slate-50 px-3 py-2.5 text-sm text-slate-800 outline-none focus:bg-white focus:shadow-sm">
            </div>
            <div>
                <label for="password_confirmation" class="mb-1 block text-sm font-medium text-slate-700">Confirm password</label>
                <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password"
                       class="w-full rounded-xl bg-slate-50 px-3 py-2.5 text-sm text-slate-800 outline-none focus:bg-white focus:shadow-sm">
            </div>
            <div class="flex justify-end">
                <button type="submit" class="rounded-xl bg-indigo-500 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-400">Save settings</button>
            </div>
        </form>
    </div>
@endsection
