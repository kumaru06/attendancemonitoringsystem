<?php

namespace App\Models;

use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;

#[Fillable(['name', 'username', 'password', 'role', 'school_id', 'is_active'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'is_active' => 'boolean',
            'role' => UserRole::class,
        ];
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === UserRole::SuperAdmin;
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    public function isScanner(): bool
    {
        return $this->role === UserRole::Scanner;
    }

    public function isManageableAccount(): bool
    {
        return $this->isAdmin() || $this->isScanner();
    }

    public function homeRoute(): string
    {
        return match ($this->role) {
            UserRole::SuperAdmin => 'users.index',
            UserRole::Admin => 'dashboard',
            UserRole::Scanner => 'scanner.index',
        };
    }

    public function recorderLabel(): string
    {
        return $this->isAdmin() ? 'Admin' : 'Scanner';
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function schoolHasStudents(): bool
    {
        return $this->school?->hasStudents() ?? false;
    }

    public function hasRole(UserRole|string $role): bool
    {
        $value = $role instanceof UserRole ? $role : UserRole::from($role);

        return $this->role === $value;
    }

    public function recordedAttendances(): HasMany
    {
        return $this->hasMany(Attendance::class, 'recorded_by');
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }
}
