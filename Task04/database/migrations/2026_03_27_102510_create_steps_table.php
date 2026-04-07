<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('steps', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('game_id')->constrained()->cascadeOnDelete();
            $table->integer('user_answer');
            $table->boolean('is_correct');
            $table->dateTime('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('steps');
    }
};
