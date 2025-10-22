<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $password = (string) config('telegram.user.password');

        if (Hash::needsRehash($password)) {
            $password = Hash::make($password);
        }

        $user = User::firstOrCreate(
            ['telegram_user_id' => config('telegram.user.user_id')],
            [
                'name' => config('telegram.user.name'),
                'email' => config('telegram.user.email'),
                'password' => $password,
            ]
        );
        $user->is_admin = true;
        $user->save();
    }
}
