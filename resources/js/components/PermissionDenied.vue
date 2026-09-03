<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ShieldX } from '@lucide/vue';
import { home } from '@/routes';
</script>

<template>
    <section class="mx-auto flex max-w-lg flex-col items-center gap-4 px-6 py-16 text-center">
        <div class="flex size-14 items-center justify-center rounded-full bg-destructive/10 text-destructive">
            <ShieldX class="size-7" aria-hidden="true" />
        </div>
        <div class="space-y-2">
            <h1 class="text-2xl font-semibold">Keine Berechtigung für diese Seite</h1>
            <p class="text-muted-foreground">Du hast nicht die erforderliche Rolle, um diesen Bereich zu öffnen.</p>
        </div>
        <Link :href="home().url" class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90">
            Zur Startseite
        </Link>
    </section>
</template>