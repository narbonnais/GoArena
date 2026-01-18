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
        Schema::table('users', function (Blueprint $table) {
            $table->string('display_name', 50)->nullable()->after('name');
            $table->string('avatar_url')->nullable()->after('display_name');
            $table->char('country_code', 2)->nullable()->after('avatar_url');
            $table->boolean('is_online')->default(false)->after('country_code');
            $table->timestamp('last_seen_at')->nullable()->after('is_online');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'display_name',
                'avatar_url',
                'country_code',
                'is_online',
                'last_seen_at',
            ]);
        });
    }
};
