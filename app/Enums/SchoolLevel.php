<?php

namespace App\Enums;

enum SchoolLevel: string
{
    case Kinder = 'kinder';
    case Elementary = 'elementary';
    case Jhs = 'jhs';
    case Shs = 'shs';
    case College = 'college';

    public function label(): string
    {
        return match ($this) {
            self::Kinder => 'Kinder',
            self::Elementary => 'Elementary',
            self::Jhs => 'JHS',
            self::Shs => 'SHS',
            self::College => 'College',
        };
    }

    public function sortOrder(): int
    {
        return match ($this) {
            self::Kinder => 1,
            self::Elementary => 2,
            self::Jhs => 3,
            self::Shs => 4,
            self::College => 5,
        };
    }

    /**
     * @return array{wrap: string, title: string, meta: string, badge: string, dot: string}
     */
    public function headerTone(): array
    {
        return match ($this) {
            self::Kinder => [
                'wrap' => 'bg-rose-50',
                'title' => 'text-rose-950',
                'meta' => 'text-rose-700/75',
                'badge' => 'bg-rose-100 text-rose-800',
                'dot' => 'bg-rose-400',
            ],
            self::Elementary => [
                'wrap' => 'bg-sky-50',
                'title' => 'text-sky-950',
                'meta' => 'text-sky-700/75',
                'badge' => 'bg-sky-100 text-sky-800',
                'dot' => 'bg-sky-400',
            ],
            self::Jhs => [
                'wrap' => 'bg-amber-50',
                'title' => 'text-amber-950',
                'meta' => 'text-amber-800/75',
                'badge' => 'bg-amber-100 text-amber-800',
                'dot' => 'bg-amber-400',
            ],
            self::Shs => [
                'wrap' => 'bg-violet-50',
                'title' => 'text-violet-950',
                'meta' => 'text-violet-800/75',
                'badge' => 'bg-violet-100 text-violet-800',
                'dot' => 'bg-violet-400',
            ],
            self::College => [
                'wrap' => 'bg-emerald-50',
                'title' => 'text-emerald-950',
                'meta' => 'text-emerald-800/75',
                'badge' => 'bg-emerald-100 text-emerald-800',
                'dot' => 'bg-emerald-400',
            ],
        };
    }

    public function chipClass(): string
    {
        return match ($this) {
            self::Kinder => 'bg-rose-50 text-rose-800',
            self::Elementary => 'bg-sky-50 text-sky-800',
            self::Jhs => 'bg-amber-50 text-amber-800',
            self::Shs => 'bg-violet-50 text-violet-800',
            self::College => 'bg-emerald-50 text-emerald-800',
        };
    }

    /**
     * @return array{active: string, idle: string}
     */
    public function filterButtonClasses(): array
    {
        $base = 'inline-flex items-center gap-2 rounded-full px-4 py-2 text-sm';

        return match ($this) {
            self::Kinder => [
                'active' => $base.' font-semibold text-white bg-rose-500 shadow-sm shadow-rose-500/20',
                'idle' => $base.' font-medium text-rose-800 bg-rose-50 hover:bg-rose-100',
            ],
            self::Elementary => [
                'active' => $base.' font-semibold text-white bg-sky-600 shadow-sm shadow-sky-600/20',
                'idle' => $base.' font-medium text-sky-800 bg-sky-50 hover:bg-sky-100',
            ],
            self::Jhs => [
                'active' => $base.' font-semibold text-white bg-amber-500 shadow-sm shadow-amber-500/20',
                'idle' => $base.' font-medium text-amber-900 bg-amber-50 hover:bg-amber-100',
            ],
            self::Shs => [
                'active' => $base.' font-semibold text-white bg-violet-600 shadow-sm shadow-violet-600/20',
                'idle' => $base.' font-medium text-violet-800 bg-violet-50 hover:bg-violet-100',
            ],
            self::College => [
                'active' => $base.' font-semibold text-white bg-emerald-600 shadow-sm shadow-emerald-600/20',
                'idle' => $base.' font-medium text-emerald-800 bg-emerald-50 hover:bg-emerald-100',
            ],
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
