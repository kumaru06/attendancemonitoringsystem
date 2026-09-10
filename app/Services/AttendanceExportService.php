<?php

namespace App\Services;

use App\Models\Attendance;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttendanceExportService
{
    public function filteredQuery(array $filters): Builder
    {
        $timezone = config('attendance.timezone');

        return Attendance::query()
            ->with(['student.section', 'recorder'])
            ->when($filters['from'] ?? null, fn (Builder $query, string $from) => $query->whereDate('attendance_date', '>=', $from))
            ->when($filters['to'] ?? null, fn (Builder $query, string $to) => $query->whereDate('attendance_date', '<=', $to))
            ->when($filters['section_id'] ?? null, function (Builder $query, int $sectionId) {
                $query->whereHas('student', fn (Builder $student) => $student->where('section_id', $sectionId));
            })
            ->when($filters['level'] ?? null, function (Builder $query, string $level) {
                $query->whereHas('student.section', fn (Builder $section) => $section->where('level', $level));
            })
            ->when($filters['search'] ?? null, function (Builder $query, string $search) {
                $query->whereHas('student', function (Builder $student) use ($search) {
                    $student->where(function (Builder $inner) use ($search) {
                        $inner->where('student_number', 'like', "%{$search}%")
                            ->orWhere('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('middle_name', 'like', "%{$search}%");
                    });
                });
            })
            ->orderByDesc('attendance_date')
            ->orderBy('time_in');
    }

    public function download(array $filters): StreamedResponse
    {
        $filename = 'attendance-'.$this->safeFilename(now($timezone = config('attendance.timezone'))->format('Ymd-His')).'.csv';

        return response()->streamDownload(function () use ($filters) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'USN/ID Number',
                'Student name',
                'Level',
                'Section/course',
                'Attendance date',
                'Time-in',
                'Status',
                'Recorded by',
            ]);

            $this->filteredQuery($filters)
                ->lazy(200)
                ->each(function (Attendance $attendance) use ($handle) {
                    fputcsv($handle, [
                        $this->sanitize($attendance->student?->student_number),
                        $this->sanitize($attendance->student?->full_name),
                        $this->sanitize($attendance->student?->section?->level?->label()),
                        $this->sanitize($attendance->student?->section?->name),
                        $this->sanitize(optional($attendance->attendance_date)->toDateString()),
                        $this->sanitize((string) $attendance->time_in),
                        $this->sanitize($attendance->status),
                        $this->sanitize($attendance->recorder?->recorderLabel()),
                    ]);
                });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function sanitize(?string $value): string
    {
        $value = (string) $value;

        if ($value !== '' && preg_match('/^[=+\-@\t\r]/', $value) === 1) {
            return "'".$value;
        }

        return $value;
    }

    private function safeFilename(string $value): string
    {
        return preg_replace('/[^A-Za-z0-9._-]/', '-', $value) ?: 'export';
    }
}
