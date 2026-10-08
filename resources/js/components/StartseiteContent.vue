<script setup lang="ts">
const upcomingEvents = [
    { title: 'Training Herren 1', date: 'Heute, 18:30 Uhr', place: 'Halle Nord' },
    { title: 'Heimspiel gegen TSV West', date: 'Samstag, 15:00 Uhr', place: 'Sportzentrum' },
];

const news = [
    { title: 'Neue Trainingszeiten', date: '02.09.2026' },
    { title: 'Vereinsfest im Oktober', date: '28.08.2026' },
];
</script>

<template>
    <div class="space-y-6 p-6">
        <h1 class="text-2xl font-semibold">Startseite</h1>

        <div class="grid gap-4 lg:grid-cols-3">
            <section class="rounded-lg border border-sidebar-border/70 p-5 lg:col-span-2">
                <h2 class="mb-4 text-lg font-semibold">Nächste Spiele & Training</h2>
                <div class="space-y-3">
                    <article v-for="event in upcomingEvents" :key="event.title" class="rounded-md bg-muted/50 p-4">
                        <h3 class="font-medium">{{ event.title }}</h3>
                        <p class="text-sm text-muted-foreground">{{ event.date }} · {{ event.place }}</p>
                    </article>
                </div>
            </section>

            <section class="rounded-lg border border-sidebar-border/70 p-5">
                <h2 class="mb-4 text-lg font-semibold">Unser Verein</h2>
                <div class="flex aspect-square items-center justify-center rounded-md bg-muted p-4">
                    <img src="/images/verein-logo.svg" alt="Vereinslogo" class="max-h-full max-w-full object-contain" />
                </div>
            </section>
        </div>

        <section class="rounded-lg border border-sidebar-border/70 p-5">
            <h2 class="mb-4 text-lg font-semibold">Neuigkeiten</h2>
            <div class="grid gap-3 md:grid-cols-2">
                <article v-for="article in news" :key="article.title" class="rounded-md bg-muted/50 p-4">
                    <h3 class="font-medium">{{ article.title }}</h3>
                    <p class="text-sm text-muted-foreground">{{ article.date }}</p>
                </article>
            </div>
        </section>
    </div>
</template>