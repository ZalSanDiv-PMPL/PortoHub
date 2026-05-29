<?php

namespace Database\Factories;

use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class StudentFactory extends Factory
{
    protected $model = Student::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'nis' => $this->faker->numerify('##########'),
            'class' => $this->faker->randomElement(['XII RPL 1', 'XII RPL 2', 'XI RPL 1', 'XI RPL 2']),
            'year' => $this->faker->randomElement([2024, 2025, 2026]),
            'phone' => $this->faker->phoneNumber(),
            'address' => $this->faker->address(),
            'github_username' => $this->faker->userName(),
            'is_validated' => true,
        ];
    }

    public function unvalidated(): static
    {
        return $this->state(fn () => ['is_validated' => false]);
    }
}
