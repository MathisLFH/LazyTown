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
                <h2 class="text-xl font-semibold tracking-tight">
                    Kontaktieren Sie uns
                </h2>
                <p class="mt-4">
                    Wir sind stets bestrebt, unser Tool weiterzuentwickeln und
                    auf die Bedürfnisse unserer Nutzer einzugehen. Wenn Sie
                    Fragen, Anregungen oder Feedback haben, zögern Sie bitte
                    nicht, uns zu kontaktieren.
                    <br /><br />
                    <b>Kontaktinformationen:</b><br />
                    <b>E-Mail:</b> Beispiel@email.com<br />
                    <b>Telefon:</b> +49 123 456789<br />
                    <b>Fax:</b> Keiner benutzt mehr Fax<br />
                    <b>Adresse:</b> Musterstraße 1, 12345 Musterstadt
                </p>
            </div>
        </section>
    </main>
</template>
