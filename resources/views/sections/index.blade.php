@extends('layouts.app', ['title' => 'Sections'])

@section('content')
    <div class="grid gap-6 lg:grid-cols-3">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-sm font-semibold text-slate-900">Add section / course</h2>
            <form method="POST" action="{{ route('sections.store') }}" class="mt-4 space-y-4">
                @csrf
                <div>
                    <label for="name" class="mb-1 block text-sm font-medium text-slate-700">Name</label>
                    <input id="name" name="name" type="text" required value="{{ old('name') }}"
                           class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
                <button type="submit" class="w-full rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Save section</button>
            </form>
        </div>

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm lg:col-span-2">
            @if ($sections->isEmpty())
                <p class="px-5 py-12 text-center text-sm text-slate-500">No sections yet. Add one to start registering students.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full text-left text-sm">
                        <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-5 py-3 font-medium">Name</th>
                                <th class="px-5 py-3 font-medium">Students</th>
                                <th class="px-5 py-3 font-medium"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($sections as $section)
                                <tr>
                                    <td class="px-5 py-3">
                                        <form method="POST" action="{{ route('sections.update', $section) }}" class="flex flex-col gap-2 sm:flex-row">
                                            @csrf
                                            @method('PUT')
                                            <input name="name" value="{{ $section->name }}" required class="w-full rounded-lg border border-slate-300 px-3 py-1.5 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                            <button type="submit" class="rounded-lg border border-slate-300 px-3 py-1.5 text-sm font-medium hover:bg-slate-50">Update</button>
                                        </form>
                                    </td>
                                    <td class="px-5 py-3 text-slate-600">{{ $section->students_count }}</td>
                                    <td></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-slate-200 px-5 py-3">{{ $sections->links() }}</div>
            @endif
        </div>
    </div>
@endsection
