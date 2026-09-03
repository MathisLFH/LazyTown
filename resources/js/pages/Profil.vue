<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { reactive, ref } from 'vue';
import { useAuth } from '@/composables/useAuth';

const { activeRole } = useAuth();
const isLoggedIn = ref(true);
const saved = ref(false);
const profile = reactive({
    name: 'Max Mustermann',
    email: 'max@example.test',
    phone: '+49 170 1234567',
    dsgvoConsent: true,
});

const payments = [
    { member: 'Max Mustermann', amount: '60,00 €', status: 'Offen' },
    { member: 'Alex Müller', amount: '60,00 €', status: 'Bezahlt' },
];

function saveProfile(): void {
    saved.value = true;
}
</script>

<template>
    <Head title="Profil" />

    <main class="space-y-6 p-6">
        <h1 class="text-2xl font-semibold">Profil</h1>

        <section class="rounded-lg border border-sidebar-border/70 p-5">
            <form class="grid max-w-xl gap-4" @submit.prevent="saveProfile">
                <label class="grid gap-1 text-sm">
                    Name
                    <input v-model="profile.name" class="h-10 rounded-md border border-input px-3" />
                </label>
                <label class="grid gap-1 text-sm">
                    E-Mail
                    <input v-model="profile.email" type="email" class="h-10 rounded-md border border-input px-3" />
                </label>
                <label class="grid gap-1 text-sm">
                    Telefon
                    <input v-model="profile.phone" type="tel" class="h-10 rounded-md border border-input px-3" />
                </label>
                <label class="flex items-center gap-2 text-sm">
                    <input v-model="profile.dsgvoConsent" type="checkbox" />
                    Datenschutz-Einstellungen bestätigt
                </label>
                <div class="flex flex-wrap gap-2">
                    <button type="submit" class="rounded-md bg-primary px-3 py-2 text-sm text-primary-foreground">Profil speichern</button>
                    <button type="button" class="rounded-md border px-3 py-2 text-sm" @click="isLoggedIn = !isLoggedIn">
                        {{ isLoggedIn ? 'Logout' : 'Login' }}
                    </button>
                    <button type="button" class="rounded-md border px-3 py-2 text-sm">Datenexport anfordern</button>
                </div>
                <p v-if="saved" class="text-sm text-muted-foreground">Profil lokal gespeichert.</p>
            </form>
        </section>

        <section v-if="activeRole === 'Verwaltung'" class="rounded-lg border border-sidebar-border/70 p-5">
            <h2 class="mb-4 text-lg font-semibold">Zahlungsübersicht</h2>
            <div class="space-y-2">
                <div v-for="payment in payments" :key="payment.member" class="flex justify-between rounded-md bg-muted/50 p-3 text-sm">
                    <span>{{ payment.member }} · {{ payment.status }}</span>
                    <span class="font-medium">{{ payment.amount }}</span>
                </div>
            </div>
        </section>
    </main>
</template>
