<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class UserController extends Controller
{
    public function __construct(private readonly AuditLogService $auditLog) {}

    public function index(): View
    {
        $this->authorize('viewAny', User::class);

        return view('users.index', [
            'users' => User::query()->orderBy('name')->paginate(15),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', User::class);

        return view('users.create');
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $this->authorize('create', User::class);

        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active', true);
        $user = User::query()->create($data);

        $this->auditLog->record($request->user(), 'user.created', $user, [
            'username' => $user->username,
            'role' => $user->role instanceof UserRole ? $user->role->value : $user->role,
        ]);

        return redirect()->route('users.index')->with('success', 'Account created.');
    }

    public function edit(User $user): View
    {
        $this->authorize('update', $user);

        return view('users.edit', compact('user'));
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $this->authorize('update', $user);

        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active');

        if (empty($data['password'])) {
            unset($data['password']);
        }

        $wasActive = $user->is_active;
        $user->update($data);

        $action = 'user.updated';
        if ($wasActive && ! $user->is_active) {
            $action = 'user.deactivated';
        }

        $this->auditLog->record($request->user(), $action, $user, [
            'username' => $user->username,
            'is_active' => $user->is_active,
        ]);

        return redirect()->route('users.index')->with('success', 'Account updated.');
    }

    public function destroy(User $user): RedirectResponse
    {
        $this->authorize('delete', $user);

        $this->auditLog->record(request()->user(), 'user.deleted', $user, [
            'username' => $user->username,
            'role' => $user->role instanceof UserRole ? $user->role->value : $user->role,
        ]);

        $user->delete();

        return redirect()->route('users.index')->with('success', 'Account deleted.');
    }
}
