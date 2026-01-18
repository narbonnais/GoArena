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
        Schema::table('multiplayer_games', function (Blueprint $table) {
            $table->string('score_phase')->default('none')->after('status');
            $table->json('dead_stones')->default('[]')->after('captures');
            $table->json('score_acceptance')->default('{"black": false, "white": false}')->after('dead_stones');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('multiplayer_games', function (Blueprint $table) {
            $table->dropColumn(['score_phase', 'dead_stones', 'score_acceptance']);
        });
    }
};
