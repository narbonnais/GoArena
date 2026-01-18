<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Play, Users, History, Trophy } from 'lucide-vue-next';

import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { GO_BOTS } from '@/composables/go/useGoBots';
import { login, register, dashboard } from '@/routes';

withDefaults(
    defineProps<{
        canRegister: boolean;
    }>(),
    {
        canRegister: true,
    },
);
</script>

<template>
    <Head title="GoArena - Master the Ancient Game">
        <link rel="preconnect" href="https://rsms.me/" />
        <link rel="stylesheet" href="https://rsms.me/inter/inter.css" />
    </Head>

    <div class="landing-page">
        <!-- Header -->
        <header class="landing-header">
            <Link href="/" class="logo-link">
                <div class="logo-icon">
                    <AppLogoIcon class="w-8 h-8" />
                </div>
                <span class="logo-text">GoArena</span>
            </Link>

            <nav class="header-nav">
                <Link
                    v-if="$page.props.auth.user"
                    :href="dashboard()"
                    class="nav-btn nav-btn-primary"
                >
                    Dashboard
                </Link>
                <template v-else>
                    <Link :href="login()" class="nav-btn nav-btn-ghost">
                        Log in
                    </Link>
                    <Link
                        v-if="canRegister"
                        :href="register()"
                        class="nav-btn nav-btn-primary"
                    >
                        Sign up
                    </Link>
                </template>
            </nav>
        </header>

        <!-- Hero Section -->
        <main class="hero-section">
            <div class="hero-content">
                <h1 class="hero-title">
                    Master the<br />
                    <span class="hero-highlight">Ancient Game</span>
                </h1>
                <p class="hero-subtitle">
                    Play Go against AI opponents of varying difficulty.
                    Improve your skills and track your progress.
                </p>

                <Link
                    :href="$page.props.auth.user ? '/go' : login()"
                    class="play-button"
                >
                    <Play class="play-icon" :size="28" />
                    <span>PLAY NOW</span>
                </Link>

                <!-- Features -->
                <div class="features">
                    <div class="feature">
                        <Users class="feature-icon" :size="20" />
                        <span>4 AI Opponents</span>
                    </div>
                    <div class="feature">
                        <Trophy class="feature-icon" :size="20" />
                        <span>Multiple Difficulties</span>
                    </div>
                    <div class="feature">
                        <History class="feature-icon" :size="20" />
                        <span>Game History</span>
                    </div>
                </div>
            </div>

            <!-- Decorative Board Preview -->
            <div class="hero-visual">
                <div class="board-preview">
                    <svg viewBox="0 0 380 380" class="board-svg">
                        <!-- Board background -->
                        <rect x="0" y="0" width="380" height="380" rx="8" fill="#ddb06d" />

                        <!-- Grid lines -->
                        <g stroke="#5c4a32" stroke-width="1" opacity="0.6">
                            <!-- Vertical lines -->
                            <line v-for="i in 9" :key="`v-${i}`" :x1="20 + (i-1) * 42.5" y1="20" :x2="20 + (i-1) * 42.5" y2="360" />
                            <!-- Horizontal lines -->
                            <line v-for="i in 9" :key="`h-${i}`" :x1="20" :y1="20 + (i-1) * 42.5" :x2="360" :y2="20 + (i-1) * 42.5" />
                        </g>

                        <!-- Star points -->
                        <g fill="#5c4a32">
                            <circle cx="105" cy="105" r="4" />
                            <circle cx="275" cy="105" r="4" />
                            <circle cx="190" cy="190" r="4" />
                            <circle cx="105" cy="275" r="4" />
                            <circle cx="275" cy="275" r="4" />
                        </g>

                        <!-- Sample stones - Black -->
                        <g>
                            <circle cx="105" cy="147.5" r="18" fill="#1a1a1a" />
                            <circle cx="102" cy="144" r="5" fill="#4a4a4a" opacity="0.5" />

                            <circle cx="147.5" cy="147.5" r="18" fill="#1a1a1a" />
                            <circle cx="144.5" cy="144.5" r="5" fill="#4a4a4a" opacity="0.5" />

                            <circle cx="190" cy="190" r="18" fill="#1a1a1a" />
                            <circle cx="187" cy="187" r="5" fill="#4a4a4a" opacity="0.5" />

                            <circle cx="232.5" cy="232.5" r="18" fill="#1a1a1a" />
                            <circle cx="229.5" cy="229.5" r="5" fill="#4a4a4a" opacity="0.5" />
                        </g>

                        <!-- Sample stones - White -->
                        <g>
                            <circle cx="147.5" cy="190" r="18" fill="#f5f5f5" stroke="#ddd" stroke-width="0.5" />
                            <circle cx="144.5" cy="187" r="5" fill="#fff" opacity="0.7" />

                            <circle cx="190" cy="147.5" r="18" fill="#f5f5f5" stroke="#ddd" stroke-width="0.5" />
                            <circle cx="187" cy="144.5" r="5" fill="#fff" opacity="0.7" />

                            <circle cx="232.5" cy="190" r="18" fill="#f5f5f5" stroke="#ddd" stroke-width="0.5" />
                            <circle cx="229.5" cy="187" r="5" fill="#fff" opacity="0.7" />
                        </g>
                    </svg>

                    <!-- Bot avatars around the board -->
                    <img
                        v-for="(bot, index) in GO_BOTS.slice(0, 4)"
                        :key="bot.id"
                        :src="bot.avatarUrl"
                        :alt="bot.name"
                        class="floating-bot"
                        :class="`bot-${index + 1}`"
                    />
                </div>
            </div>
        </main>

        <!-- Board Size Showcase -->
        <section class="board-showcase">
            <h2 class="showcase-title">Choose Your Board</h2>
            <div class="board-options">
                <div class="board-option">
                    <div class="mini-board-showcase board-9">
                        <svg viewBox="0 0 100 100">
                            <rect x="0" y="0" width="100" height="100" fill="#ddb06d" rx="4" />
                            <g stroke="#5c4a32" stroke-width="0.8" opacity="0.5">
                                <line v-for="i in 9" :key="`v9-${i}`" :x1="5 + (i-1) * 11.25" y1="5" :x2="5 + (i-1) * 11.25" y2="95" />
                                <line v-for="i in 9" :key="`h9-${i}`" :x1="5" :y1="5 + (i-1) * 11.25" :x2="95" :y2="5 + (i-1) * 11.25" />
                            </g>
                        </svg>
                    </div>
                    <h3 class="board-size-title">9x9</h3>
                    <p class="board-size-desc">Quick games, perfect for beginners</p>
                </div>
                <div class="board-option">
                    <div class="mini-board-showcase board-13">
                        <svg viewBox="0 0 100 100">
                            <rect x="0" y="0" width="100" height="100" fill="#ddb06d" rx="4" />
                            <g stroke="#5c4a32" stroke-width="0.5" opacity="0.5">
                                <line v-for="i in 13" :key="`v13-${i}`" :x1="4 + (i-1) * 7.67" y1="4" :x2="4 + (i-1) * 7.67" y2="96" />
                                <line v-for="i in 13" :key="`h13-${i}`" :x1="4" :y1="4 + (i-1) * 7.67" :x2="96" :y2="4 + (i-1) * 7.67" />
                            </g>
                        </svg>
                    </div>
                    <h3 class="board-size-title">13x13</h3>
                    <p class="board-size-desc">Medium length, balanced play</p>
                </div>
                <div class="board-option featured">
                    <div class="mini-board-showcase board-19">
                        <svg viewBox="0 0 100 100">
                            <rect x="0" y="0" width="100" height="100" fill="#ddb06d" rx="4" />
                            <g stroke="#5c4a32" stroke-width="0.3" opacity="0.5">
                                <line v-for="i in 19" :key="`v19-${i}`" :x1="3 + (i-1) * 4.94" y1="3" :x2="3 + (i-1) * 4.94" y2="97" />
                                <line v-for="i in 19" :key="`h19-${i}`" :x1="3" :y1="3 + (i-1) * 4.94" :x2="97" :y2="3 + (i-1) * 4.94" />
                            </g>
                            <!-- Star points for 19x19 -->
                            <g fill="#5c4a32">
                                <circle cx="22.52" cy="22.52" r="2" />
                                <circle cx="50" cy="22.52" r="2" />
                                <circle cx="77.48" cy="22.52" r="2" />
                                <circle cx="22.52" cy="50" r="2" />
                                <circle cx="50" cy="50" r="2" />
                                <circle cx="77.48" cy="50" r="2" />
                                <circle cx="22.52" cy="77.48" r="2" />
                                <circle cx="50" cy="77.48" r="2" />
                                <circle cx="77.48" cy="77.48" r="2" />
                            </g>
                        </svg>
                    </div>
                    <h3 class="board-size-title">19x19</h3>
                    <p class="board-size-desc">Traditional full game</p>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer class="landing-footer">
            <p>GoArena - Play the ancient game of Go</p>
        </footer>
    </div>
