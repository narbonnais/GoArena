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
        Schema::table('analysis_studies', function (Blueprint $table) {
            // Add index for the index() query which orders by updated_at
            $table->index(['user_id', 'updated_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('analysis_studies', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'updated_at']);
        });
    }
};
