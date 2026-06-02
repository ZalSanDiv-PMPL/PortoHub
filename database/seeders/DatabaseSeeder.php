<?php

namespace Database\Seeders;

use App\Models\ClassAssignment;
use App\Models\GithubToken;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

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
            'password' => env('SEED_ADMIN_PASSWORD', 'password'),
            'password_set_at' => now(),
            'email_verified_at' => now(),
        ]);

        // Akun Testing Khusus (Tupai Kidal)
        $tupaiUser = User::updateOrCreate([
            'email' => 'tupaikidal@portohub.test',
        ], [
            'name' => 'Tupai Kidal',
            'role' => 'student',
            'password' => env('SEED_TEST_PASSWORD', 'password'),
            'password_set_at' => now(),
        ]);

        Student::updateOrCreate(
            ['user_id' => $tupaiUser->id],
            [
                'nis' => '2026099',
                'year' => 2024,
                'phone' => '08129999999',
                'address' => 'Jakarta, Indonesia',
                'is_validated' => true,
            ]
        );

        GithubToken::updateOrCreate(
            ['user_id' => $tupaiUser->id],
            [
                'github_id' => 99999999,
                'github_username' => 'tupaikidal-dev',
                'access_token' => 'dummy_token_'.Str::random(10),
                'is_active' => true,
            ]
        );

        $this->call([
            DemoPortfolioSeeder::class,
        ]);

        // Hubungkan Tupai Kidal ke kelas yang diajar oleh Pak Hendra
        $teacher = Teacher::whereHas('user', function ($q) {
            $q->where('email', 'hendra.rpl@portohub.test');
        })->first();

        $tupaiStudent = Student::where('nis', '2026099')->first();

        if ($teacher && $tupaiStudent) {
            ClassAssignment::updateOrCreate(
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
