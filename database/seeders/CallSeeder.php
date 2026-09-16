<?php

namespace Database\Seeders;

use App\Models\Call;
use Illuminate\Database\Seeder;

class CallSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Call::factory()->count(5)->create();
    }
}
