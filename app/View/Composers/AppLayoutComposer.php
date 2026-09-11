<?php

namespace App\View\Composers;

use App\Models\Attendance;
use App\Models\Student;
use App\Services\AttendanceService;
use Illuminate\View\View;

class AppLayoutComposer
{
    public function __construct(private readonly AttendanceService $attendance) {}

    public function compose(View $view): void
    {
        $user = auth()->user();

        if (! $user?->isAdmin()) {
            $view->with('notYetCheckedIn', 0);

            return;
        }

        $today = $this->attendance->today();
        $totalActive = Student::query()->where('is_active', true)->count();
        $presentToday = Attendance::query()
            ->whereDate('attendance_date', $today)
            ->whereHas('student', fn ($query) => $query->where('is_active', true))
            ->count();

        $view->with('notYetCheckedIn', max($totalActive - $presentToday, 0));
    }
}
