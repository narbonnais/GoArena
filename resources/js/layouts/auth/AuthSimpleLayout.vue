<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { home } from '@/routes';

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div class="auth-page">
        <div class="auth-container">
            <!-- Logo and branding -->
            <div class="auth-header">
                <Link :href="home()" class="logo-link">
                    <div class="logo-box">
                        <AppLogoIcon class="logo-icon" />
                    </div>
                    <span class="logo-text">GoArena</span>
                </Link>
            </div>

            <!-- Auth card -->
            <div class="auth-card">
                <div class="auth-card-header">
                    <h1 class="auth-title">{{ title }}</h1>
                    <p class="auth-description">{{ description }}</p>
                </div>
                <slot />
            </div>
        </div>
    </div>
</template>

<style scoped>
.auth-page {
    min-height: 100vh;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 2rem 1rem;
    background-color: var(--background);
}

.auth-container {
    width: 100%;
    max-width: 400px;
    display: flex;
    flex-direction: column;
    gap: 2rem;
}

.auth-header {
    display: flex;
    justify-content: center;
}

.logo-link {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    text-decoration: none;
}

.logo-box {
    width: 44px;
    height: 44px;
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: var(--go-green);
    border-radius: 0.625rem;
}

.logo-icon {
    width: 28px;
    height: 28px;
}

.logo-text {
    font-size: 1.5rem;
    font-weight: 800;
    color: var(--foreground);
    letter-spacing: -0.02em;
}

.auth-card {
    padding: 2rem;
    background-color: var(--card);
    border-radius: 0.75rem;
    border: 1px solid var(--border);
}

.auth-card-header {
    text-align: center;
    margin-bottom: 1.5rem;
}

.auth-title {
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--foreground);
    margin: 0 0 0.5rem;
}

.auth-description {
    font-size: 0.875rem;
    color: var(--muted-foreground);
    margin: 0;
}
</style>