</template>

<style scoped>
.landing-page {
    min-height: 100vh;
    display: flex;
    flex-direction: column;
    background-color: var(--background);
    color: var(--foreground);
}

/* Header */
.landing-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 2rem;
    background-color: var(--background-deep);
    border-bottom: 1px solid var(--border);
}

.logo-link {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    text-decoration: none;
}

.logo-icon {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: var(--go-green);
    border-radius: 0.5rem;
}

.logo-text {
    font-size: 1.5rem;
    font-weight: 800;
    color: var(--foreground);
    letter-spacing: -0.02em;
}

.header-nav {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.nav-btn {
    padding: 0.5rem 1rem;
    border-radius: 0.5rem;
    font-size: 0.875rem;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.15s ease;
}

.nav-btn-ghost {
    color: var(--muted-foreground);
}

.nav-btn-ghost:hover {
    color: var(--foreground);
    background-color: var(--accent);
}

.nav-btn-primary {
    background-color: var(--go-green);
    color: white;
}

.nav-btn-primary:hover {
    background-color: var(--go-green-hover);
}

/* Hero Section */
.hero-section {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 4rem 2rem;
    gap: 4rem;
}

@media (max-width: 1024px) {
    .hero-section {
        flex-direction: column;
        padding: 2rem 1rem;
        gap: 2rem;
    }
}

.hero-content {
    max-width: 500px;
}

.hero-title {
    font-size: 3.5rem;
    font-weight: 800;
    line-height: 1.1;
    margin: 0 0 1.5rem;
    letter-spacing: -0.02em;
}

@media (max-width: 768px) {
    .hero-title {
        font-size: 2.5rem;
    }
}

.hero-highlight {
    color: var(--go-green);
}

.hero-subtitle {
    font-size: 1.125rem;
    color: var(--muted-foreground);
    line-height: 1.6;
    margin: 0 0 2rem;
}

.play-button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.75rem;
    padding: 1rem 2.5rem;
    font-size: 1.25rem;
    font-weight: 800;
    letter-spacing: 0.1em;
    color: white;
    background: linear-gradient(135deg, var(--go-green) 0%, var(--go-green-hover) 100%);
    border: none;
    border-radius: 0.75rem;
    text-decoration: none;
    transition: all 0.2s ease;
    box-shadow: 0 4px 20px var(--go-green-muted);
}

