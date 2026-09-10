<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppContent from '@/components/AppContent.vue';
import AppShell from '@/components/AppShell.vue';
import HotReloadTimer from '@/components/HotReloadTimer.vue';
import Navbar from '@/components/Navbar.vue';
import { Toaster } from '@/components/ui/sonner';
import { home, logout } from '@/routes';


const props = defineProps<{
    title?: string;
}>();
const page = usePage();
const isAuthenticated = computed(() => Boolean(page.props.auth.user));
const hasClubMembership = computed(() =>
    page.props.teams.some((team) => !team.isPersonal),
);
</script>

<template>
    <Head title="Impressum"/>
    <main
        class="flex min-h-screen items-center justify-center bg-muted/30 p-6"
    >
       
        <section
            class="relative w-full max-w-20xl rounded-2xl border border-sidebar-border/70 bg-background p-8 text-center shadow-sm sm:p-12"
        >
         <button class="absolute top-4 left-4">
            <Link
                :href="home().url"
                class="rounded-md border px-3 py-2 text-sm transition-colors hover:bg-muted"
            >
                Zurück zur Startseite
            </Link>
        </button>
            <div>
               <h1> LOGO</h1>
            <h2 class="text-3xl font-bold">
                {{ title ?? 'Impressum' }}
            </h2>
            <slot />          
            </div>
        </section>
    </main>
</template>