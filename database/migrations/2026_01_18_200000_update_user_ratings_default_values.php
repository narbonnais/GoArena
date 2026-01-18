<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Updates default rating values from 1500 to 700 (18 kyu) per EGF standard.
     * This aligns with the new rating system where:
     * - 1 dan = 2100 rating (EGF standard)
     * - New players start at 700 (18 kyu)
     */
    public function up(): void
    {
        Schema::table('user_ratings', function (Blueprint $table) {
            $table->unsignedSmallInteger('rating')->default(700)->change();
            $table->unsignedSmallInteger('peak_rating')->default(700)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_ratings', function (Blueprint $table) {
            $table->unsignedSmallInteger('rating')->default(1500)->change();
            $table->unsignedSmallInteger('peak_rating')->default(1500)->change();
        });
    }
};
