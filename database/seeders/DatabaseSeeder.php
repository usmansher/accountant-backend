<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Entry;
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

        Account::all()->runForEach(function () {
            Entry::factory()
                ->hasItems(4) // Each Entry has 5 EntryItems
                ->count(20)        // Create 10 Entries
                ->create();
        });



        // User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);
    }
}
