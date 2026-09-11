<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateSettingsRequest;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function __construct(private readonly AuditLogService $auditLog) {}

    public function edit(): View
    {
        $user = auth()->user();

        abort_unless($user?->isAdmin(), 403);

        $user->loadMissing('school');

        return view('settings.edit', [
            'user' => $user,
        ]);
    }

    public function update(UpdateSettingsRequest $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->safe()->only(['name', 'password']);

        if (empty($data['password'])) {
            unset($data['password']);
        }

        $user->update($data);

        if ($user->school) {
            $user->school->update([
                'name' => $request->validated('school_name'),
            ]);
        }

        $this->auditLog->record($user, 'settings.updated', $user, [
            'name' => $user->name,
            'school' => $user->school?->name,
        ]);

        return redirect()->route('settings.edit')->with('success', 'Settings saved.');
    }
}
