<?php

namespace App\Services;

use App\Models\Student;

class FaceRecognitionService
{
    public function length(): int
    {
        return (int) config('attendance.face_descriptor_length', 128);
    }

    public function threshold(): float
    {
        return (float) config('attendance.face_match_threshold', 0.5);
    }

    /**
     * @param  list<mixed>  $descriptor
     * @return list<float>|null
     */
    public function normalize(array $descriptor): ?array
    {
        if (count($descriptor) !== $this->length()) {
            return null;
        }

        $normalized = [];

        foreach ($descriptor as $value) {
            if (! is_numeric($value)) {
                return null;
            }

            $number = (float) $value;

            if (! is_finite($number)) {
                return null;
            }

            $normalized[] = $number;
        }

        return $normalized;
    }

    /**
     * @param  list<float>  $left
     * @param  list<float>  $right
     */
    public function distance(array $left, array $right): float
    {
        $sum = 0.0;
        $count = min(count($left), count($right), $this->length());

        for ($index = 0; $index < $count; $index++) {
            $delta = $left[$index] - $right[$index];
            $sum += $delta * $delta;
        }

        return sqrt($sum);
    }

    public function reset(Student $student): Student
    {
        $student->forceFill([
            'face_descriptor' => null,
            'face_enrolled_at' => null,
        ])->save();

        return $student->fresh(['section']) ?? $student;
    }

    /**
     * @param  list<float>  $descriptor
     */
    public function enroll(Student $student, array $descriptor): Student
    {
        $student->forceFill([
            'face_descriptor' => $descriptor,
            'face_enrolled_at' => now(),
        ])->save();

        return $student->fresh(['section']) ?? $student;
    }

    /**
     * @param  list<float>  $descriptor
     */
    public function match(array $descriptor, ?int $schoolId): ?Student
    {
        $bestStudent = null;
        $bestDistance = INF;
        $secondDistance = INF;
        $threshold = $this->threshold();
        $margin = (float) config('attendance.face_ambiguity_margin', 0.08);

        $candidates = Student::query()
            ->whereNotNull('face_descriptor')
            ->when($schoolId, fn ($query) => $query->where('school_id', $schoolId))
            ->get();

        foreach ($candidates as $student) {
            $stored = $this->normalize($student->face_descriptor ?? []);

            if ($stored === null) {
                continue;
            }

            $distance = $this->distance($descriptor, $stored);

            if ($distance < $bestDistance) {
                $secondDistance = $bestDistance;
                $bestDistance = $distance;
                $bestStudent = $student;
            } elseif ($distance < $secondDistance) {
                $secondDistance = $distance;
            }
        }

        if ($bestStudent === null || $bestDistance > $threshold) {
            return null;
        }

        if (is_finite($secondDistance) && ($secondDistance - $bestDistance) < $margin) {
            return null;
        }

        return $bestStudent;
    }
}
