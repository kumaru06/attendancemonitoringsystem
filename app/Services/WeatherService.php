<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

class WeatherService
{
    /**
     * @return array{temperature: int, summary: string}|null
     */
    public function current(): ?array
    {
        return Cache::remember('weather:manila:current', (int) config('attendance.weather.cache_seconds', 1800), function (): ?array {
            try {
                $response = Http::connectTimeout(2)
                    ->timeout(4)
                    ->get('https://api.open-meteo.com/v1/forecast', [
                        'latitude' => (float) config('attendance.weather.latitude', 14.5995),
                        'longitude' => (float) config('attendance.weather.longitude', 120.9842),
                        'current' => 'temperature_2m,weather_code',
                        'timezone' => (string) config('attendance.timezone', 'Asia/Manila'),
                    ]);
            } catch (ConnectionException|RequestException) {
                return null;
            } catch (Throwable) {
                return null;
            }

            if (! $response->successful()) {
                return null;
            }

            $temperature = $response->json('current.temperature_2m');
            $code = $response->json('current.weather_code');

            if (! is_numeric($temperature) || ! is_numeric($code)) {
                return null;
            }

            return [
                'temperature' => (int) round((float) $temperature),
                'summary' => $this->summaryFor((int) $code),
            ];
        });
    }

    private function summaryFor(int $code): string
    {
        return match (true) {
            $code === 0 => 'Sunny',
            $code === 1 => 'Mostly sunny',
            $code === 2 => 'Partly cloudy',
            $code === 3 => 'Cloudy',
            in_array($code, [45, 48], true) => 'Foggy',
            $code >= 51 && $code <= 57 => 'Drizzle',
            $code >= 61 && $code <= 67 => 'Rain',
            $code >= 71 && $code <= 77 => 'Snow',
            $code >= 80 && $code <= 82 => 'Showers',
            $code >= 95 => 'Stormy',
            default => 'Fair',
        };
    }
}
