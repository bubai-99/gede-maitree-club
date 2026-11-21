<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database: the books and fee plans, then SAMPLE content.
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            BookSeeder::class,
            SampleDataSeeder::class,
            SampleUsersSeeder::class,
            SampleAccountsSeeder::class,
            SampleEventsSeeder::class,
            SamplePanjikaSeeder::class,
            SampleNewsSeeder::class,
        ]);
    }
}
