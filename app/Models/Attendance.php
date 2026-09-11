<?php

namespace App\Models;

use App\Enums\AttendanceMethod;
use App\Models\Concerns\BelongsToSchool;
use Database\Factories\AttendanceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['student_id', 'attendance_date', 'time_in', 'status', 'method', 'recorded_by', 'school_id'])]
class Attendance extends Model
{
    /** @use HasFactory<AttendanceFactory> */
    use BelongsToSchool, HasFactory;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'method' => AttendanceMethod::Qr->value,
    ];

    protected function casts(): array
    {
        return [
            'attendance_date' => 'date',
            'method' => AttendanceMethod::class,
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
