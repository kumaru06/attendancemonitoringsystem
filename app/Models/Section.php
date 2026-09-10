<?php

namespace App\Models;

use App\Enums\SchoolLevel;
use App\Models\Concerns\BelongsToSchool;
use Database\Factories\SectionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'level', 'school_id'])]
class Section extends Model
{
    /** @use HasFactory<SectionFactory> */
    use BelongsToSchool, HasFactory;

    protected function casts(): array
    {
        return [
            'level' => SchoolLevel::class,
        ];
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }

    public function labeledName(): string
    {
        $level = $this->level?->label();

        return $level ? $level.' · '.$this->name : $this->name;
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query
            ->orderByRaw(SchoolLevel::orderBySql())
            ->orderBy('name');
    }
}
