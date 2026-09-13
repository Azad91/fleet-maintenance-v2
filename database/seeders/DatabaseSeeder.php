<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            GarageSeeder::class,
            ComplaintTypeSeeder::class,
            ServiceTemplateSeeder::class,
        ]);

        // Only seed demo data outside production
        if (! app()->environment('production')) {
            $this->call(DemoDataSeeder::class);
        }
    }
}
