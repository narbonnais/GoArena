<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('game_connections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('multiplayer_game_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamp('connected_at');
            $table->timestamp('last_ping_at')->nullable();
            $table->timestamp('disconnected_at')->nullable();

            // Unique constraint: one connection record per user per game
            $table->unique(['multiplayer_game_id', 'user_id']);

            // Index for checking active connections
            $table->index(['multiplayer_game_id', 'disconnected_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('game_connections');
    }
};
