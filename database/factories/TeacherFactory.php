<?php

namespace Database\Factories;

use App\Models\Teacher;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TeacherFactory extends Factory
{
    protected $model = Teacher::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'nip' => $this->faker->numerify('##################'),
            'specialization' => $this->faker->randomElement(['Pemrograman Web', 'Basis Data', 'Mobile Development', 'Jaringan Komputer']),
            'department' => 'Rekayasa Perangkat Lunak',
            'phone' => $this->faker->phoneNumber(),
            'address' => $this->faker->address(),
            'is_validated' => true,
        ];
    }
}
