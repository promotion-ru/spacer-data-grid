<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();
        /** @var User $user */
        $user = new User();
        $user->name = 'Vlad';
        $user->surname = 'Admin';
        $user->email = 'admin@admin.ru';
        $user->password = bcrypt('123123123');
        $user->save();
    }
}
