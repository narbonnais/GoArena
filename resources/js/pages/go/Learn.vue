<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { computed } from 'vue';

import DailyPuzzle from '@/components/go/learn/DailyPuzzle.vue';
import LessonCard from '@/components/go/learn/LessonCard.vue';
import ProgressTracker from '@/components/go/learn/ProgressTracker.vue';
import QuickTips from '@/components/go/learn/QuickTips.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import type { LessonListItem } from '@/types/learn';

interface Props {
    lessons: LessonListItem[];
    completedCount: number;
    error?: string;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Learn',
        href: '/go/learn',
    },
];

// Transform lessons to match LessonCard expectations
const formattedLessons = computed(() => {
    return props.lessons.map(lesson => ({
        id: lesson.id,
        slug: lesson.slug,
        title: lesson.title,
        description: lesson.description,
        duration: lesson.duration,
        completed: lesson.completed,
        locked: lesson.locked,
    }));
});

function handleLessonClick(lessonId: number) {
    const lesson = props.lessons.find(l => l.id === lessonId);
    if (lesson && !lesson.locked) {
        router.visit(`/go/learn/${lesson.slug}`);
    }
}
</script>

<template>
    <Head title="Learn Go" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="learn-page">
            <!-- Left Sidebar - Tips -->
            <aside class="learn-sidebar left-sidebar">
                <QuickTips />
            </aside>

            <!-- Main Content - Lessons -->
            <main class="learn-main">
                <ProgressTracker :completed="completedCount" :total="lessons.length" />

                <!-- Error message -->
                <div v-if="error" class="error-banner">
                    <p>{{ error }}</p>
                </div>

                <section class="lessons-section">
                    <h2 class="section-title">Lessons</h2>
                    <div v-if="lessons.length > 0" class="lessons-list">
                        <LessonCard
                            v-for="lesson in formattedLessons"
                            :key="lesson.id"
                            :lesson="lesson"
                            @click="handleLessonClick"
                        />
                    </div>
                    <div v-else class="empty-state">
                        <p>No lessons available yet. Check back soon!</p>
                    </div>
                </section>
            </main>

            <!-- Right Sidebar - Puzzle Widget -->
            <aside class="learn-sidebar right-sidebar">
                <DailyPuzzle />
            </aside>
        </div>
    </AppLayout>
</template>

<style scoped>
.learn-page {
    display: grid;
    grid-template-columns: 1fr;
    gap: 1.5rem;
    padding: 1.5rem;
    max-width: 1400px;
    margin: 0 auto;
}

@media (min-width: 768px) {
    .learn-page {
        grid-template-columns: 1fr 2fr;
    }

    .left-sidebar {
        order: 1;
    }

    .learn-main {
        order: 2;
    }

    .right-sidebar {
        order: 3;
        grid-column: 1 / 2;
        grid-row: 2;
    }
}

@media (min-width: 1200px) {
    .learn-page {
        grid-template-columns: 280px 1fr 280px;
    }

    .left-sidebar {
        order: 1;
    }

    .learn-main {
        order: 2;
    }

    .right-sidebar {
        order: 3;
        grid-column: auto;
        grid-row: auto;
    }
}

.learn-sidebar {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.learn-main {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.error-banner {
    padding: 0.75rem 1rem;
    background-color: rgba(239, 68, 68, 0.1);
    border: 1px solid rgba(239, 68, 68, 0.3);
    border-radius: 0.5rem;
}

.error-banner p {
    font-size: 0.875rem;
    color: #dc2626;
    margin: 0;
}

.section-title {
    font-size: 0.75rem;
    font-weight: 600;
    color: var(--muted-foreground);
    text-transform: uppercase;
    letter-spacing: 0.1em;
    margin: 0 0 1rem;
}

.lessons-list {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.empty-state {
    padding: 2rem;
    text-align: center;
    color: var(--muted-foreground);
    background-color: var(--card);
    border: 1px solid var(--border);
    border-radius: 0.75rem;
}

.empty-state p {
    margin: 0;
}
</style>
