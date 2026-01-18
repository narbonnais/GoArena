<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Play, History, User, Settings, FlaskConical, FolderOpen, GraduationCap } from 'lucide-vue-next';

import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarGroup,
    SidebarGroupLabel,
    SidebarGroupContent,
} from '@/components/ui/sidebar';
import { type NavItem } from '@/types';

import AppLogo from './AppLogo.vue';

const playNavItems: NavItem[] = [
    {
        title: 'Play Now',
        href: '/go',
        icon: Play,
    },
    {
        title: 'Learn',
        href: '/go/learn',
        icon: GraduationCap,
    },
    {
        title: 'Analyze',
        href: '/go/analyze',
        icon: FlaskConical,
    },
    {
        title: 'History',
        href: '/go/history',
        icon: History,
    },
    {
        title: 'My Studies',
        href: '/go/studies',
        icon: FolderOpen,
    },
];

const accountNavItems: NavItem[] = [
    {
        title: 'Profile',
        href: '/settings/profile',
        icon: User,
    },
    {
        title: 'Settings',
        href: '/settings',
        icon: Settings,
    },
];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link href="/go">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <!-- Play Section -->
            <SidebarGroup>
                <SidebarGroupLabel>Play</SidebarGroupLabel>
                <SidebarGroupContent>
                    <NavMain :items="playNavItems" />
                </SidebarGroupContent>
            </SidebarGroup>

            <!-- Account Section -->
            <SidebarGroup>
                <SidebarGroupLabel>Account</SidebarGroupLabel>
                <SidebarGroupContent>
                    <NavMain :items="accountNavItems" />
                </SidebarGroupContent>
            </SidebarGroup>
        </SidebarContent>

        <SidebarFooter>
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
