<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::where('email', 'admin@'.'dan'.'huong'.'chay.vn')->delete();

        User::updateOrCreate(
            ['email' => 'admin@paprika-patras.gr'],
            [
                'name' => 'Admin Paprika',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'role_id' => Role::where('slug', 'super-admin')->value('id'),
            ]
        );
    }
}
