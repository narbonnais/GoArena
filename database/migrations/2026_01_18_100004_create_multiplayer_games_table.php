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
        Schema::create('multiplayer_games', function (Blueprint $table) {
            $table->id();
            $table->foreignId('black_player_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('white_player_id')->constrained('users')->onDelete('cascade');
            $table->unsignedTinyInteger('board_size'); // 9, 13, or 19
            $table->foreignId('time_control_id')->constrained()->onDelete('cascade');
            $table->decimal('komi', 4, 1)->default(6.5);

            // Game status
            $table->boolean('is_ranked')->default(true);
            $table->enum('status', ['pending', 'active', 'finished', 'abandoned'])->default('pending');
            $table->enum('current_player', ['black', 'white'])->default('black');

            // Time tracking (in milliseconds for precision)
            $table->unsignedBigInteger('black_time_remaining_ms');
            $table->unsignedBigInteger('white_time_remaining_ms');
            $table->timestamp('last_move_at')->nullable();

            // Result
            $table->enum('winner', ['black', 'white', 'draw'])->nullable();
            $table->enum('end_reason', ['score', 'resignation', 'timeout', 'abandonment'])->nullable();

            // Game state (JSON)
            $table->json('move_history')->default('[]');
            $table->unsignedInteger('move_count')->default(0);
            $table->json('captures')->default('{"black": 0, "white": 0}');
            $table->json('scores')->nullable(); // Final scores after counting

            // Rating changes (null if unranked)
            $table->unsignedSmallInteger('black_rating_before')->nullable();
            $table->unsignedSmallInteger('black_rating_after')->nullable();
            $table->unsignedSmallInteger('white_rating_before')->nullable();
            $table->unsignedSmallInteger('white_rating_after')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['status', 'created_at']); // For live games list
            $table->index(['black_player_id', 'created_at']);
            $table->index(['white_player_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('multiplayer_games');
    }
};
