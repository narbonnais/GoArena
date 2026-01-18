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
        Schema::create('analysis_studies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // Study metadata
            $table->string('title', 255);
            $table->text('description')->nullable();

            // Board config
            $table->unsignedTinyInteger('board_size'); // 9, 13, or 19
            $table->decimal('komi', 4, 1)->default(6.5);

            // Move tree (JSON structure with nodes)
            $table->json('move_tree');

            // Optional link to source game
            $table->foreignId('source_game_id')
                ->nullable()
                ->constrained('go_games')
                ->onDelete('set null');

            // Visibility
            $table->boolean('is_public')->default(false);

            $table->timestamps();

            // Index for user's studies queries
            $table->index(['user_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('analysis_studies');
    }
};
