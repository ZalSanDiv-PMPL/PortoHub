<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Student;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Admin User
        User::updateOrCreate([
            'email' => 'admin@portohub.local',
        ], [
            'name' => 'Admin PortoHub',
            'role' => 'admin',
            'password' => 'password',
            'password_set_at' => now(),
            'email_verified_at' => now(),
        ]);

        // Akun Testing Khusus (Tupai Kidal)
        $tupaiUser = User::updateOrCreate([
            'email' => 'tupaikidal',
        ], [
            'name' => 'Tupai Kidal',
            'role' => 'student',
            'password' => 'Kambingguling_001',
            'password_set_at' => now(),
        ]);

        Student::updateOrCreate(
            ['user_id' => $tupaiUser->id],
            [
                'nis' => '2026099',
                'class' => 'X RPL B',
                'year' => 2024,
                'phone' => '08129999999',
                'address' => 'Jakarta, Indonesia',
                'github_username' => 'tupaikidal-dev',
                'is_validated' => true,
            ]
        );

        $this->call([
            DemoPortfolioSeeder::class,
        ]);

        // Hubungkan Tupai Kidal ke kelas yang diajar oleh Pak Hendra
        $teacher = \App\Models\Teacher::whereHas('user', function($q) {
            $q->where('email', 'hendra.rpl@portohub.test');
        })->first();

        $tupaiStudent = Student::where('nis', '2026099')->first();

        if ($teacher && $tupaiStudent) {
            \App\Models\ClassAssignment::updateOrCreate(
                [
                    'teacher_id' => $teacher->id,
                    'student_id' => $tupaiStudent->id,
                    'class' => 'X RPL B',
                    'semester' => 2,
                ],
                [
                    'is_active' => true,
                ]
            );
        }
    }
}
