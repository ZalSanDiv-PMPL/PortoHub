<?php

namespace Database\Factories;

use App\Models\Validation;
use App\Models\Project;
use App\Models\Teacher;
use Illuminate\Database\Eloquent\Factories\Factory;

class ValidationFactory extends Factory
{
    protected $model = Validation::class;

    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'teacher_id' => Teacher::factory(),
            'is_approved' => true,
            'validation_date' => now(),
            'notes' => $this->faker->sentence(),
            'functionality_score' => $this->faker->numberBetween(60, 100),
            'code_quality_score' => $this->faker->numberBetween(60, 100),
            'documentation_score' => $this->faker->numberBetween(60, 100),
            'originality_score' => $this->faker->numberBetween(60, 100),
        ];
    }

    public function rejected(): static
    {
        return $this->state(fn () => ['is_approved' => false]);
    }
}
