<?php

namespace Database\Factories;

use App\Models\ClassAssignment;
use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Database\Eloquent\Factories\Factory;

class ClassAssignmentFactory extends Factory
{
    protected $model = ClassAssignment::class;

    public function definition(): array
    {
        return [
            'student_id' => Student::factory(),
            'teacher_id' => Teacher::factory(),
            'class' => $this->faker->randomElement(['XII RPL 1', 'XII RPL 2', 'XI RPL 1', 'XI RPL 2']),
            'semester' => $this->faker->randomElement(['Ganjil 2025/2026', 'Genap 2025/2026']),
            'is_active' => true,
        ];
    }
}
