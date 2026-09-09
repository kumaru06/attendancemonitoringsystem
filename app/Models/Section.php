<?php

namespace App\Models;

use Database\Factories\SectionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name'])]
class Section extends Model
{
    /** @use HasFactory<SectionFactory> */
    use HasFactory;

    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }
}
