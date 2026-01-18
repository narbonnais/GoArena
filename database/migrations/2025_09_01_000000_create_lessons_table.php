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
        Schema::create('lessons', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title');
            $table->text('description');
            $table->enum('category', ['basics', 'capturing', 'territory', 'tactics', 'strategy']);
            $table->enum('difficulty', ['beginner', 'intermediate', 'advanced']);
            $table->string('duration', 20); // e.g., "10 min"
            $table->unsignedInteger('order')->default(0);
            $table->json('prerequisites')->nullable(); // array of lesson IDs
            $table->json('steps'); // JSON array of LessonStep objects
            $table->timestamps();

            $table->index(['category', 'order']);
            $table->index('difficulty');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lessons');
    }
};
