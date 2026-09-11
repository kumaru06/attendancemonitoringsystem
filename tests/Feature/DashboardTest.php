<?php

namespace Tests\Feature;

use App\Enums\SchoolLevel;
use App\Models\Attendance;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_dashboard_renders_the_welcome_hero_and_empty_attendance_state(): void
    {
        $this->travelTo(now('Asia/Manila')->setTime(13, 0, 0));
        $this->fakeManilaWeather();

        $admin = User::factory()->admin()->create([
            'name' => 'Maria Santos',
        ]);

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Welcome back, Maria!')
            ->assertSee('Good afternoon')
            ->assertSee('Keep Tracking, Keep Growing.')
            ->assertSee('Scan Student')
            ->assertSee('Daily Entrance Monitoring')
            ->assertSee('Settings')
            ->assertDontSee('Reports')
            ->assertSee($admin->school->name)
            ->assertSee('No attendance has been recorded yet today.')
            ->assertSee('Student scan records will appear here once available.')
            ->assertSee('Search student, section, or scan...', false);
    }

    public function test_dashboard_shows_manila_weather(): void
    {
        $this->travelTo(now('Asia/Manila')->setTime(9, 0, 0));
        $this->fakeManilaWeather();

        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('32°C')
            ->assertSee('Mostly sunny');

        Http::assertSent(fn ($request): bool => str_contains($request->url(), 'api.open-meteo.com/v1/forecast'));
    }

    public function test_dashboard_still_renders_when_weather_is_unavailable(): void
    {
        $this->travelTo(now('Asia/Manila')->setTime(9, 0, 0));
        Http::preventStrayRequests();
        Http::fake([
            'api.open-meteo.com/*' => Http::failedConnection(),
        ]);

        $admin = User::factory()->admin()->create([
            'name' => 'Maria Santos',
        ]);

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Welcome back, Maria!')
            ->assertDontSee('°C');
    }

    public function test_dashboard_escapes_the_administrator_name(): void
    {
        $this->travelTo(now('Asia/Manila')->setTime(9, 0, 0));
        $this->fakeManilaWeather();

        $admin = User::factory()->admin()->create([
            'name' => "<script>alert('xss')</script>",
        ]);

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee("<script>alert('xss')</script>", false)
            ->assertSee('&lt;script&gt;', false);
    }

    public function test_dashboard_shows_present_percent_change_against_yesterday(): void
    {
        $this->travelTo(now('Asia/Manila')->setTime(9, 0, 0));
        $this->fakeManilaWeather();

        $admin = User::factory()->admin()->create();
        $section = $this->sectionFor($admin, ['level' => SchoolLevel::College]);
        $todayStudent = $this->studentFor($admin, [
            'first_name' => 'TodayKid',
            'section_id' => $section->id,
        ]);
        $bothStudent = $this->studentFor($admin, [
            'first_name' => 'BothKid',
            'section_id' => $section->id,
        ]);

        Attendance::factory()->create([
            'student_id' => $bothStudent->id,
            'attendance_date' => now('Asia/Manila')->subDay()->toDateString(),
            'recorded_by' => $admin->id,
        ]);
        Attendance::factory()->create([
            'student_id' => $bothStudent->id,
            'attendance_date' => now('Asia/Manila')->toDateString(),
            'recorded_by' => $admin->id,
        ]);
        Attendance::factory()->create([
            'student_id' => $todayStudent->id,
            'attendance_date' => now('Asia/Manila')->toDateString(),
            'recorded_by' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('+100%');
    }
}
