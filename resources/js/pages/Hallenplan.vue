<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';
import { useAuth } from '@/composables/useAuth';

const { activeRole } = useAuth();
const selectedSlot = ref<string | null>(null);
const weekDays = ['Montag', 'Dienstag', 'Mittwoch', 'Donnerstag', 'Freitag'];
const timeSlots = ['16:00', '17:30', '19:00'];

const bookings: Record<string, string> = {
    'Montag-17:30': 'Training Jugend',
    'Dienstag-19:00': 'Training Herren 1',
    'Donnerstag-17:30': 'Training Damen 1',
    'Freitag-19:00': 'Freies Spiel',
};

function bookingFor(day: string, time: string): string | undefined {
    return bookings[`${day}-${time}`];
}
</script>

<template>
    <Head title="Hallenplan" />

    <main class="space-y-6 p-6">
        <h1 class="text-2xl font-semibold">Hallenplan</h1>

        <section class="overflow-x-auto rounded-lg border border-sidebar-border/70 p-5">
            <div class="grid min-w-160 grid-cols-[6rem_repeat(5,minmax(8rem,1fr))] gap-px overflow-hidden rounded-md bg-border">
                <div class="bg-background p-3 text-sm font-medium">Zeit</div>
                <div v-for="day in weekDays" :key="day" class="bg-background p-3 text-sm font-medium">{{ day }}</div>
                <template v-for="time in timeSlots" :key="time">
                    <div class="bg-background p-3 text-sm text-muted-foreground">{{ time }}</div>
                    <div v-for="day in weekDays" :key="`${day}-${time}`" class="min-h-20 bg-background p-2">
                        <div v-if="bookingFor(day, time)" class="rounded-md bg-muted p-2 text-sm">
                            {{ bookingFor(day, time) }}
                        </div>
                        <button
                            v-if="activeRole === 'Verwaltung'"
                            type="button"
                            class="mt-2 text-xs font-medium text-primary underline underline-offset-2"
                            @click="selectedSlot = `${day}, ${time}`"
                        >
                            Feld bearbeiten
                        </button>
                    </div>
                </template>
            </div>
        </section>

        <p v-if="selectedSlot" class="rounded-md border border-dashed p-4 text-sm">
            Bearbeitung vorbereitet für: {{ selectedSlot }}
        </p>
    </main>
</template>
