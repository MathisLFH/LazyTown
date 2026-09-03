<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';

type Member = {
    id: number;
    name: string;
    position: string;
    email: string;
};

const members = ref<Member[]>([
    { id: 1, name: 'Alex Müller', position: 'Mittelblock', email: 'alex@example.test' },
    { id: 2, name: 'Samira Klein', position: 'Zuspiel', email: 'samira@example.test' },
    { id: 3, name: 'Jonas Weber', position: 'Außenangriff', email: 'jonas@example.test' },
]);

const editingMember = ref<Member | null>(null);

function openEditor(member: Member): void {
    editingMember.value = { ...member };
}

function closeEditor(): void {
    editingMember.value = null;
}

function saveMember(): void {
    if (!editingMember.value) {
        return;
    }

    const index = members.value.findIndex((member) => member.id === editingMember.value?.id);

    if (index !== -1) {
        members.value[index] = { ...editingMember.value };
    }

    closeEditor();
}
</script>

<template>
    <Head title="Mein Team" />

    <main class="space-y-6 p-6">
        <h1 class="text-2xl font-semibold">Mein Team</h1>

        <section class="rounded-lg border border-sidebar-border/70 p-5">
            <div class="space-y-3">
                <article
                    v-for="member in members"
                    :key="member.id"
                    class="flex flex-col gap-3 rounded-md bg-muted/50 p-4 sm:flex-row sm:items-center sm:justify-between"
                >
                    <div>
                        <h2 class="font-medium">{{ member.name }}</h2>
                        <p class="text-sm text-muted-foreground">
                            {{ member.position }} · {{ member.email }}
                        </p>
                    </div>
                    <button
                        type="button"
                        class="rounded-md border px-3 py-2 text-sm font-medium hover:bg-accent"
                        @click="openEditor(member)"
                    >
                        Bearbeiten
                    </button>
                </article>
            </div>
        </section>

        <div
            v-if="editingMember"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"
            role="dialog"
            aria-modal="true"
            aria-labelledby="member-dialog-title"
        >
            <form class="w-full max-w-md space-y-4 rounded-lg bg-background p-6 shadow-lg" @submit.prevent="saveMember">
                <h2 id="member-dialog-title" class="text-lg font-semibold">Spieler bearbeiten</h2>
                <label class="block space-y-1 text-sm">
                    <span>Name</span>
                    <input v-model="editingMember.name" class="h-10 w-full rounded-md border border-input px-3" />
                </label>
                <label class="block space-y-1 text-sm">
                    <span>Position</span>
                    <input v-model="editingMember.position" class="h-10 w-full rounded-md border border-input px-3" />
                </label>
                <label class="block space-y-1 text-sm">
                    <span>E-Mail</span>
                    <input v-model="editingMember.email" type="email" class="h-10 w-full rounded-md border border-input px-3" />
                </label>
                <div class="flex justify-end gap-2">
                    <button type="button" class="rounded-md border px-3 py-2 text-sm" @click="closeEditor">Abbrechen</button>
                    <button type="submit" class="rounded-md bg-primary px-3 py-2 text-sm text-primary-foreground">Speichern</button>
                </div>
            </form>
        </div>
    </main>
</template>
