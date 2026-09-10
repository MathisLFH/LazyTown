<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { login, register, home, logout } from '@/routes';
</script>

<template>
    <Head title="Impressum"/>
    <main
        class="flex min-h-screen items-center justify-center bg-muted/30 p-6"
    >
    <butoon>
     <Link
        :href="home().url"
        class="rounded-md border px-3 py-2 text-sm transition-colors hover:bg-muted"
    >
        Zurück zur Startseite
    </Link>
    </butoon>
        <section
            class="w-full max-w-20xl rounded-2xl border border-sidebar-border/70 bg-background p-8 text-center shadow-sm sm:p-12"
        >
            <div>
                LT
            <h1 class="text-3xl font-bold">
                Impressum
            </h1>

            <h2 class="text-xl font-semibold mt-8">
                Angaben zum Betreiber
            </h2>

            <p class="mt-4">
                Musterverein e. V.<br>
                Musterstraße 1<br>
                12345 Musterstadt<br>
                Deutschland
            </p>

            <h2 class="text-xl font-semibold mt-8">
                Kontakt
            </h2>

            <p class="mt-4">
                E-Mail: info@musterverein.de<br>
                Telefon: +49 123 456789
            </p>

            <h2 class="text-xl font-semibold mt-8">
                Vertretungsberechtigt
            </h2>

            <p class="mt-4">
                Max Mustermann, Vorsitzender
            </p>

            <h2 class="text-xl font-semibold mt-8">
                Registereintrag
            </h2>

            <p class="mt-4">
                Registergericht: Amtsgericht Musterstadt<br>
                Registernummer: VR 12345
            </p>
            </div>
        </section>
    </main>
</template>
