<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Hidden(['token_hash', 'encrypted_token'])]
class StudentQrCredential extends Model
{
    protected $fillable = [
        'student_id',
        'token_hash',
        'encrypted_token',
        'revoked_at',
    ];

    protected function casts(): array
    {
        return [
            'revoked_at' => 'datetime',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function isCurrent(): bool
    {
        return $this->revoked_at === null;
    }
}
