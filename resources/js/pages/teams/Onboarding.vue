<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { Building2 } from '@lucide/vue';
import { store } from '@/actions/App/Http/Controllers/Teams/ClubOnboardingController';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { home } from '@/routes';
</script>

<template>
    <Head title="Verein erstellen" />

    <main class="mx-auto max-w-xl space-y-8 p-6">
        <div class="space-y-3">
            <Building2 class="size-8 text-primary" aria-hidden="true" />
            <h1 class="text-3xl font-semibold">Deinen Verein einrichten</h1>
            <p class="text-muted-foreground">Erstelle deinen Verein und schalte anschließend den Vereinszugang über die Test-Zahlungsseite frei.</p>
        </div>

        <Form v-bind="store.form()" class="space-y-6 rounded-lg border p-6" v-slot="{ errors, processing }">
            <div class="grid gap-2">
                <Label for="name">Vereinsname</Label>
                <Input id="name" name="name" placeholder="Name deines Vereins" required autofocus />
                <InputError :message="errors.name" />
            </div>
            <Button type="submit" :disabled="processing">{{ processing ? 'Wird erstellt...' : 'Weiter zur Zahlung' }}</Button>
        </Form>

        <Link :href="home().url" class="text-sm text-muted-foreground hover:text-foreground">Später einrichten</Link>
    </main>
</template>