<?php

namespace Database\Factories;

use App\Models\Section;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Student>
 */
class StudentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'student_number' => fake()->unique()->numerify('2026-#####'),
            'first_name' => fake()->firstName(),
            'middle_name' => fake()->optional(0.6)->firstName(),
            'last_name' => fake()->lastName(),
            'section_id' => Section::factory(),
            'photo_path' => null,
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
