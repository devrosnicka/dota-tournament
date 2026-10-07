<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('players', function (Blueprint $table) {
            $table->id();
            $table->string('nick')->collation('NOCASE')->unique();
            $table->string('login_code')->nullable();
            $table->timestamp('login_code_expires_at')->nullable();
            $table->string('status')->default('active');
            $table->unsignedInteger('withdrawn_from_round')->nullable();
            $table->unsignedInteger('seed_rank')->nullable();
            $table->double('seed_score')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('players');
    }
};
