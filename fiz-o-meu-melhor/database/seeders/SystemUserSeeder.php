<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SystemUserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'system@local.test'],
            [
                'name' => 'System User',
                'password' => Hash::make('system-password'),
            ]
        );
    }
}

