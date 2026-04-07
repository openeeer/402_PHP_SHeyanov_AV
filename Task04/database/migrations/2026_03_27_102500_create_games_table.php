<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('games', function (Blueprint $table): void {
            $table->id();
            $table->string('player_name');
            $table->string('expression');
            $table->integer('correct_answer');
            $table->dateTime('started_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('games');
    }
};
