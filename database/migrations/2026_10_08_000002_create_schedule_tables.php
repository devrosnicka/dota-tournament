<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rounds', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('number')->unique();
            $table->string('format');
            $table->string('status')->default('planned');
            $table->timestamps();
        });

        Schema::create('round_sits', function (Blueprint $table) {
            $table->foreignId('round_id')->constrained()->cascadeOnDelete();
            $table->foreignId('player_id')->constrained()->cascadeOnDelete();
            $table->unique(['round_id', 'player_id']);
        });

        Schema::create('matches', function (Blueprint $table) {
            $table->id();
            $table->string('stage');
            $table->foreignId('round_id')->nullable()->constrained()->cascadeOnDelete();
            $table->unsignedInteger('lobby')->nullable();
            $table->unsignedInteger('map_number')->nullable();
            $table->string('winner')->nullable();
            $table->unsignedInteger('kills_a')->nullable();
            $table->unsignedInteger('kills_b')->nullable();
            $table->string('radiant_side')->nullable();
            $table->string('first_pick_side')->nullable();
            $table->foreignId('reported_by')->nullable()->constrained('players')->nullOnDelete();
            $table->timestamp('reported_at')->nullable();
            $table->timestamps();
        });

        Schema::create('match_players', function (Blueprint $table) {
            $table->foreignId('match_id')->constrained()->cascadeOnDelete();
            $table->foreignId('player_id')->constrained()->cascadeOnDelete();
            $table->string('side');
            $table->unique(['match_id', 'player_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('match_players');
        Schema::dropIfExists('matches');
        Schema::dropIfExists('round_sits');
        Schema::dropIfExists('rounds');
    }
};
