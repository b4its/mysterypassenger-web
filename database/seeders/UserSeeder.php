<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $accounts = [
            [
                'name' => 'Administrator',
                'username' => 'admin',
                'email' => 'admin@mysterypassenger.test',
                'role' => UserRole::Admin,
                'organization' => 'Direktorat Jenderal Perhubungan',
            ],
            [
                'name' => 'Reviewer Utama',
                'username' => 'reviewer',
                'email' => 'reviewer@mysterypassenger.test',
                'role' => UserRole::Reviewer,
                'organization' => 'Direktorat Jenderal Perhubungan',
            ],
            [
                'name' => 'Surveyor Lapangan',
                'username' => 'surveyor',
                'email' => 'surveyor@mysterypassenger.test',
                'role' => UserRole::Surveyor,
                'organization' => 'Balai Pengelola Transportasi',
            ],
        ];

        foreach ($accounts as $account) {
            User::updateOrCreate(
                ['email' => $account['email']],
                $account + [
                    'password' => Hash::make('password'),
                    'is_active' => true,
                ],
            );
        }
    }
}
