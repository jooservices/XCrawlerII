<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => config('seed.admin.email')],
            [
                'name' => config('seed.admin.name'),
                'password' => Hash::make(config('seed.admin.password')),
                'email_verified_at' => now(),
            ]
        );
    }
}
