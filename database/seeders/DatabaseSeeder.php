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
        // Demo login (password: "password", from UserFactory). Demo schools, the MoEYS subject
        // template, users per role and 200 Khmer-named students arrive with their tables in M1–M3.
        User::factory()->create([
            'name' => 'អ្នកគ្រប់គ្រង',
            'email' => 'admin@sala.test',
        ]);
    }
}
