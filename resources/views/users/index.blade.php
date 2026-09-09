@extends('layouts.app', ['title' => 'Accounts'])

@section('content')
    <div class="mb-5 flex justify-end">
        <a href="{{ route('users.create') }}" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
            <x-icon name="plus" class="h-4 w-4" /> Add account
        </a>
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-5 py-3 font-medium">Name</th>
                        <th class="px-5 py-3 font-medium">Username</th>
                        <th class="px-5 py-3 font-medium">Role</th>
                        <th class="px-5 py-3 font-medium">Status</th>
                        <th class="px-5 py-3 font-medium"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($users as $account)
                        <tr>
                            <td class="px-5 py-3 font-medium text-slate-900">{{ $account->name }}</td>
                            <td class="px-5 py-3">{{ $account->username }}</td>
                            <td class="px-5 py-3">{{ $account->role->label() }}</td>
                            <td class="px-5 py-3">
                                <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium {{ $account->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">
                                    {{ $account->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="px-5 py-3 text-right">
                                <a href="{{ route('users.edit', $account) }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-700">Edit</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-200 px-5 py-3">{{ $users->links() }}</div>
    </div>
@endsection
