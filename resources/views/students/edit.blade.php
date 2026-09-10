@extends('layouts.app', ['title' => 'Edit student'])

@section('content')
    <div class="mx-auto w-full max-w-xl rounded-[1.75rem] bg-white p-6 shadow-[0_28px_80px_rgba(15,23,42,0.12)] sm:p-7">
        <form method="POST" action="{{ route('students.update', $student) }}" enctype="multipart/form-data" class="flex flex-col gap-5">
            @csrf
            @method('PUT')
            @include('students._form', ['student' => $student])
            <div class="flex justify-end gap-3">
                <a href="{{ route('students.show', $student) }}" class="rounded-xl bg-slate-100 px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-200">Cancel</a>
                <button type="submit" class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800">Save changes</button>
            </div>
        </form>
    </div>
@endsection
