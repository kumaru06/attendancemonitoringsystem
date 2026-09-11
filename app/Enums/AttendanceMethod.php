<?php

namespace App\Enums;

enum AttendanceMethod: string
{
    case Qr = 'qr';
    case Face = 'face';

    public function label(): string
    {
        return match ($this) {
            self::Qr => 'QR',
            self::Face => 'Face',
        };
    }
}
