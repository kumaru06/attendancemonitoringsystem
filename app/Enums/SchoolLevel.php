<?php

namespace App\Enums;

enum SchoolLevel: string
{
    case Elementary = 'elementary';
    case Jhs = 'jhs';
    case Shs = 'shs';
    case College = 'college';

    public function label(): string
    {
        return match ($this) {
            self::Elementary => 'Elementary',
            self::Jhs => 'JHS',
            self::Shs => 'SHS',
            self::College => 'College',
        };
    }

    public function sortOrder(): int
    {
        return match ($this) {
            self::Elementary => 1,
            self::Jhs => 2,
            self::Shs => 3,
            self::College => 4,
        };
    }

    public static function orderBySql(string $column = 'level'): string
    {
        $cases = collect(self::cases())
            ->map(fn (self $level): string => "when {$column} = '{$level->value}' then {$level->sortOrder()}")
            ->implode(' ');

        return "case {$cases} else 99 end";
    }
}
