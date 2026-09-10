<?php

namespace App\Models;

use App\Enums\StudentGender;
use App\Models\Concerns\BelongsToSchool;
use Database\Factories\StudentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['student_number', 'first_name', 'middle_name', 'last_name', 'gender', 'section_id', 'school_id', 'photo_path', 'is_active'])]
class Student extends Model
{
    /** @use HasFactory<StudentFactory> */
    use BelongsToSchool, HasFactory;

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'gender' => StudentGender::class,
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

    protected function initials(): Attribute
    {
        return Attribute::get(function (): string {
            $letters = array_filter([
                mb_substr((string) $this->first_name, 0, 1),
                mb_substr((string) $this->last_name, 0, 1),
            ]);

            return mb_strtoupper(implode('', $letters));
        });
    }

    public function sf2Name(): string
    {
        $name = $this->last_name.', '.$this->first_name;

        if (filled($this->middle_name)) {
            $name .= ', '.$this->middle_name;
        }

        return $name;
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
