<?php

namespace App\Enums;

enum UserRole: string
{
    case SuperAdmin = 'superadmin';
    case Admin = 'admin';
    case Scanner = 'scanner';

    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Super Administrator',
            self::Admin => 'Administrator',
            self::Scanner => 'Scanner Staff',
        };
    }

    /**
     * @return list<self>
     */
    public static function assignableRoles(): array
    {
        return [self::Admin];
    }

    /**
     * @return list<self>
     */
    public static function manageableRoles(): array
    {
        return [self::Admin, self::Scanner];
    }
}
