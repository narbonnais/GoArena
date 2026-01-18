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
        Schema::table('go_games', function (Blueprint $table) {
            $table->boolean('is_finished')->default(true)->after('duration_seconds');
        });

        // Make winner and end_reason nullable for in-progress games
        Schema::table('go_games', function (Blueprint $table) {
            $table->string('winner')->nullable()->change();
            $table->string('end_reason')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('go_games', function (Blueprint $table) {
            $table->dropColumn('is_finished');
        });

        // Revert winner and end_reason to non-nullable enums
        Schema::table('go_games', function (Blueprint $table) {
            $table->enum('winner', ['black', 'white', 'draw'])->change();
            $table->enum('end_reason', ['score', 'resignation'])->change();
        });
    }
};
