@extends('layouts.app', ['title' => $student->full_name])

@section('content')
    <div class="rounded-[2rem] bg-slate-50/80 p-3 sm:p-5">
        @include('students._profile')
    </div>
@endsection
