<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppContent from '@/components/AppContent.vue';
import AppShell from '@/components/AppShell.vue';
import HotReloadTimer from '@/components/HotReloadTimer.vue';
import Navbar from '@/components/Navbar.vue';
import { Toaster } from '@/components/ui/sonner';
import { home, logout } from '@/routes';
import { Link } from '@inertiajs/vue3';


const page = usePage();
const isAuthenticated = computed(() => Boolean(page.props.auth.user));
const hasClubMembership = computed(() =>
    page.props.teams.some((team) => !team.isPersonal),
);

const title = usePage()
                .url.split('/')
                .pop()
                ?.replace('-', ' ')
</script>

<template>
    <Head/>
    <main
        class="flex min-h-screen items-center justify-center bg-muted/30 p-6"
    >  
        <section
            class="w-full relative max-w-20xl rounded-2xl border border-sidebar-border/70 bg-background p-8 text-center shadow-sm sm:p-12"
        >
        <div
                class="mx-auto mb-6 flex size-20 items-center justify-center rounded-2xl bg-primary text-2xl font-bold text-primary-foreground"
            >
                LT
            </div>
       
            <div>
            <h2 class="text-3xl font-bold capitalize">
                {{ title }}
            </h2>
             <button class="absolute top-4 left-4" id="layout_button">
            <Link
                :href="home().url"
                class="rounded-md border px-3 py-2 text-sm"
            >
                Zurück zur Startseite
            </Link>
            </button>

            <slot />          
            </div>
        </section>
    </main>
</template>