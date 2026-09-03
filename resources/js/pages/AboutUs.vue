<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import DashboardContent from '@/components/DashboardContent.vue';
import { login, register } from '@/routes';

const page = usePage();
</script>

<template>
    <Head title="About Us"/>
    <main
        class="flex min-h-screen items-center justify-center bg-muted/30 p-6"
    >
        <section
            class="w-full max-w-2xl rounded-2xl border border-sidebar-border/70 bg-background p-8 text-center shadow-sm sm:p-12"
        >
            <div
                class="mx-auto mb-6 flex size-20 items-center justify-center rounded-2xl bg-primary text-2xl font-bold text-primary-foreground"
            >
                LT
            </div>
            <p class="mb-3 text-sm font-medium uppercase tracking-[0.2em] text-muted-foreground">
                Vereinsverwaltung
            </p>
            <h1 class="text-3xl font-semibold tracking-tight sm:text-4xl">
                Herzlich willkommen bei LazyTown
            </h1>
            <p class="mx-auto mt-4 max-w-lg text-base leading-7 text-muted-foreground">
                Organisiere Teams, Trainings, Spiele und Hallenbelegungen an
                einem zentralen Ort. Melde dich an, um den Bereich deines
                Vereins zu öffnen.
            </p>
            <div class="mt-8 flex flex-col justify-center gap-3 sm:flex-row">
                <Link
                    :href="login()"
                    class="rounded-md bg-primary px-5 py-2.5 text-sm font-medium text-primary-foreground transition hover:opacity-90"
                >
                    Anmelden
                </Link>
                <Link
                    :href="register()"
                    class="rounded-md border px-5 py-2.5 text-sm font-medium transition hover:bg-accent"
                >
                    Konto erstellen
                </Link>
            </div>
        </section>
    </main>
</template>
