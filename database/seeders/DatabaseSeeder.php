<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            KonfigurasiSeeder::class,
            KriteriaSeeder::class,
            PenggunaSeeder::class,
            NasabahHistorisSeeder::class,
        ]);
    }
}
