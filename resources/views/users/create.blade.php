@extends('layouts.app', ['title' => 'Add account'])

@section('content')
    <div class="mx-auto max-w-xl rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <form method="POST" action="{{ route('users.store') }}" class="space-y-4">
            @csrf
            @include('users._form')
            <div class="flex justify-end gap-3">
                <a href="{{ route('users.index') }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium">Cancel</a>
                <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Create account</button>
            </div>
        </form>
    </div>
@endsection
