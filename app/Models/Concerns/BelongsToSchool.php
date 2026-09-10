<?php

namespace App\Models\Concerns;

use App\Models\Attendance;
use App\Models\School;
use App\Models\Section;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToSchool
{
    public static function bootBelongsToSchool(): void
    {
        static::addGlobalScope('school', function (Builder $query): void {
            $schoolId = auth()->user()?->school_id;

            if (! $schoolId) {
                return;
            }

            $query->where($query->getModel()->getTable().'.school_id', $schoolId);
        });

        static::creating(function (Model $model): void {
            if ($model->getAttribute('school_id')) {
                return;
            }

            if ($model instanceof Student && $model->getAttribute('section_id')) {
                $sectionSchoolId = Section::withoutGlobalScopes()->find($model->getAttribute('section_id'))?->school_id;

                if ($sectionSchoolId) {
                    $model->setAttribute('school_id', $sectionSchoolId);

                    return;
                }
            }

            if ($model instanceof Attendance && $model->getAttribute('student_id')) {
                $studentSchoolId = Student::withoutGlobalScopes()->find($model->getAttribute('student_id'))?->school_id;

                if ($studentSchoolId) {
                    $model->setAttribute('school_id', $studentSchoolId);

                    return;
                }
            }

            $userSchoolId = auth()->user()?->school_id;

            if ($userSchoolId) {
                $model->setAttribute('school_id', $userSchoolId);
            }
        });
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function belongsToSchoolOf(?User $user): bool
    {
        return $user?->school_id !== null
            && (int) $this->school_id === (int) $user->school_id;
    }
}
