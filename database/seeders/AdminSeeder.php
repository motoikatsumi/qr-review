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
            ['email' => 'info@assist-grp.jp'],
            [
                'name' => '管理者',
                'password' => Hash::make('A$s1st#Qr!2026xZ'),
            ]
        );
    }
}
