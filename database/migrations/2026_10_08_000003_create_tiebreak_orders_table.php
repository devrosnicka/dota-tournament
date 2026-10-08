<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tiebreak_orders', function (Blueprint $table) {
            $table->id();
            $table->string('context')->unique();
            $table->json('ordered_player_ids');
            $table->string('note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tiebreak_orders');
    }
};
