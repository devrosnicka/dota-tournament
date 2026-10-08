<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('final_setup', function (Blueprint $table) {
            $table->id();
            $table->foreignId('captain1_id')->constrained('players');
            $table->foreignId('captain2_id')->constrained('players');
            $table->string('advantage_choice')->nullable();
            // Finalist id => group stage position, frozen when the draft starts.
            $table->json('finalists');
            $table->timestamps();
        });

        Schema::create('final_picks', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('pick_number')->unique();
            $table->foreignId('captain_id')->constrained('players');
            $table->foreignId('player_id')->unique()->constrained('players');
            $table->timestamps();
        });

        Schema::create('final_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('player_id')->unique()->constrained('players');
            $table->foreignId('captain_id')->constrained('players');
            $table->unsignedTinyInteger('position');
            $table->unique(['captain_id', 'position']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('final_roles');
        Schema::dropIfExists('final_picks');
        Schema::dropIfExists('final_setup');
    }
};
