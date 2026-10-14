<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { home } from '@/routes';

const title = usePage().url.split('/').pop()?.replace('-', ' ');
</script>

<template>
    <Head />
    <main class="flex min-h-screen items-center justify-center bg-muted/30 p-6">
        <section
            class="max-w-20xl relative w-full rounded-2xl border border-sidebar-border/70 bg-background p-8 text-center shadow-sm sm:p-12"
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
