<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class VietnameseMenuSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(PaprikaCatalogSeeder::class);
    }
}
