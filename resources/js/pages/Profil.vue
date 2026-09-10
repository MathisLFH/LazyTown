<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { CalendarDays, Mail, MapPin, Pencil, Phone } from '@lucide/vue';
import { computed } from 'vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { edit as editProfile } from '@/routes/profile';

const page = usePage();
const user = computed(() => page.props.auth.user);
const formattedBirthDate = computed(() => {
    const birthDate = user.value.birth_date?.slice(0, 10);

    if (!birthDate) {
        return 'Nicht angegeben';
    }

    const [year, month, day] = birthDate.split('-');

    return `${day}.${month}.${year}`;
});
</script>

<template>
    <Head title="Profil" />

    <div class="space-y-8">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <Heading title="Profil" description="Deine persönlichen Daten und Kontaktdaten." />
            <Button as-child>
                <Link :href="editProfile().url">
                    <Pencil /> Profil bearbeiten
                </Link>
            </Button>
        </div>

        <section class="max-w-2xl space-y-5 rounded-lg border p-6">
            <div class="grid gap-5 sm:grid-cols-2">
                <label class="grid gap-2 text-sm">
                    <span class="font-medium">Name</span>
                    <input :value="user.name" disabled class="h-10 rounded-md border border-input bg-muted px-3 text-muted-foreground opacity-75" />
                </label>
                <label class="grid gap-2 text-sm">
                    <span class="font-medium">Benutzer-ID</span>
                    <input :value="user.public_id" disabled class="h-10 rounded-md border border-input bg-muted px-3 text-muted-foreground opacity-75" />
                </label>
                <label class="grid gap-2 text-sm">
                    <span class="flex items-center gap-2 font-medium"><CalendarDays class="size-4" /> Geburtsdatum</span>
                    <input :value="formattedBirthDate" disabled class="h-10 rounded-md border border-input bg-muted px-3 text-muted-foreground opacity-75" />
                </label>
                <label class="grid gap-2 text-sm">
                    <span class="flex items-center gap-2 font-medium"><MapPin class="size-4" /> Wohnort</span>
                    <input :value="user.city ?? 'Nicht angegeben'" disabled class="h-10 rounded-md border border-input bg-muted px-3 text-muted-foreground opacity-75" />
                </label>
                <label class="grid gap-2 text-sm">
                    <span class="flex items-center gap-2 font-medium"><Mail class="size-4" /> E-Mail</span>
                    <input :value="user.email" disabled class="h-10 rounded-md border border-input bg-muted px-3 text-muted-foreground opacity-75" />
                </label>
                <label class="grid gap-2 text-sm">
                    <span class="flex items-center gap-2 font-medium"><Phone class="size-4" /> Telefon</span>
                    <input :value="user.phone ?? 'Nicht angegeben'" disabled class="h-10 rounded-md border border-input bg-muted px-3 text-muted-foreground opacity-75" />
                </label>
            </div>
        </section>
    </div>
</template>
