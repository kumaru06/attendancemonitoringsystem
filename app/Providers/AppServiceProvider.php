<?php

namespace App\Providers;

use App\Models\Attendance;
use App\Models\Section;
use App\Models\Student;
use App\Models\User;
use App\Policies\AttendancePolicy;
use App\Policies\SectionPolicy;
use App\Policies\StudentPolicy;
use App\Policies\UserPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::policy(Student::class, StudentPolicy::class);
        Gate::policy(Attendance::class, AttendancePolicy::class);
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Section::class, SectionPolicy::class);

        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute((int) config('attendance.login_rate_limit_per_minute', 5))
                ->by($request->ip());
        });

        RateLimiter::for('scan', function (Request $request) {
            return Limit::perMinute((int) config('attendance.scan_rate_limit_per_minute', 30))
                ->by((string) ($request->user()?->id ?: $request->ip()));
        });
    }
}
