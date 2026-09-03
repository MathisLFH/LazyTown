<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppContent from '@/components/AppContent.vue';
import AppShell from '@/components/AppShell.vue';
import Navbar from '@/components/Navbar.vue';
import { Toaster } from '@/components/ui/sonner';

const isAuthenticated = computed(() => Boolean(usePage().props.auth.user));
</script>

<template>
    <div class="flex min-h-screen flex-col">
        <Navbar v-if="isAuthenticated" />
        <AppShell variant="header" class="flex-1">
            <AppContent variant="header" class="min-h-0">
                <slot />
            </AppContent>
            <Toaster />
        </AppShell>
    </div>
</template>
