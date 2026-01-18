<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('go_games', function (Blueprint $table) {
            $table->string('game_type', 10)->default('bot')->after('id');
            $table->unsignedBigInteger('legacy_multiplayer_id')->nullable()->after('game_type');
            $table->foreignId('black_player_id')->nullable()->constrained('users')->after('user_id');
            $table->foreignId('white_player_id')->nullable()->constrained('users')->after('black_player_id');
            $table->foreignId('time_control_id')->nullable()->constrained()->after('board_size');
            $table->boolean('is_ranked')->default(false)->after('komi');
            $table->string('status', 20)->default('finished')->after('is_ranked');
            $table->string('score_phase', 20)->default('none')->after('status');
            $table->string('current_player', 10)->nullable()->after('score_phase');
            $table->unsignedBigInteger('black_time_remaining_ms')->nullable()->after('current_player');
            $table->unsignedBigInteger('white_time_remaining_ms')->nullable()->after('black_time_remaining_ms');
            $table->timestamp('last_move_at')->nullable()->after('white_time_remaining_ms');
            $table->json('dead_stones')->default('[]')->after('white_captures');
            $table->json('score_acceptance')->default('{"black": false, "white": false}')->after('dead_stones');
            $table->unsignedSmallInteger('black_rating_before')->nullable()->after('score_acceptance');
            $table->unsignedSmallInteger('black_rating_after')->nullable()->after('black_rating_before');
            $table->unsignedSmallInteger('white_rating_before')->nullable()->after('black_rating_after');
            $table->unsignedSmallInteger('white_rating_after')->nullable()->after('white_rating_before');

            $table->index(['game_type', 'status', 'created_at']);
            $table->index(['black_player_id', 'created_at']);
            $table->index(['white_player_id', 'created_at']);
        });

        DB::table('go_games')->update([
            'game_type' => 'bot',
            'status' => DB::raw("CASE WHEN is_finished = 1 THEN 'finished' ELSE 'active' END"),
            'black_player_id' => DB::raw('user_id'),
        ]);

        if (Schema::hasTable('multiplayer_games')) {
            DB::table('multiplayer_games')
                ->orderBy('id')
                ->chunk(100, function ($games) {
                    $rows = [];

                    foreach ($games as $game) {
                        $captures = $this->decodeJson($game->captures, ['black' => 0, 'white' => 0]);
                        $scores = $this->decodeJson($game->scores, []);
                        $deadStones = $this->normalizeJson($game->dead_stones, '[]');
                        $scoreAcceptance = $this->normalizeJson(
                            $game->score_acceptance,
                            '{"black": false, "white": false}'
                        );
                        $moveHistory = $this->normalizeJson($game->move_history, '[]');

                        $blackScore = isset($scores['black']) ? (float) $scores['black'] : 0.0;
                        $whiteScore = isset($scores['white']) ? (float) $scores['white'] : 0.0;
                        $scoreMargin = null;

                        if ($game->end_reason === 'score' && $scores) {
                            $scoreMargin = round(abs($blackScore - $whiteScore), 1);
                        }

                        $durationSeconds = $this->calculateDurationSeconds(
                            $game->created_at,
                            $game->last_move_at ?? $game->updated_at
                        );

                        $rows[] = [
                            'game_type' => 'human',
                            'legacy_multiplayer_id' => $game->id,
                            'user_id' => $game->black_player_id,
                            'black_player_id' => $game->black_player_id,
                            'white_player_id' => $game->white_player_id,
                            'board_size' => $game->board_size,
                            'time_control_id' => $game->time_control_id,
                            'ai_level' => 'human',
                            'komi' => $game->komi,
                            'is_ranked' => (bool) $game->is_ranked,
                            'status' => $game->status,
                            'score_phase' => $game->score_phase,
                            'current_player' => $game->current_player,
                            'black_time_remaining_ms' => $game->black_time_remaining_ms,
                            'white_time_remaining_ms' => $game->white_time_remaining_ms,
                            'last_move_at' => $game->last_move_at,
                            'winner' => $game->winner,
                            'end_reason' => $game->end_reason,
                            'score_margin' => $scoreMargin,
                            'move_count' => $game->move_count,
                            'black_score' => $blackScore,
                            'white_score' => $whiteScore,
                            'black_captures' => (int) ($captures['black'] ?? 0),
                            'white_captures' => (int) ($captures['white'] ?? 0),
                            'dead_stones' => $deadStones,
                            'score_acceptance' => $scoreAcceptance,
                            'move_history' => $moveHistory,
                            'duration_seconds' => $durationSeconds,
                            'is_finished' => in_array($game->status, ['finished', 'abandoned'], true),
                            'black_rating_before' => $game->black_rating_before,
                            'black_rating_after' => $game->black_rating_after,
                            'white_rating_before' => $game->white_rating_before,
                            'white_rating_after' => $game->white_rating_after,
                            'created_at' => $game->created_at,
                            'updated_at' => $game->updated_at,
                            'deleted_at' => $game->deleted_at,
                        ];
                    }

                    if ($rows) {
                        DB::table('go_games')->insert($rows);
                    }
                });
        }

        if (Schema::hasTable('game_connections')) {
            Schema::table('game_connections', function (Blueprint $table) {
                $table->foreignId('game_id')->nullable()->after('id');
            });

            $mapping = DB::table('go_games')
                ->whereNotNull('legacy_multiplayer_id')
                ->pluck('id', 'legacy_multiplayer_id');

            foreach ($mapping as $legacyId => $gameId) {
                DB::table('game_connections')
                    ->where('multiplayer_game_id', $legacyId)
                    ->update(['game_id' => $gameId]);
            }

            Schema::table('game_connections', function (Blueprint $table) {
                $table->dropForeign(['multiplayer_game_id']);
                $table->dropUnique(['multiplayer_game_id', 'user_id']);
                $table->dropIndex(['multiplayer_game_id', 'disconnected_at']);
                $table->dropColumn('multiplayer_game_id');
            });

            Schema::table('game_connections', function (Blueprint $table) {
                $table->foreign('game_id')->references('id')->on('go_games')->onDelete('cascade');
                $table->unique(['game_id', 'user_id']);
                $table->index(['game_id', 'disconnected_at']);
            });
        }

        Schema::dropIfExists('multiplayer_games');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('multiplayer_games', function (Blueprint $table) {
            $table->id();
            $table->foreignId('black_player_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('white_player_id')->constrained('users')->onDelete('cascade');
            $table->unsignedTinyInteger('board_size');
            $table->foreignId('time_control_id')->constrained()->onDelete('cascade');
            $table->decimal('komi', 4, 1)->default(6.5);
            $table->boolean('is_ranked')->default(true);
            $table->enum('status', ['pending', 'active', 'finished', 'abandoned'])->default('pending');
            $table->enum('current_player', ['black', 'white'])->default('black');
            $table->unsignedBigInteger('black_time_remaining_ms');
            $table->unsignedBigInteger('white_time_remaining_ms');
            $table->timestamp('last_move_at')->nullable();
            $table->enum('winner', ['black', 'white', 'draw'])->nullable();
            $table->enum('end_reason', ['score', 'resignation', 'timeout', 'abandonment'])->nullable();
            $table->json('move_history')->default('[]');
            $table->unsignedInteger('move_count')->default(0);
            $table->json('captures')->default('{"black": 0, "white": 0}');
            $table->json('scores')->nullable();
            $table->unsignedSmallInteger('black_rating_before')->nullable();
            $table->unsignedSmallInteger('black_rating_after')->nullable();
            $table->unsignedSmallInteger('white_rating_before')->nullable();
            $table->unsignedSmallInteger('white_rating_after')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['status', 'created_at']);
            $table->index(['black_player_id', 'created_at']);
            $table->index(['white_player_id', 'created_at']);
        });

        DB::table('go_games')
            ->where('game_type', 'human')
            ->orderBy('id')
            ->chunk(100, function ($games) {
                $rows = [];

                foreach ($games as $game) {
                    $captures = [
                        'black' => (int) $game->black_captures,
                        'white' => (int) $game->white_captures,
                    ];

                    $scores = null;
                    if ($game->black_score !== null || $game->white_score !== null) {
                        $scores = [
                            'black' => (float) ($game->black_score ?? 0),
                            'white' => (float) ($game->white_score ?? 0),
                        ];
                    }

                    $rows[] = [
                        'id' => $game->legacy_multiplayer_id,
                        'black_player_id' => $game->black_player_id ?? $game->user_id,
                        'white_player_id' => $game->white_player_id,
                        'board_size' => $game->board_size,
                        'time_control_id' => $game->time_control_id,
                        'komi' => $game->komi,
                        'is_ranked' => (bool) $game->is_ranked,
                        'status' => $game->status,
                        'current_player' => $game->current_player ?? 'black',
                        'black_time_remaining_ms' => $game->black_time_remaining_ms ?? 0,
                        'white_time_remaining_ms' => $game->white_time_remaining_ms ?? 0,
                        'last_move_at' => $game->last_move_at,
                        'winner' => $game->winner,
                        'end_reason' => $game->end_reason,
                        'move_history' => $this->normalizeJson($game->move_history, '[]'),
                        'move_count' => $game->move_count ?? 0,
                        'captures' => json_encode($captures),
                        'scores' => $scores ? json_encode($scores) : null,
                        'black_rating_before' => $game->black_rating_before,
                        'black_rating_after' => $game->black_rating_after,
                        'white_rating_before' => $game->white_rating_before,
                        'white_rating_after' => $game->white_rating_after,
                        'created_at' => $game->created_at,
                        'updated_at' => $game->updated_at,
                        'deleted_at' => $game->deleted_at,
                    ];
                }

                if ($rows) {
                    DB::table('multiplayer_games')->insert($rows);
                }
            });

        if (Schema::hasTable('game_connections')) {
            Schema::table('game_connections', function (Blueprint $table) {
                $table->foreignId('multiplayer_game_id')->nullable()->after('id');
            });

            $mapping = DB::table('go_games')
                ->whereNotNull('legacy_multiplayer_id')
                ->pluck('legacy_multiplayer_id', 'id');

            foreach ($mapping as $gameId => $legacyId) {
                DB::table('game_connections')
                    ->where('game_id', $gameId)
                    ->update(['multiplayer_game_id' => $legacyId]);
            }

            Schema::table('game_connections', function (Blueprint $table) {
                $table->dropForeign(['game_id']);
                $table->dropUnique(['game_id', 'user_id']);
                $table->dropIndex(['game_id', 'disconnected_at']);
                $table->dropColumn('game_id');
            });

            Schema::table('game_connections', function (Blueprint $table) {
                $table->foreign('multiplayer_game_id')->references('id')->on('multiplayer_games')->onDelete('cascade');
                $table->unique(['multiplayer_game_id', 'user_id']);
                $table->index(['multiplayer_game_id', 'disconnected_at']);
            });
        }

        Schema::table('go_games', function (Blueprint $table) {
            $table->dropIndex(['game_type', 'status', 'created_at']);
            $table->dropIndex(['black_player_id', 'created_at']);
            $table->dropIndex(['white_player_id', 'created_at']);
            $table->dropForeign(['black_player_id']);
            $table->dropForeign(['white_player_id']);
            $table->dropForeign(['time_control_id']);
            $table->dropColumn([
                'game_type',
                'legacy_multiplayer_id',
                'black_player_id',
                'white_player_id',
                'time_control_id',
                'is_ranked',
                'status',
                'score_phase',
                'current_player',
                'black_time_remaining_ms',
                'white_time_remaining_ms',
                'last_move_at',
                'dead_stones',
                'score_acceptance',
                'black_rating_before',
                'black_rating_after',
                'white_rating_before',
                'white_rating_after',
            ]);
        });
    }

    /**
     * @param  array<string, mixed>  $default
     * @return array<string, mixed>
     */
    private function decodeJson(mixed $value, array $default): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return $default;
    }

    private function normalizeJson(mixed $value, string $default): string
    {
        if (is_string($value)) {
            return $value;
        }

        if (is_array($value)) {
            return json_encode($value);
        }

        return $default;
    }

    private function calculateDurationSeconds(?string $start, ?string $end): int
    {
        if (! $start || ! $end) {
            return 0;
        }

        $startTime = Carbon::parse($start);
        $endTime = Carbon::parse($end);

        return max(0, $startTime->diffInSeconds($endTime));
    }
};
