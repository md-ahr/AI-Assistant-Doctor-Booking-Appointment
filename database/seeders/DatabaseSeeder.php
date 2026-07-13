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
        User::query()->updateOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'password' => 'password',
                'email_verified_at' => now(),
                'is_admin' => true,
            ],
        );

        User::query()->updateOrCreate(
            ['email' => 'member@example.com'],
            [
                'name' => 'Demo Member',
                'password' => 'password',
                'email_verified_at' => now(),
                'is_admin' => false,
            ],
        );

        $this->call([DoctorSeeder::class]);
    }
}
