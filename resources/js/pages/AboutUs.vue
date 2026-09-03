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
            <p class="mb-3 text-sm font-medium uppercase tracking-[0.2em] width-[60px] text-muted-foreground">
                Über uner Team und unsere Services. 
            </p>
            <h1 class="text-3xl font-semibold tracking-tight sm:text-4xl">
                Was erwartet sie bei uns?
            </h1>
            <p class="mx-auto mt-4 max-w-lg text-base leading-7 text-muted-foreground" style="margin-bottom: 20px;">
                Organisiere Teams, Trainings, Spiele und Hallenbelegungen an
                einem zentralen Ort.
            </p>

            <h2 class="text-xl font-semibold tracking-tight"> 
                Wer sind wir?
            </h2>
            <p style="margin-bottom: 20px;">
                Wir sind ein junges Team von Entwicklern, die sich leidenschaftlich für die Entwicklung von Softwarelösungen für Sportvereine einsetzen. Unser Ziel ist es, die Verwaltung von Teams, Trainings, Spielen und Hallenbelegungen zu vereinfachen und zu optimieren.
            </p>
            <h2 class="text-xl font-semibold tracking-tight"> 
                Unser Tool
            </h2>
            <p style="margin-bottom: 20px;">
                Unser Tool bietet eine zentrale Plattform, auf der Vereinsmitglieder und Trainer alle relevanten Informationen und Ressourcen an einem Ort finden können. Wir legen großen Wert auf Benutzerfreundlichkeit, Effizienz und Sicherheit, um sicherzustellen, dass unsere Nutzer die bestmögliche Erfahrung haben.
            </p>
            <h2 class="text-xl font-semibold tracking-tight"> 
                Kontaktieren Sie uns
            </h2>
            <p style="margin-bottom: 20px;">
             Wir sind stets bestrebt, unser Tool weiterzuentwickeln und auf die Bedürfnisse unserer Nutzer einzugehen. Wenn Sie Fragen, Anregungen oder Feedback haben, zögern Sie bitte nicht, uns zu kontaktieren. Wir freuen uns darauf, von Ihnen zu hören!
             <br><br>
            <b>Kontaktinformationen:</b><br>
            <b>E-Mail:</b> Beispiel@email.com<br>
            <b>Telefon:</b> +49 123 456789<br>
            <b>Fax:</b> Keiner benutzt mehr Fax<br>
            <b>Adresse:</b> Musterstraße 1, 12345 Musterstadt
            </p>
            <div class="mt-8 flex flex-col justify-center gap-3 sm:flex-row">
               
            </div>
        </section>
    </main>
    <footer class="bg-gray-100 text-gray-600 py-8 mt-12">
    <div class="max-w-7xl mx-auto px-6 text-center">
        
        <div class="flex justify-center gap-6 mb-4">
            <a href="#" class="hover:text-gray-900">
                Impressum
            </a>

            <a href="#" class="hover:text-gray-900">
                Datenschutz
            </a>

            <a href="#" class="hover:text-gray-900">
                Kontakt
            </a>

            <a href="#" class="hover:text-gray-900">
                Über uns
            </a>
        </div>

        <p class="text-sm text-gray-500">
            © 2026 Unser Verein. Alle Rechte vorbehalten.
        </p>

    </div>
</footer>
</template>
