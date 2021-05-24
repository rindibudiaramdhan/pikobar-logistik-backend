<?php

use Illuminate\Database\Seeder;

class VillagesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $filepath = base_path('database/seeds/data/villages.sql');
        DB::unprepared(file_get_contents($filepath));
    }
}
