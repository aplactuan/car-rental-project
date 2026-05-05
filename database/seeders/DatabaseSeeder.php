<?php

namespace Database\Seeders;

use App\Models\Car;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\Customer;
use App\Models\Driver;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
        ]);

        if (config('app.env') !== 'production') {
            Car::factory(10)->create();
            Driver::factory(10)->create();
            Customer::factory(10)->create();
        }
    }
}
