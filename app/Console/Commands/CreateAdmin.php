<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Console\Command;

class CreateAdmin extends Command
{
    protected $signature = 'admin:create';

    protected $description = 'Interactively create the initial administrator account';

    public function handle(): int
    {
        $name = $this->ask('Administrator name');
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
            'role' => UserRole::Admin,
            'is_active' => true,
        ]);

        $this->info("Administrator [{$username}] created.");

        return self::SUCCESS;
    }
}
