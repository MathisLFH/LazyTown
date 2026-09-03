<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

type ScheduleEntry = {
    id: number;
    title: string;
    type: 'Spiel' | 'Training';
    date: string;
    time: string;
    place: string;
};

const filter = ref<'Alle' | ScheduleEntry['type']>('Alle');
const view = ref<'liste' | 'kalender'>('liste');
const entries: ScheduleEntry[] = [
    { id: 1, title: 'Training Herren 1', type: 'Training', date: '02.09.2026', time: '18:30', place: 'Halle Nord' },
    { id: 2, title: 'Heimspiel gegen TSV West', type: 'Spiel', date: '05.09.2026', time: '15:00', place: 'Sportzentrum' },
    { id: 3, title: 'Training Herren 1', type: 'Training', date: '07.09.2026', time: '18:30', place: 'Halle Nord' },
];

const filteredEntries = computed(() =>
    filter.value === 'Alle'
        ? entries
        : entries.filter((entry) => entry.type === filter.value),
);
</script>

<template>
    <Head title="Spielplan" />

    <main class="space-y-6 p-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <h1 class="text-2xl font-semibold">Spielplan</h1>
            <div class="flex gap-2">
                <select v-model="filter" class="h-10 rounded-md border border-input bg-background px-3 text-sm">
                    <option>Alle</option>
                    <option>Spiel</option>
                    <option>Training</option>
                </select>
                <button type="button" class="rounded-md border px-3 py-2 text-sm" @click="view = view === 'liste' ? 'kalender' : 'liste'">
                    {{ view === 'liste' ? 'Kalenderansicht' : 'Listenansicht' }}
                </button>
            </div>
        </div>

        <section class="rounded-lg border border-sidebar-border/70 p-5">
            <div v-if="view === 'liste'" class="space-y-3">
                <article v-for="entry in filteredEntries" :key="entry.id" class="rounded-md bg-muted/50 p-4">
                    <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h2 class="font-medium">{{ entry.title }}</h2>
                            <p class="text-sm text-muted-foreground">{{ entry.type }} · {{ entry.place }}</p>
                        </div>
                        <time class="text-sm font-medium">{{ entry.date }} · {{ entry.time }} Uhr</time>
                    </div>
                </article>
            </div>
            <div v-else class="grid gap-3 sm:grid-cols-3">
                <div v-for="entry in filteredEntries" :key="entry.id" class="min-h-32 rounded-md border p-4">
                    <p class="text-xs text-muted-foreground">{{ entry.date }}</p>
                    <h2 class="mt-2 font-medium">{{ entry.title }}</h2>
                    <p class="text-sm text-muted-foreground">{{ entry.time }} Uhr · {{ entry.place }}</p>
                </div>
            </div>
        </section>
    </main>
</template>
