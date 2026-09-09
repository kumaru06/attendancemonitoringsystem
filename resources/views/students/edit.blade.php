@extends('layouts.app', ['title' => 'Edit student'])

@section('content')
    <div class="mx-auto max-w-3xl rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <form method="POST" action="{{ route('students.update', $student) }}" enctype="multipart/form-data" class="space-y-6">
            @csrf
            @method('PUT')
            @include('students._form', ['student' => $student])
            <div class="flex justify-end gap-3">
                <a href="{{ route('students.show', $student) }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Cancel</a>
                <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500">Save changes</button>
            </div>
        </form>
    </div>
@endsection
