<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Play, Trash2, Eye, Trophy, XCircle, Clock } from 'lucide-vue-next';

import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import type { GameHistoryItem, OngoingGameItem } from '@/types/go';

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface PaginatedGames {
    data: GameHistoryItem[];
    links: PaginationLink[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
}

const props = defineProps<{
    ongoingGames: OngoingGameItem[];
    games: PaginatedGames;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'History',
        href: '/go/history',
    },
];

function formatDate(dateString: string): string {
    const date = new Date(dateString);
    return date.toLocaleDateString(undefined, {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

function formatRelativeTime(dateString: string): string {
    const date = new Date(dateString);
    const now = new Date();
    const diffMs = now.getTime() - date.getTime();
    const diffMins = Math.floor(diffMs / 60000);
    const diffHours = Math.floor(diffMs / 3600000);
    const diffDays = Math.floor(diffMs / 86400000);

    if (diffMins < 1) return 'just now';
    if (diffMins < 60) return `${diffMins}m ago`;
    if (diffHours < 24) return `${diffHours}h ago`;
    if (diffDays < 7) return `${diffDays}d ago`;
    return formatDate(dateString);
}

function formatResult(game: GameHistoryItem): string {
    if (game.end_reason === 'resignation') {
        return 'by resignation';
    }
    if (game.end_reason === 'timeout') {
        return 'by timeout';
    }
    if (game.end_reason === 'abandonment') {
        return 'by abandonment';
    }
    if (game.winner === 'draw') {
        return 'Draw';
    }
    const margin = game.score_margin ?? 0;
    return margin > 0 ? `by ${margin} points` : 'by score';
}

function deleteGame(game: { can_delete: boolean; delete_url: string | null }) {
    if (!game.can_delete || !game.delete_url) return;
    if (confirm('Are you sure you want to delete this game from your history?')) {
        router.delete(game.delete_url);
    }
}

function navigateToPage(url: string | null) {
    // Validate URL is safe (relative path only)
    if (url && (url.startsWith('/') || url.startsWith('?'))) {
        router.visit(url, {
            preserveScroll: false,
            onSuccess: () => window.scrollTo({ top: 0, behavior: 'smooth' }),
        });
    }
}

/**
 * Decode HTML entities in pagination labels safely
 * Laravel paginator uses &laquo; and &raquo; for prev/next arrows
 */
function decodePaginationLabel(label: string): string {
    return label
        .replace(/&laquo;/g, '\u00AB')  // «
        .replace(/&raquo;/g, '\u00BB')  // »
        .replace(/&amp;/g, '&')
        .replace(/&lt;/g, '<')
        .replace(/&gt;/g, '>')
        .replace(/&quot;/g, '"');
}
</script>

<template>
    <Head title="Game History" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="history-page">
            <!-- Header -->
            <div class="history-header">
                <h1 class="history-title">Game History</h1>
                <Link href="/go" class="new-game-btn">
                    <Play :size="16" />
                    New Game
                </Link>
            </div>

            <!-- Ongoing Games Section -->
            <div v-if="ongoingGames.length > 0" class="ongoing-section">
                <h2 class="section-title">Ongoing Games</h2>
                <div class="games-list">
                    <div
                        v-for="game in ongoingGames"
                        :key="game.id"
                        class="game-card ongoing"
                    >
                        <!-- Status indicator -->
                        <div class="result-indicator ongoing">
                            <Clock :size="20" />
                        </div>

                        <!-- Game info -->
                        <div class="game-info">
                            <div class="game-main">
                                <span class="result-badge ongoing">{{ game.status_label }}</span>
                            </div>
                            <div class="game-meta">
                                <span class="opponent">vs {{ game.opponent }}</span>
                                <span class="separator">•</span>
                                <span class="board-size">{{ game.board_size }}×{{ game.board_size }}</span>
                                <span class="separator">•</span>
                                <span class="moves">{{ game.move_count }} moves</span>
                                <span class="separator">•</span>
                                <span class="date">{{ formatRelativeTime(game.updated_at) }}</span>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="game-actions">
                            <Link v-if="game.resume_url" :href="game.resume_url" class="action-btn resume-btn">
                                <Play :size="16" />
                                Resume
                            </Link>
                            <button v-if="game.can_delete" @click="deleteGame(game)" class="action-btn delete-btn">
                                <Trash2 :size="16" />
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Empty state -->
            <div v-if="games.data.length === 0 && ongoingGames.length === 0" class="empty-state">
                <div class="empty-stones">
                    <div class="empty-stone black"></div>
                    <div class="empty-stone white"></div>
                </div>
                <h2 class="empty-title">No games yet</h2>
                <p class="empty-text">
                    Play against the AI or other players to see your history here.
                </p>
                <Link href="/go" class="start-btn">
                    <Play :size="18" />
                    Start Playing
                </Link>
            </div>

            <!-- Finished Games Section -->
            <div v-if="games.data.length > 0" class="finished-section">
                <h2 v-if="ongoingGames.length > 0" class="section-title">Game History</h2>
            </div>

            <!-- Games list -->
            <div v-if="games.data.length > 0" class="games-list">
                <div
                    v-for="game in games.data"
                    :key="game.id"
                    class="game-card"
                >
                    <!-- Result indicator -->
                    <div class="result-indicator" :class="{ win: game.user_won, loss: !game.user_won && game.winner !== 'draw' }">
                        <Trophy v-if="game.user_won" :size="20" />
                        <XCircle v-else-if="game.winner !== 'draw'" :size="20" />
                        <span v-else class="draw-icon">=</span>
                    </div>

                    <!-- Game info -->
                    <div class="game-info">
                        <div class="game-main">
                            <span class="result-badge" :class="{ win: game.user_won, loss: !game.user_won && game.winner !== 'draw', draw: game.winner === 'draw' }">
                                {{ game.user_won ? 'Victory' : game.winner === 'draw' ? 'Draw' : 'Defeat' }}
                            </span>
                            <span class="result-detail">{{ formatResult(game) }}</span>
                        </div>
                        <div class="game-meta">
                            <span class="opponent">vs {{ game.opponent }}</span>
                            <span class="separator">•</span>
                            <span class="board-size">{{ game.board_size }}×{{ game.board_size }}</span>
                            <span class="separator">•</span>
                            <span class="moves">{{ game.move_count }} moves</span>
                            <span class="separator">•</span>
                            <span class="date">{{ formatDate(game.created_at) }}</span>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="game-actions">
                        <Link :href="game.detail_url" class="action-btn replay-btn">
                            <Eye :size="16" />
                            View
                        </Link>
                        <button v-if="game.can_delete" @click="deleteGame(game)" class="action-btn delete-btn">
                            <Trash2 :size="16" />
                        </button>
                    </div>
                </div>
            </div>

            <!-- Pagination -->
            <div v-if="games.last_page > 1" class="pagination">
                <button
                    v-for="link in games.links"
                    :key="link.label"
                    @click="navigateToPage(link.url)"
                    :disabled="!link.url"
                    class="page-btn"
                    :class="{ active: link.active, disabled: !link.url }"
                >{{ decodePaginationLabel(link.label) }}</button>
            </div>

            <!-- Stats footer -->
            <div v-if="games.total > 0" class="stats-footer">
                Showing {{ games.data.length }} of {{ games.total }} games
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.history-page {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
    padding: 1.5rem;
    max-width: 800px;
    margin: 0 auto;
}

/* Header */
.history-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1rem;
}

.history-title {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--foreground);
    margin: 0;
}

.new-game-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    font-size: 0.875rem;
    font-weight: 600;
    color: white;
    background-color: var(--go-green);
    border-radius: 0.5rem;
    text-decoration: none;
    transition: all 0.15s ease;
}

