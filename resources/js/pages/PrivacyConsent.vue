<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Form, Link } from '@inertiajs/vue3';
import DatenschutzerklaerungContent from '@/components/Landing/DatenschutzerklaerungContent.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { datenschutzerklaerung } from '@/routes';
import privacyConsent from '@/routes/privacy/consent';

const { update } = privacyConsent;
</script>

<template>
    <Head title="Datenschutz bestätigen" />
    <main class="min-h-screen bg-muted/30 p-6">
        <section
            class="mx-auto max-w-4xl space-y-6 rounded-2xl border border-sidebar-border/70 bg-background p-8 shadow-sm sm:p-12"
        >
            <header class="space-y-3">
                <h1 class="text-3xl font-bold">
                    Datenschutzerklärung bestätigen
                </h1>
                <p class="text-muted-foreground">
                    Bitte lies die aktuelle Datenschutzerklärung und bestätige
                    sie, um LazyTown weiter zu nutzen.
                </p>
            </header>

            <DatenschutzerklaerungContent />

            <Form
                v-bind="update.form()"
                v-slot="{ errors, processing }"
                class="grid gap-4 border-t pt-6"
            >
                <label class="flex items-start gap-3 text-sm">
                    <input
                        type="checkbox"
                        name="privacy_consent"
                        value="1"
                        required
                        class="mt-1"
                    />
                    <span>
                        Ich habe die
                        <Link
                            :href="datenschutzerklaerung()"
                            class="underline underline-offset-4"
                            target="_blank"
                            >Datenschutzerklärung</Link
                        >
                        gelesen und stimme der aktuellen Version zu.
                    </span>
                </label>
                <InputError :message="errors.privacy_consent" />
                <Button type="submit" :disabled="processing" class="w-fit">
                    Zustimmung speichern
                </Button>
            </Form>
        </section>
    </main>
</template>
