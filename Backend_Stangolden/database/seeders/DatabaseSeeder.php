<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash; // tambahkan import Hash

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // contoh: buat admin default
        User::factory()->create([
            'name' => 'Admin Utama',
            'email' => 'admin@stangolden.com',
            'password' => Hash::make('admin123'),
            'role' => 'admin',
            // tambahkan field lain jika model Anda butuh default tertentu
             'approved' => true,
             'active' => true,
        ]);

        // Jika ingin tambah user siswa dummy:
         User::factory(10)->create();
    }
}