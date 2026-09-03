<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppContent from '@/components/AppContent.vue';
import AppShell from '@/components/AppShell.vue';
import HotReloadTimer from '@/components/HotReloadTimer.vue';
import Navbar from '@/components/Navbar.vue';
import { Toaster } from '@/components/ui/sonner';

const page = usePage();
const isAuthenticated = computed(() => Boolean(page.props.auth.user));
const hasClubMembership = computed(() =>
    page.props.teams.some((team) => !team.isPersonal),
);
</script>

<template>
    <div class="flex min-h-screen flex-col">
        <Navbar v-if="isAuthenticated && hasClubMembership" />
        <AppShell variant="header" class="flex-1">
            <AppContent variant="header" class="min-h-0">
                <slot />
            </AppContent>
            <Toaster />
        </AppShell>
        <HotReloadTimer />
    </div>
</template>
