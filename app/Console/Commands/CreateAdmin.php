<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\School;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

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

        $schoolName = $this->ask('School name', $name);

        if (! $schoolName) {
            $this->error('School name is required.');

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

        DB::transaction(function () use ($name, $username, $password, $schoolName): void {
            $school = School::query()->create([
                'name' => $schoolName,
            ]);

            User::query()->create([
                'name' => $name,
                'username' => $username,
                'password' => $password,
                'role' => UserRole::Admin,
                'school_id' => $school->id,
                'is_active' => true,
            ]);
        });

        $this->info("Administrator [{$username}] created for [{$schoolName}].");

        return self::SUCCESS;
    }
}
