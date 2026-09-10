<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { home } from '@/routes';
</script>

<template>
    <main
        class="flex min-h-screen items-center justify-center bg-muted/30 p-6"
    >
        <Link
            :href="home().url"
            class="rounded-md border px-3 py-2 text-sm transition-colors hover:bg-muted"
        >
            Zurück zur Startseite
        </Link>
        <section
            class="w-full max-w-20xl rounded-2xl border border-sidebar-border/70 bg-background p-8 text-center shadow-sm sm:p-12"
        >
            <div
                class="mx-auto mb-6 flex size-20 items-center justify-center rounded-2xl bg-primary text-2xl font-bold text-primary-foreground"
            >
                LT
            </div>
            <h2 class="text-xl font-semibold tracking-tight">
                Über unser Team und unsere Services
            </h2>
            <p
                class="mb-3 text-sm font-medium uppercase tracking-[0.2em] text-muted-foreground"
            >
                Über unser Team und unsere Services.
            </p>
            <h1 class="text-3xl font-semibold tracking-tight sm:text-4xl">
                Was erwartet Sie bei uns?
            </h1>
            <p
                class="mx-auto mt-4 max-w-lg text-base leading-7 text-muted-foreground"
            >
                Organisiere Teams, Trainings, Spiele und Hallenbelegungen an
                einem zentralen Ort.
            </p>

            <h2 class="text-xl font-semibold tracking-tight">Wer sind wir?</h2>
            <p class="mt-4">
                Wir sind ein junges Team von Entwicklern, die sich leidenschaftlich
                für die Entwicklung von Softwarelösungen für Sportvereine einsetzen.
                Unser Ziel ist es, die Verwaltung von Teams, Trainings, Spielen und
                Hallenbelegungen zu vereinfachen und zu optimieren.
            </p>
            <h2 class="mt-6 text-xl font-semibold tracking-tight">Unser Tool</h2>
            <p class="mt-4">
                Unser Tool bietet eine zentrale Plattform, auf der Vereinsmitglieder
                und Trainer alle relevanten Informationen und Ressourcen an einem Ort
                finden können. Wir legen großen Wert auf Benutzerfreundlichkeit,
                Effizienz und Sicherheit.
            </p>
            <h2 class="mt-6 text-xl font-semibold tracking-tight">
                Kontaktieren Sie uns
            </h2>
            <p class="mt-4">
                Wenn Sie Fragen, Anregungen oder Feedback haben, zögern Sie bitte
                nicht, uns zu kontaktieren.
                <br /><br />
                <b>Kontaktinformationen:</b><br />
                <b>E-Mail:</b> Beispiel@email.com<br />
                <b>Telefon:</b> +49 123 456789<br />
                <b>Fax:</b> Keiner benutzt mehr Fax<br />
                <b>Adresse:</b> Musterstraße 1, 12345 Musterstadt
            </p>
        </section>
    </main>
</template>
