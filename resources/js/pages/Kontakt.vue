<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { login, register } from '@/routes';
</script>

<template>
    <Head title="Kontakt"/>
    <main
        class="flex min-h-screen items-center justify-center bg-muted/30 p-6"
    >
        <section
            class="w-full max-w-20xl rounded-2xl border border-sidebar-border/70 bg-background p-8 text-center shadow-sm sm:p-12"
        >
            <div>
                LT
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
            </div>
        </section>
    </main>
</template>
