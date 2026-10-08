<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rankings', function (Blueprint $table) {
            $table->foreignId('rater_id')->constrained('players')->cascadeOnDelete();
            $table->foreignId('ratee_id')->constrained('players')->cascadeOnDelete();
            $table->unsignedInteger('position');
            $table->unique(['rater_id', 'ratee_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rankings');
    }
};
