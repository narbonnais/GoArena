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
        Schema::create('go_games', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // Game config
            $table->unsignedTinyInteger('board_size'); // 9, 13, or 19
            $table->string('ai_level', 10); // e.g., '30k', '1d', '5d'
            $table->decimal('komi', 4, 1)->default(6.5);

            // Result
            $table->enum('winner', ['black', 'white', 'draw']);
            $table->enum('end_reason', ['score', 'resignation']);
            $table->decimal('score_margin', 5, 1)->nullable(); // null for resignation
            $table->unsignedInteger('move_count');

            // Scores
            $table->decimal('black_score', 5, 1);
            $table->decimal('white_score', 5, 1);
            $table->unsignedInteger('black_captures');
            $table->unsignedInteger('white_captures');

            // Move history (JSON array of moves)
            $table->json('move_history');

            // Duration
            $table->unsignedInteger('duration_seconds');

            $table->timestamps();

            // Index for user's game history queries
            $table->index(['user_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('go_games');
    }
};
