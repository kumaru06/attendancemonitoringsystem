@extends('layouts.app', ['title' => 'Accounts'])

@section('content')
    <div class="mb-5 flex justify-end">
        <a href="{{ route('users.create') }}" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
            <x-icon name="plus" class="h-4 w-4" /> Add account
        </a>
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto overflow-y-visible">
            <table class="min-w-full text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-5 py-3 font-medium">Name</th>
                        <th class="px-5 py-3 font-medium">Username</th>
                        <th class="px-5 py-3 font-medium">Role</th>
                        <th class="px-5 py-3 font-medium">Status</th>
                        <th class="px-5 py-3 text-right font-medium">Actions</th>
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
                            <td class="relative px-5 py-3 text-right">
                                <details data-action-menu class="relative inline-block text-left">
                                    <summary class="inline-flex cursor-pointer list-none items-center rounded-lg border border-slate-300 bg-white px-2.5 py-1.5 text-slate-600 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 [&::-webkit-details-marker]:hidden">
                                        <span class="sr-only">Actions for {{ $account->name }}</span>
                                        <x-icon name="ellipsis" class="h-4 w-4" />
                                    </summary>
                                    <div class="absolute right-0 z-20 mt-1 w-40 overflow-hidden rounded-lg border border-slate-200 bg-white py-1 text-left shadow-lg">
                                        <a href="{{ route('users.edit', $account) }}" class="block px-3 py-2 text-sm text-slate-700 hover:bg-slate-50">Edit</a>
                                        @can('delete', $account)
                                            <button type="button" class="block w-full px-3 py-2 text-left text-sm text-rose-700 hover:bg-rose-50" data-open-modal="delete-user-{{ $account->id }}">
                                                Delete
                                            </button>
                                        @endcan
                                    </div>
                                </details>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-200 px-5 py-3">{{ $users->links() }}</div>
    </div>

    @foreach ($users as $account)
        @can('delete', $account)
            <x-modal id="delete-user-{{ $account->id }}" title="Delete account">
                <p>
                    Delete <span class="font-medium text-slate-900">{{ $account->name }}</span>
                    (<span class="font-medium">{{ $account->username }}</span>)?
                    Attendance history stays on file. This account will no longer be able to sign in.
                </p>
                <form method="POST" action="{{ route('users.destroy', $account) }}" class="mt-5 flex justify-end gap-2">
                    @csrf
                    @method('DELETE')
                    <button type="button" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium" data-modal-close>Cancel</button>
                    <button type="submit" class="rounded-lg bg-rose-600 px-4 py-2 text-sm font-semibold text-white hover:bg-rose-700">Delete</button>
                </form>
            </x-modal>
        @endcan
    @endforeach
@endsection
