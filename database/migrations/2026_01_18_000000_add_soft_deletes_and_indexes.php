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
        // Add soft deletes to go_games
        Schema::table('go_games', function (Blueprint $table) {
            $table->softDeletes();
            $table->index('ai_level');
            $table->index(['user_id', 'is_finished']);
        });

        // Add soft deletes to analysis_studies
        Schema::table('analysis_studies', function (Blueprint $table) {
            $table->softDeletes();
            $table->index(['is_public', 'created_at']);
        });

        // Note: The index ['user_id', 'completed'] on lesson_progress already exists
        // in the create_lesson_progress_table migration, so we don't add it here
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('go_games', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropIndex(['ai_level']);
            $table->dropIndex(['user_id', 'is_finished']);
        });

        Schema::table('analysis_studies', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropIndex(['is_public', 'created_at']);
        });

        // Note: The index ['user_id', 'completed'] on lesson_progress is managed
        // by the create_lesson_progress_table migration, so we don't drop it here
    }
};
