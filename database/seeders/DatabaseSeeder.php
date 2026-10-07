<?php

namespace Database\Seeders;

use App\Models\Player;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed a local database with a typical number of players.
     */
    public function run(): void
    {
        Player::factory()->count(12)->create();
    }
}
