<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AdminSeeder extends Seeder
{
    public function run()
    {
        User::updateOrCreate(
            ['email' => 'admin@assist-grp.jp'],
            [
                'name' => '管理者',
                'password' => Hash::make('password123'),
            ]
        );
    }
}
