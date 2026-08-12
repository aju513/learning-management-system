<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        Artisan::call('admin:permissions-sync');
        Artisan::call('admin:menu-regenerate');

        if (app()->environment('local')) {
            $this->call(LmsDemoSeeder::class);
        }
    }
}
