<?php

namespace Database\Seeders;

use App\Enums\RolesEnum;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            ['name' => 'Sara Haddad', 'email' => 'sara@auction.test'],
            ['name' => 'Omar Nasser', 'email' => 'omar@auction.test'],
            ['name' => 'Lina Fares', 'email' => 'lina@auction.test'],
        ];

        foreach ($users as $user) {
            User::firstOrCreate(
                ['email' => $user['email']],
                [
                    'name' => $user['name'],
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                ],
            )->syncRoles(RolesEnum::USER->value);
        }
    }
}
