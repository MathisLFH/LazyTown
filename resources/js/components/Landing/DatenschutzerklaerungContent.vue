<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { home } from '@/routes';
</script>

<template>
    <main
        class="flex min-h-screen items-center justify-center bg-muted/30 p-6"
    >
        <section
            class="relative w-full max-w-20xl rounded-2xl border border-sidebar-border/70 bg-background p-8 text-center shadow-sm sm:p-12"
        >
            <Link
                :href="home().url"
                class="rounded-md border px-3 py-2 text-sm transition-colors hover:bg-muted"
            >
                Zurück zur Startseite
            </Link>
            <div class="mt-6">
                <div class="mb-4 text-2xl font-bold">LT</div>
                <h1 class="text-3xl font-bold">Datenschutzerklärung</h1>
                <p class="mt-4">Wir bekommen alle Daten, lol</p>
            </div>
        </section>
    </main>
</template>
