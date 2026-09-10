<?php

namespace Database\Factories;

use App\Enums\SchoolLevel;
use App\Models\School;
use App\Models\Section;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Section>
 */
class SectionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->bothify('Grade ##-??'),
            'level' => SchoolLevel::Shs,
            'school_id' => School::factory(),
        ];
    }

    public function kinder(): static
    {
        return $this->state(fn (array $attributes): array => [
            'level' => SchoolLevel::Kinder,
        ]);
    }

    public function elementary(): static
    {
        return $this->state(fn (array $attributes): array => [
            'level' => SchoolLevel::Elementary,
        ]);
    }

    public function jhs(): static
    {
        return $this->state(fn (array $attributes): array => [
            'level' => SchoolLevel::Jhs,
        ]);
    }

    public function shs(): static
    {
        return $this->state(fn (array $attributes): array => [
            'level' => SchoolLevel::Shs,
        ]);
    }

    public function college(): static
    {
        return $this->state(fn (array $attributes): array => [
            'level' => SchoolLevel::College,
        ]);
    }
}
