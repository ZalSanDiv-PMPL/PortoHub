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
        // User::factory(10)->create();

        User::updateOrCreate([
            'email' => 'test@example.com',
        ], [
            'name' => 'Test User',
            'password' => 'password',
            'password_set_at' => now(),
        ]);

        User::updateOrCreate([
            'email' => 'tupaikidal',
        ], [
            'name' => 'Tupai Kidal',
            'role' => 'student',
            'password' => 'Kambingguling_001',
            'password_set_at' => now(),
        ]);

        $this->call([
            DemoPortfolioSeeder::class,
        ]);
    }
}
