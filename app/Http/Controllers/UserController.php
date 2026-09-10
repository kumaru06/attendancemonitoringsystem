<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\School;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class UserController extends Controller
{
    public function __construct(private readonly AuditLogService $auditLog) {}

    public function index(): View
    {
        $this->authorize('viewAny', User::class);

        return view('users.index', [
            'users' => User::query()
                ->with('school')
                ->whereIn('role', UserRole::manageableRoles())
                ->orderBy('name')
                ->paginate(15),
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

        $data = $request->safe()->except(['school_name']);
        $data['is_active'] = $request->boolean('is_active', true);

        $user = DB::transaction(function () use ($data, $request): User {
            $school = School::query()->create([
                'name' => $request->validated('school_name'),
            ]);

            $data['school_id'] = $school->id;

            return User::query()->create($data);
        });

        $this->auditLog->record($request->user(), 'user.created', $user, [
            'username' => $user->username,
            'role' => $user->role instanceof UserRole ? $user->role->value : $user->role,
            'school' => $user->school?->name,
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

        $data = $request->safe()->except(['school_name']);
        $data['is_active'] = $request->boolean('is_active');

        if (empty($data['password'])) {
            unset($data['password']);
        }

        $wasActive = $user->is_active;
        $user->update($data);

        if ($user->school && $request->filled('school_name')) {
            $user->school->update(['name' => $request->validated('school_name')]);
        }

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

        $school = $user->school;
        $user->delete();

        if ($school && ! $school->users()->exists() && ! $school->hasStudents()) {
            $school->delete();
        }

        return redirect()->route('users.index')->with('success', 'Account deleted.');
    }
}
