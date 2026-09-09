<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Student;
use App\Services\AttendanceService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(AttendanceService $attendanceService): View
    {
        $today = $attendanceService->today();

        $activeStudents = Student::query()->where('is_active', true);
        $totalActive = (clone $activeStudents)->count();

        $presentToday = Attendance::query()
            ->whereDate('attendance_date', $today)
            ->whereHas('student', fn ($query) => $query->where('is_active', true))
            ->count();

        $recent = Attendance::query()
            ->with(['student.section', 'recorder'])
            ->whereDate('attendance_date', $today)
            ->latest('id')
            ->limit(10)
            ->get();

        return view('dashboard.index', [
            'now' => $attendanceService->now(),
            'timezone' => $attendanceService->timezone(),
            'totalActive' => $totalActive,
            'presentToday' => $presentToday,
            'notYetCheckedIn' => max($totalActive - $presentToday, 0),
            'recent' => $recent,
        ]);
    }
}
