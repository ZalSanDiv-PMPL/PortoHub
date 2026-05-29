<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProjectFactory extends Factory
{
    protected $model = Project::class;

    public function definition(): array
    {
        return [
            'student_id' => Student::factory(),
            'title' => $this->faker->sentence(4),
            'description' => $this->faker->paragraph(3),
            'thumbnail_path' => null,
            'development_model' => $this->faker->randomElement(['waterfall', 'agile', 'other']),
            'github_url' => 'https://github.com/' . $this->faker->userName() . '/' . $this->faker->slug(2),
            'status' => 'submitted',
            'visibility' => 'public',
            'tech_stack' => null,
            'submission_date' => now(),
            'approval_date' => null,
            'rejection_reason' => null,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn () => [
            'status' => 'approved',
            'approval_date' => now(),
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn () => [
            'status' => 'rejected',
            'rejection_reason' => $this->faker->sentence(),
        ]);
    }

    public function private(): static
    {
        return $this->state(fn () => ['visibility' => 'private']);
    }

    public function restricted(): static
    {
        return $this->state(fn () => ['visibility' => 'restricted']);
    }
}
