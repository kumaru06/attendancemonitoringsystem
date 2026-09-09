@php use App\Enums\UserRole; $user = $user ?? null; @endphp

<div>
    <label for="name" class="mb-1 block text-sm font-medium text-slate-700">Name</label>
    <input id="name" name="name" required value="{{ old('name', $user?->name) }}" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500">
</div>
<div>
    <label for="username" class="mb-1 block text-sm font-medium text-slate-700">Username</label>
    <input id="username" name="username" required value="{{ old('username', $user?->username) }}" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500">
</div>
<div>
    <label for="role" class="mb-1 block text-sm font-medium text-slate-700">Role</label>
    <select id="role" name="role" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500">
        @foreach (UserRole::cases() as $role)
            <option value="{{ $role->value }}" @selected(old('role', $user?->role?->value ?? 'scanner') === $role->value)>{{ $role->label() }}</option>
        @endforeach
    </select>
</div>
<div>
    <label for="password" class="mb-1 block text-sm font-medium text-slate-700">Password @if($user)<span class="font-normal text-slate-400">(leave blank to keep)</span>@endif</label>
    <input id="password" name="password" type="password" @unless($user) required @endunless autocomplete="new-password" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500">
</div>
<div>
    <label for="password_confirmation" class="mb-1 block text-sm font-medium text-slate-700">Confirm password</label>
    <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500">
</div>
<label class="inline-flex items-center gap-2 text-sm text-slate-700">
    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $user?->is_active ?? true)) class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
    Active account
</label>