.new-game-btn:hover {
    background-color: var(--go-green-hover);
}

/* Empty state */
.empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 4rem 2rem;
    background-color: var(--card);
    border: 2px dashed var(--border);
    border-radius: 0.75rem;
    text-align: center;
}

.empty-stones {
    display: flex;
    gap: 0.5rem;
    margin-bottom: 1rem;
}

.empty-stone {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
}

.empty-stone.black {
    background: radial-gradient(circle at 30% 30%, #4a4a4a, #1a1a1a);
    border: 1px solid #000;
}

.empty-stone.white {
    background: radial-gradient(circle at 30% 30%, #fff, #e0e0e0);
    border: 1px solid #ccc;
}

.empty-title {
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--foreground);
    margin: 0 0 0.5rem;
}

.empty-text {
    font-size: 0.875rem;
    color: var(--muted-foreground);
    margin: 0 0 1.5rem;
}

.start-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.5rem;
    font-size: 1rem;
    font-weight: 600;
    color: white;
    background-color: var(--go-green);
    border-radius: 0.5rem;
    text-decoration: none;
    transition: all 0.15s ease;
}

.start-btn:hover {
    background-color: var(--go-green-hover);
}

/* Games list */
.games-list {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.game-card {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    background-color: var(--card);
    border: 1px solid var(--border);
    border-radius: 0.75rem;
    transition: all 0.15s ease;
}

.game-card:hover {
    background-color: var(--accent);
}

/* Result indicator */
.result-indicator {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background-color: var(--muted);
    color: var(--muted-foreground);
    flex-shrink: 0;
}

.result-indicator.win {
    background-color: var(--go-green-muted);
    color: var(--go-green);
}

.result-indicator.loss {
    background-color: hsl(0 84% 60% / 0.15);
    color: hsl(0 84% 60%);
}

.draw-icon {
    font-size: 1.25rem;
    font-weight: 700;
}

/* Game info */
.game-info {
    flex: 1;
    min-width: 0;
}

.game-main {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 0.25rem;
}

.result-badge {
    font-size: 0.875rem;
    font-weight: 700;
    color: var(--foreground);
}

.result-badge.win {
    color: var(--go-green);
}

.result-badge.loss {
    color: hsl(0 84% 60%);
}

.result-badge.draw {
    color: var(--muted-foreground);
}

.result-detail {
    font-size: 0.875rem;
    color: var(--muted-foreground);
}

.game-meta {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.75rem;
    color: var(--muted-foreground);
    flex-wrap: wrap;
}

.separator {
    opacity: 0.5;
}

.board-size {
    font-weight: 500;
}

/* Actions */
.game-actions {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    flex-shrink: 0;
}

.action-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.375rem;
    padding: 0.5rem 0.75rem;
    font-size: 0.8125rem;
    font-weight: 600;
    border-radius: 0.5rem;
    cursor: pointer;
    transition: all 0.15s ease;
    text-decoration: none;
}

