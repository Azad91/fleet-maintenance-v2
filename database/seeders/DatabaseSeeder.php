<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Two-tier seeding model:
     *
     *   1. UserSeeder runs in EVERY environment.
     *      It creates exactly one account — the platform SuperAdmin.
     *      Everything else (companies, garages, tenants) is created
     *      through the UI by that SuperAdmin.
     *
     *   2. DemoDataSeeder runs ONLY outside production.
     *      It creates a rich set of demo companies, garages, users
     *      and domain data so developers and QA can exercise every
     *      screen without manual setup.
     *
     * The split keeps production data clean: no customer name is
     * ever hard-coded into a migration or seeder.
     */
    public function run(): void
    {
        $this->call(UserSeeder::class);

        if (! app()->environment('production')) {
            $this->call(DemoDataSeeder::class);
        }
    }
}
