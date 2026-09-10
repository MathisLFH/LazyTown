<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { home } from '@/routes';
</script>

<template>
    <main
        class="flex min-h-screen items-center justify-center bg-muted/30 p-6"
    >
        <section
            class="w-full max-w-20xl rounded-2xl border border-sidebar-border/70 bg-background p-8 text-center shadow-sm sm:p-12"
        >
            <Link
                :href="home().url"
                class="rounded-md border px-3 py-2 text-sm transition-colors hover:bg-muted"
            >
                Zurück zur Startseite
            </Link>
            <div class="mt-6">
                <div class="mb-4 text-2xl font-bold">LT</div>
                <h1 class="text-3xl font-bold">Impressum</h1>

                <h2 class="mt-8 text-xl font-semibold">Angaben zum Betreiber</h2>
                <p class="mt-4">
                    Musterverein e. V.<br />
                    Musterstraße 1<br />
                    12345 Musterstadt<br />
                    Deutschland
                </p>

                <h2 class="mt-8 text-xl font-semibold">Kontakt</h2>
                <p class="mt-4">
                    E-Mail: info@musterverein.de<br />
                    Telefon: +49 123 456789
                </p>

                <h2 class="mt-8 text-xl font-semibold">Vertretungsberechtigt</h2>
                <p class="mt-4">Max Mustermann, Vorsitzender</p>

                <h2 class="mt-8 text-xl font-semibold">Registereintrag</h2>
                <p class="mt-4">
                    Registergericht: Amtsgericht Musterstadt<br />
                    Registernummer: VR 12345
                </p>
            </div>
        </section>
    </main>
</template>
