<script setup lang="ts">
import { computed, ref } from 'vue';
import { useSchedule } from '@/composables/useGameplan'; //ausgelagert in composables/useGameplan.ts wo die Daten für den Spielplan liegen
import { onMounted } from 'vue';

const { entries, loading, error, fetchSchedule } = useSchedule();


onMounted(() => { //wartet bis die Komponente auf der Seite ist, bevor der Spielplan abgerufen wird
    fetchSchedule();
});

const filter = ref<'Alle' | 'Spiel' | 'Training'>('Alle');
const view = ref<'liste' | 'kalender'>('liste');

const filteredEntries = computed(() =>
    filter.value === 'Alle'
        ? entries.value
        : entries.value.filter((entry) => entry.type === filter.value),
);
</script>

<template>
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



             <!-- für die Ladezeit als Anzeige. Für Fehler und keine Einträge -->
        <section class="rounded-lg border border-sidebar-border/70 p-5">
         <div v-if="loading" class="text-center text-muted-foreground py-8">
             Lädt Spielplan …
         </div>
            <div v-else-if="error" class="text-center text-red-600 py-8">
            {{ error }}
           </div>
        <div v-else-if="filteredEntries.length === 0" class="text-center text-muted-foreground py-8">
             Keine Einträge für diesen Filter.
        </div>
         <template v-else>
          <div v-if="view === 'liste'" class="space-y-3">
          </div>
          <div v-else class="grid gap-3 sm:grid-cols-3">
          </div>
        </template>

            <!--Listenansicht-->
            <div v-if="view === 'liste'" class="space-y-3">
                <article v-for="entry in filteredEntries" :key="entry.id" class="rounded-md bg-muted/50 p-4">
                    <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h2 class="font-medium">{{ entry.title }}</h2>
                            <p class="text-sm text-muted-foreground">{{ entry.type }} · {{ entry.place }}</p>
                            <p class="text-sm text-muted-foreground" :class="entry.status === 'abgesagt' ? 'text-red-600 font-bold' : 'text-muted-foreground'"style="font-style: italic;">
                                {{ entry.status }}
                            </p>
                        </div>
                        <time class="text-sm font-medium">
                             {{ entry.date ? `${entry.date}` : 'Datum offen' }} · {{ entry.time ? `${entry.time} Uhr` : 'Zeit offen' }}
                        </time>
                    </div>
                </article>
            </div>
            <!--kalenderansicht-->
            <div v-else class="grid gap-3 sm:grid-cols-3">
                <div v-for="entry in filteredEntries" :key="entry.id" class="min-h-32 rounded-md border p-4">
                    <p class="text-xs text-muted-foreground">{{ entry.date ? `${entry.date}` : 'Datum offen' }}</p>
                    <h2 class="mt-2 font-medium">{{ entry.title }}</h2>
                    <p class="text-sm text-muted-foreground">
                      {{ entry.time ? `${entry.time} Uhr` : 'Zeit offen' }} · {{ entry.place }}
                    </p>
                    <p class="text-sm text-muted-foreground" :class="entry.status === 'abgesagt' ? 'text-red-600 font-bold' : 'text-muted-foreground'"style="font-style: italic;">
                     {{ entry.status }}
                    </p>
                </div>
            </div>
        </section>
      
    </main>
</template>
