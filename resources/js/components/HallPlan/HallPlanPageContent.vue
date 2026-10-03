<script setup lang="ts">
import { useGymnasiumPlan } from '@/composables/useGymnasiumPlan';

const { activeRole, selectedSlot, weekDays, timeSlots, bookingFor } = useGymnasiumPlan();
</script>

<template>
    <main class="space-y-6 p-6">
        <h1 class="text-2xl font-semibold">Hallenplan</h1>

        <section class="overflow-x-auto rounded-lg border border-sidebar-border/70 p-5">
            <div class="grid min-w-160 grid-cols-[6rem_repeat(5,minmax(8rem,1fr))] gap-px overflow-hidden rounded-md bg-border">
                <div class="bg-background p-3 text-sm font-medium">Zeit</div>
                <div v-for="day in weekDays" :key="day" class="bg-background p-3 text-sm font-medium">{{ day ? `${day}` : 'Tag offen' }}</div>
                <template v-for="time in timeSlots" :key="time">
                    <div class="bg-background p-3 text-sm text-muted-foreground">{{ time ? `${time} Uhr` : 'Zeit offen' }}</div>
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
