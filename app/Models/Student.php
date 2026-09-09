<?php

namespace App\Models;

use Database\Factories\StudentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['student_number', 'first_name', 'middle_name', 'last_name', 'section_id', 'photo_path', 'is_active'])]
class Student extends Model
{
    /** @use HasFactory<StudentFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    protected function fullName(): Attribute
    {
        return Attribute::get(fn (): string => trim(implode(' ', array_filter([
            $this->first_name,
            $this->middle_name,
            $this->last_name,
        ]))));
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    public function qrCredentials(): HasMany
    {
        return $this->hasMany(StudentQrCredential::class);
    }

    public function currentQrCredential(): HasOne
    {
        return $this->hasOne(StudentQrCredential::class)->whereNull('revoked_at')->latestOfMany();
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }
}
