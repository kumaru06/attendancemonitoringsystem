<?php

namespace App\Http\Controllers;

use App\Enums\SchoolLevel;
use App\Models\Attendance;
use App\Models\Student;
use App\Services\AttendanceService;
use App\Services\WeatherService;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(AttendanceService $attendanceService, WeatherService $weather): View
    {
        $today = $attendanceService->today();
        $now = $attendanceService->now();
        $yesterday = $now->subDay()->toDateString();

        $activeStudents = Student::query()->where('is_active', true);
        $totalActive = (clone $activeStudents)->count();

        $presentToday = Attendance::query()
            ->whereDate('attendance_date', $today)
            ->whereHas('student', fn ($query) => $query->where('is_active', true))
            ->count();

        $presentYesterday = Attendance::query()
            ->whereDate('attendance_date', $yesterday)
            ->whereHas('student', fn ($query) => $query->where('is_active', true))
            ->count();

        $recent = Attendance::query()
            ->with(['student.section', 'recorder'])
            ->whereDate('attendance_date', $today)
            ->latest('id')
            ->limit(10)
            ->get();

        $user = auth()->user();
        $bannerPath = public_path('images/dashboard-banner.jpg');

        return view('dashboard.index', [
            'now' => $now,
            'timezone' => $attendanceService->timezone(),
            'totalActive' => $totalActive,
            'presentToday' => $presentToday,
            'presentDelta' => $this->percentChange($presentToday, $presentYesterday),
            'notYetCheckedIn' => max($totalActive - $presentToday, 0),
            'levelStats' => $this->levelStats($today),
            'recent' => $recent,
            'weather' => $weather->current(),
            'firstName' => Str::of((string) $user?->name)->trim()->before(' ')->toString(),
            'schoolName' => $user?->school?->name,
            'greeting' => $this->greetingForHour((int) $now->format('G')),
            'gmtLabel' => $this->gmtLabel($now->utcOffset()),
            'bannerUrl' => is_file($bannerPath) ? asset('images/dashboard-banner.jpg') : null,
        ]);
    }

    /**
     * @return Collection<int, array{level: SchoolLevel, total: int, present: int}>
     */
    private function levelStats(string $today): Collection
    {
        $schoolId = auth()->user()?->school_id;

        $activeByLevel = Student::query()
            ->where('students.is_active', true)
            ->join('sections', 'sections.id', '=', 'students.section_id')
            ->when($schoolId, fn ($query) => $query->where('sections.school_id', $schoolId))
            ->groupBy('sections.level')
            ->selectRaw('sections.level as level, COUNT(*) as total')
            ->pluck('total', 'level');

        $presentByLevel = Attendance::query()
            ->whereDate('attendances.attendance_date', $today)
            ->join('students', 'students.id', '=', 'attendances.student_id')
            ->join('sections', 'sections.id', '=', 'students.section_id')
            ->where('students.is_active', true)
            ->when($schoolId, function ($query) use ($schoolId) {
                $query->where('students.school_id', $schoolId)
                    ->where('sections.school_id', $schoolId);
            })
            ->groupBy('sections.level')
            ->selectRaw('sections.level as level, COUNT(*) as total')
            ->pluck('total', 'level');

        return collect(SchoolLevel::cases())->map(fn (SchoolLevel $level): array => [
            'level' => $level,
            'total' => (int) $activeByLevel->get($level->value, 0),
            'present' => (int) $presentByLevel->get($level->value, 0),
        ]);
    }

    private function percentChange(int $current, int $previous): ?int
    {
        if ($previous === 0) {
            return $current === 0 ? 0 : null;
        }

        return (int) round((($current - $previous) / $previous) * 100);
    }

    private function greetingForHour(int $hour): string
    {
        return match (true) {
            $hour < 12 => 'Good morning',
            $hour < 18 => 'Good afternoon',
            default => 'Good evening',
        };
    }

    private function gmtLabel(int $offsetSeconds): string
    {
        $hours = intdiv($offsetSeconds, 3600);

        return 'GMT'.($hours >= 0 ? '+' : '').$hours;
    }
}