.play-button:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 30px var(--go-green-muted);
}

.play-icon {
    fill: white;
}

/* Features */
.features {
    display: flex;
    gap: 1.5rem;
    margin-top: 2.5rem;
    flex-wrap: wrap;
}

.feature {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
    color: var(--muted-foreground);
}

.feature-icon {
    color: var(--go-green);
}

/* Board Preview */
.hero-visual {
    position: relative;
}

.board-preview {
    position: relative;
    width: 380px;
    height: 380px;
    border-radius: 12px;
    box-shadow:
        0 25px 50px -12px rgba(0, 0, 0, 0.5),
        0 0 0 1px rgba(255, 255, 255, 0.1);
    overflow: hidden;
}

@media (max-width: 768px) {
    .board-preview {
        width: 300px;
        height: 300px;
    }

    .board-svg {
        width: 100%;
        height: 100%;
    }
}

.board-svg {
    display: block;
}

/* Floating bots */
.floating-bot {
    position: absolute;
    width: 48px;
    height: 48px;
    border-radius: 50%;
    animation: float 3s ease-in-out infinite;
    filter: drop-shadow(0 4px 8px rgba(0, 0, 0, 0.3));
    background-color: var(--card);
    border: 2px solid var(--border);
}

.bot-1 {
    top: -20px;
    left: -20px;
    animation-delay: 0s;
}

.bot-2 {
    top: -20px;
    right: -20px;
    animation-delay: 0.5s;
}

.bot-3 {
    bottom: -20px;
    left: -20px;
    animation-delay: 1s;
}

.bot-4 {
    bottom: -20px;
    right: -20px;
    animation-delay: 1.5s;
}

@keyframes float {
    0%, 100% {
        transform: translateY(0);
    }
    50% {
        transform: translateY(-10px);
    }
}

/* Board Showcase */
.board-showcase {
    padding: 4rem 2rem;
    background-color: var(--background-deep);
    border-top: 1px solid var(--border);
}

.showcase-title {
    text-align: center;
    font-size: 2rem;
    font-weight: 700;
    color: var(--foreground);
    margin: 0 0 2.5rem;
}

.board-options {
    display: flex;
    justify-content: center;
    gap: 2rem;
    flex-wrap: wrap;
}

.board-option {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 1.5rem;
    background-color: var(--card);
    border: 1px solid var(--border);
    border-radius: 1rem;
    transition: all 0.2s ease;
    max-width: 200px;
}

.board-option:hover {
    transform: translateY(-4px);
    border-color: var(--go-green);
    box-shadow: 0 12px 24px rgba(0, 0, 0, 0.15);
}

.board-option.featured {
    border-color: var(--go-green);
    background-color: var(--go-green-muted);
}

.mini-board-showcase {
    width: 120px;
    height: 120px;
    border-radius: 0.5rem;
    overflow: hidden;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    margin-bottom: 1rem;
}

.mini-board-showcase svg {
    width: 100%;
    height: 100%;
    display: block;
}

.board-size-title {
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--foreground);
    margin: 0 0 0.25rem;
}

.board-size-desc {
    font-size: 0.875rem;
    color: var(--muted-foreground);
    text-align: center;
    margin: 0;
}

/* Footer */
.landing-footer {
    padding: 1.5rem;
    text-align: center;
    color: var(--muted-foreground);
    font-size: 0.875rem;
    border-top: 1px solid var(--border);
    background-color: var(--background-deep);
}
</style>
