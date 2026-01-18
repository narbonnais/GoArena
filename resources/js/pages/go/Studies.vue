<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { FlaskConical, Trash2, Eye, Plus, Grid3X3 } from 'lucide-vue-next';

import { useAnalysisStudy } from '@/composables/go/useAnalysisStudy';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import type { AnalysisStudyListItem } from '@/types/analysis';

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface PaginatedStudies {
    data: AnalysisStudyListItem[];
    links: PaginationLink[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
}

const props = defineProps<{
    studies: PaginatedStudies;
}>();

const studyApi = useAnalysisStudy();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'My Studies',
        href: '/go/studies',
    },
];

function formatDate(dateString: string): string {
    const date = new Date(dateString);
    return date.toLocaleDateString(undefined, {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
}

function formatRelativeDate(dateString: string): string {
    const date = new Date(dateString);
    const now = new Date();
    const diffMs = now.getTime() - date.getTime();
    const diffDays = Math.floor(diffMs / (1000 * 60 * 60 * 24));

    if (diffDays === 0) {
        return 'Today';
    } else if (diffDays === 1) {
        return 'Yesterday';
    } else if (diffDays < 7) {
        return `${diffDays} days ago`;
    } else {
        return formatDate(dateString);
    }
}

async function deleteStudy(studyId: number) {
    if (confirm('Are you sure you want to delete this study? This cannot be undone.')) {
        const success = await studyApi.deleteStudy(studyId);
        if (success) {
            router.reload();
        }
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
    <Head title="My Studies" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="studies-page">
            <!-- Header -->
            <div class="studies-header">
                <h1 class="studies-title">My Studies</h1>
                <Link href="/go/analyze" class="new-study-btn">
                    <Plus :size="16" />
                    New Analysis
                </Link>
            </div>

            <!-- Empty state -->
            <div v-if="studies.data.length === 0" class="empty-state">
                <div class="empty-icon">
                    <FlaskConical :size="48" />
                </div>
                <h2 class="empty-title">No studies yet</h2>
                <p class="empty-text">
                    Create your first analysis study to explore game positions, variations, and add annotations.
                </p>
                <Link href="/go/analyze" class="start-btn">
                    <Plus :size="18" />
                    Start Analyzing
                </Link>
            </div>

            <!-- Studies grid -->
            <div v-else class="studies-grid">
                <div
                    v-for="study in studies.data"
                    :key="study.id"
                    class="study-card"
                >
                    <!-- Board size indicator -->
                    <div class="board-indicator">
                        <Grid3X3 :size="16" />
                        <span>{{ study.board_size }}×{{ study.board_size }}</span>
                    </div>

                    <!-- Study info -->
                    <div class="study-info">
                        <h3 class="study-title">{{ study.title }}</h3>
                        <p v-if="study.description" class="study-description">
                            {{ study.description }}
                        </p>
                        <div class="study-meta">
                            <span class="moves">{{ study.move_count }} moves</span>
                            <span class="separator">•</span>
                            <span class="date">{{ formatRelativeDate(study.updated_at) }}</span>
                            <span v-if="study.is_public" class="public-badge">
                                Public
                            </span>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="study-actions">
                        <Link :href="`/go/studies/${study.id}`" class="action-btn open-btn">
                            <Eye :size="16" />
                            Open
                        </Link>
                        <button @click="deleteStudy(study.id)" class="action-btn delete-btn">
                            <Trash2 :size="16" />
                        </button>
                    </div>
                </div>
            </div>

            <!-- Pagination -->
            <div v-if="studies.last_page > 1" class="pagination">
                <button
                    v-for="link in studies.links"
                    :key="link.label"
                    @click="navigateToPage(link.url)"
                    :disabled="!link.url"
                    class="page-btn"
                    :class="{ active: link.active, disabled: !link.url }"
                >{{ decodePaginationLabel(link.label) }}</button>
            </div>

            <!-- Stats footer -->
            <div v-if="studies.total > 0" class="stats-footer">
                Showing {{ studies.data.length }} of {{ studies.total }} studies
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.studies-page {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
    padding: 1.5rem;
    max-width: 900px;
    margin: 0 auto;
}

/* Header */
.studies-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1rem;
}

.studies-title {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--foreground);
    margin: 0;
}

.new-study-btn {
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

.new-study-btn:hover {
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

.empty-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 80px;
    height: 80px;
    background-color: var(--muted);
    border-radius: 50%;
    color: var(--muted-foreground);
    margin-bottom: 1rem;
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
    max-width: 400px;
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

/* Studies grid */
.studies-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 1rem;
}

.study-card {
    display: flex;
    flex-direction: column;
    padding: 1rem;
    background-color: var(--card);
    border: 1px solid var(--border);
    border-radius: 0.75rem;
    transition: all 0.15s ease;
}

.study-card:hover {
    background-color: var(--accent);
    border-color: var(--foreground);
}

/* Board indicator */
.board-indicator {
    display: inline-flex;
    align-items: center;
    gap: 0.375rem;
    padding: 0.25rem 0.5rem;
    font-size: 0.75rem;
    font-weight: 600;
    color: var(--muted-foreground);
    background-color: var(--background);
    border-radius: 0.25rem;
    align-self: flex-start;
    margin-bottom: 0.75rem;
}

/* Study info */
.study-info {
    flex: 1;
    min-width: 0;
}

.study-title {
    font-size: 1rem;
    font-weight: 600;
    color: var(--foreground);
    margin: 0 0 0.375rem;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.study-description {
    font-size: 0.8125rem;
    color: var(--muted-foreground);
    margin: 0 0 0.5rem;
    overflow: hidden;
    text-overflow: ellipsis;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
}

.study-meta {
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

.public-badge {
    padding: 0.125rem 0.375rem;
    font-size: 0.6875rem;
    font-weight: 600;
    color: #3b82f6;
    background-color: rgba(59, 130, 246, 0.1);
    border-radius: 0.25rem;
}

/* Actions */
.study-actions {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-top: 1rem;
    padding-top: 0.75rem;
    border-top: 1px solid var(--border);
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

.open-btn {
    flex: 1;
    justify-content: center;
    background-color: var(--secondary);
    border: 1px solid var(--border);
    color: var(--foreground);
}

.open-btn:hover {
    background-color: var(--go-green);
    border-color: var(--go-green);
    color: white;
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
</style>
