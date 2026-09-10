<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Console\Command;

class CreateSuperAdmin extends Command
{
    protected $signature = 'superadmin:create';

    protected $description = 'Interactively create a super administrator account';

    public function handle(): int
    {
        $name = $this->ask('Super administrator name');
        $username = $this->ask('Username');

        if (! $name || ! $username) {
            $this->error('Name and username are required.');

            return self::FAILURE;
        }

        if (User::query()->where('username', $username)->exists()) {
            $this->error('That username is already taken.');

            return self::FAILURE;
        }

        $password = $this->secret('Password');
        $confirm = $this->secret('Confirm password');

        if (! $password || strlen($password) < 8) {
            $this->error('Password must be at least 8 characters.');

            return self::FAILURE;
        }

        if ($password !== $confirm) {
            $this->error('Passwords do not match.');

            return self::FAILURE;
        }

        User::query()->create([
            'name' => $name,
            'username' => $username,
            'password' => $password,
            'role' => UserRole::SuperAdmin,
            'is_active' => true,
        ]);

        $this->info("Super administrator [{$username}] created.");

        return self::SUCCESS;
    }
}
