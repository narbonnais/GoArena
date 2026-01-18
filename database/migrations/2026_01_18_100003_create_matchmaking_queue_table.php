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
        Schema::create('matchmaking_queue', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->unsignedTinyInteger('board_size'); // 9, 13, or 19
            $table->foreignId('time_control_id')->constrained()->onDelete('cascade');
            $table->unsignedSmallInteger('rating');
            $table->unsignedSmallInteger('max_rating_diff')->default(200);
            $table->boolean('is_ranked')->default(true);
            $table->timestamp('joined_at');
            $table->timestamp('expires_at');

            // Unique constraint: one queue entry per user
            $table->unique('user_id');

            // Index for finding matches
            $table->index(['board_size', 'time_control_id', 'is_ranked', 'rating']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('matchmaking_queue');
    }
};
