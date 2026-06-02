<?php

namespace Database\Seeders;

use App\Models\User;
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
        // Admin User Pusat
        User::updateOrCreate([
            'email' => 'admin@portohub.local',
        ], [
            'name' => 'Admin PortoHub',
            'role' => 'admin',
            'password' => env('SEED_ADMIN_PASSWORD', 'password'),
            'password_set_at' => now(),
            'email_verified_at' => now(),
            'is_active' => true,
        ]);

        // Panggil Seeder skenario utama (Guru, Siswa, dan Proyek)
        $this->call([
            DemoPortfolioSeeder::class,
        ]);
    }
}
