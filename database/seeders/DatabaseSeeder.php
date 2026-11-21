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
        $email = config('admin.email');
        $password = config('admin.password');

        if (! $email || ! $password) {
            return;
        }

        User::updateOrCreate(
            ['email' => $email],
            [
                'name' => config('admin.name'),
                'password' => $password,
                'is_admin' => true,
                'email_verified_at' => now(),
            ],
        );
    }
}
