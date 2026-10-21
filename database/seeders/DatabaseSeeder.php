<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            DataRujukanSeeder::class,
            PenggunaSeeder::class,
            PegawaiHrSeeder::class,
            RujukanPeraturanSeeder::class,
            PeraturanMbsp1995Seeder::class,
        ]);

        // Data contoh hanya untuk persekitaran bukan produksi.
        if (! app()->environment('production')) {
            $this->call(ContohAduanSeeder::class);
        }
    }
}
