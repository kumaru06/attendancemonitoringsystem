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
use App\View\Composers\AppLayoutComposer;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $publicHtml = $this->app->basePath('public_html');

        if (is_dir($publicHtml)) {
            $this->app->usePublicPath($publicHtml);
        }
    }

    public function boot(): void
    {
        if (str_starts_with((string) config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }

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

        RateLimiter::for('search', function (Request $request) {
            return Limit::perMinute(30)->by((string) ($request->user()?->id ?: $request->ip()));
        });

        View::composer('layouts.app', AppLayoutComposer::class);
    }
}