.replay-btn {
    background-color: var(--secondary);
    border: 1px solid var(--border);
    color: var(--foreground);
}

.replay-btn:hover {
    background-color: var(--accent);
}

.delete-btn {
    background-color: transparent;
    border: 1px solid var(--border);
    color: var(--muted-foreground);
}

.delete-btn:hover {
    background-color: hsl(0 84% 60% / 0.15);
    border-color: hsl(0 84% 60% / 0.3);
    color: hsl(0 84% 60%);
}

/* Pagination */
.pagination {
    display: flex;
    justify-content: center;
    gap: 0.25rem;
    flex-wrap: wrap;
}

.page-btn {
    padding: 0.5rem 0.75rem;
    font-size: 0.875rem;
    font-weight: 500;
    border-radius: 0.375rem;
    background-color: var(--card);
    border: 1px solid var(--border);
    color: var(--foreground);
    cursor: pointer;
    transition: all 0.15s ease;
}

.page-btn:hover:not(.disabled) {
    background-color: var(--accent);
}

.page-btn.active {
    background-color: var(--go-green);
    border-color: var(--go-green);
    color: white;
}

.page-btn.disabled {
    opacity: 0.4;
    cursor: not-allowed;
}

/* Stats footer */
.stats-footer {
    text-align: center;
    font-size: 0.875rem;
    color: var(--muted-foreground);
}

/* Section titles */
.section-title {
    font-size: 1rem;
    font-weight: 600;
    color: var(--muted-foreground);
    margin: 0 0 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

/* Ongoing games section */
.ongoing-section {
    margin-bottom: 1.5rem;
}

/* Ongoing game card styles */
.game-card.ongoing {
    border-color: var(--go-green);
    border-width: 2px;
}

.result-indicator.ongoing {
    background-color: var(--go-green-muted);
    color: var(--go-green);
}

.result-badge.ongoing {
    color: var(--go-green);
}

/* Resume button */
.resume-btn {
    background-color: var(--go-green);
    border-color: var(--go-green);
    color: white;
}

.resume-btn:hover {
    background-color: var(--go-green-hover);
}

/* Finished section */
.finished-section {
    margin-bottom: 0.5rem;
}
</style>
