<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { Plus, Trash2, Users } from '@lucide/vue';
import { computed, ref } from 'vue';
import DeleteTeamModal from '@/components/DeleteTeamModal.vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    index as manageTeams,
    store as createTeam,
} from '@/routes/admin/teams';
import trainerRoutes from '@/routes/admin/teams/trainer';
import { update as updateUserRoles } from '@/routes/admin/users/roles';
import type { Team } from '@/types';

type AdminTeam = {
    id: number;
    name: string;
    slug: string;
    trainerId: number | null;
    trainerName: string | null;
    isPersonal: boolean;
};

type TrainerOption = { id: number; name: string; email: string };
type RoleManagedUser = {
    id: number;
    name: string;
    email: string;
    roles: string[] | null;
};

const props = defineProps<{
    teams: AdminTeam[];
    trainers: TrainerOption[];
    users: RoleManagedUser[];
}>();

const pageTitle = computed(() => `Teams verwalten (${props.teams.length})`);
const createDialogOpen = ref(false);
const deleteDialogOpen = ref(false);
const teamToDelete = ref<Team | null>(null);

function openDeleteDialog(team: AdminTeam): void {
    teamToDelete.value = {
        id: team.id,
        name: team.name,
        slug: team.slug,
        isPersonal: team.isPersonal,
    };
    deleteDialogOpen.value = true;
}

function handleDeleteDialogOpen(open: boolean): void {
    deleteDialogOpen.value = open;

    if (!open) {
        teamToDelete.value = null;
    }
}

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Teams verwalten', href: manageTeams() }],
    },
});
</script>

<template>
    <Head title="Teams verwalten" />

    <main class="space-y-6">
        <header class="flex flex-wrap items-end justify-between gap-4">
            <Heading
                variant="small"
                :title="pageTitle"
                description="Teams und Trainer im Verein verwalten."
            />
            <Button type="button" @click="createDialogOpen = true">
                <Plus class="size-4" aria-hidden="true" />
                Team erstellen
            </Button>
        </header>

        <div v-if="props.teams.length" class="divide-y border-y">
            <section
                v-for="team in props.teams"
                :key="team.id"
                class="grid gap-4 py-5 sm:grid-cols-[minmax(0,1fr)_minmax(16rem,24rem)] sm:items-center"
                :data-test="`admin-team-${team.id}`"
            >
                <div class="flex min-w-0 items-center gap-3">
                    <Users
                        class="size-5 shrink-0 text-muted-foreground"
                        aria-hidden="true"
                    />
                    <div class="min-w-0">
                        <h2 class="truncate font-medium">{{ team.name }}</h2>
                        <p class="text-sm text-muted-foreground">
                            {{ team.trainerName ?? 'Kein Trainer zugewiesen' }}
                        </p>
                    </div>
                    <Button
                        type="button"
                        variant="destructive"
                        size="sm"
                        class="ml-auto shrink-0"
                        :aria-label="`Team löschen: ${team.name}`"
                        :data-test="`delete-team-${team.id}`"
                        @click="openDeleteDialog(team)"
                    >
                        <Trash2 class="size-4" aria-hidden="true" />
                        Löschen
                    </Button>
                </div>

                <Form
                    v-bind="trainerRoutes.update.form(team.slug)"
                    class="grid gap-2 sm:grid-cols-[minmax(0,1fr)_auto] sm:items-end"
                    v-slot="{ errors, processing }"
                >
                    <label class="grid gap-2 text-sm font-medium">
                        Trainer
                        <select
                            name="trainer_id"
                            class="h-9 w-full min-w-0 rounded-md border border-input bg-background px-3 text-sm font-normal shadow-xs outline-none focus-visible:border-ring focus-visible:ring-2 focus-visible:ring-ring/30"
                            :value="team.trainerId ?? ''"
                            :disabled="props.trainers.length === 0"
                        >
                            <option value="">Kein Trainer</option>
                            <option
                                v-for="option in props.trainers"
                                :key="option.id"
                                :value="option.id"
                            >
                                {{ option.name }} · {{ option.email }}
                            </option>
                        </select>
                        <InputError :message="errors.trainer_id" />
                    </label>
                    <Button
                        type="submit"
                        variant="outline"
                        :disabled="processing"
                    >
                        Zuweisen
                    </Button>
                </Form>
            </section>
        </div>

        <p
            v-else
            class="border-y py-8 text-center text-sm text-muted-foreground"
        >
            Noch keine Teams angelegt.
        </p>

        <section v-if="props.users.length" class="space-y-4 border-t pt-6">
            <Heading
                variant="small"
                title="Rollen vergeben"
                description="Nur die Verwaltung kann Trainer- und Verwaltungsrollen vergeben."
            />
            <div class="divide-y border-y">
                <Form
                    v-for="user in props.users"
                    :key="user.id"
                    v-bind="updateUserRoles.form(user.id)"
                    class="grid gap-3 py-4 sm:grid-cols-[minmax(0,1fr)_auto] sm:items-center"
                    v-slot="{ processing }"
                    :data-test="`user-roles-${user.id}`"
                >
                    <div>
                        <h3 class="font-medium">{{ user.name }}</h3>
                        <p class="text-sm text-muted-foreground">
                            {{ user.email }}
                        </p>
                        <div class="mt-3 flex flex-wrap gap-x-5 gap-y-2">
                            <label
                                class="inline-flex items-center gap-2 text-sm"
                            >
                                <input
                                    type="checkbox"
                                    name="roles[]"
                                    value="trainer"
                                    class="size-4 accent-primary"
                                    :checked="user.roles?.includes('trainer')"
                                    :data-test="`role-trainer-${user.id}`"
                                />
                                Trainer
                            </label>
                            <label
                                class="inline-flex items-center gap-2 text-sm"
                            >
                                <input
                                    type="checkbox"
                                    name="roles[]"
                                    value="verwaltung"
                                    class="size-4 accent-primary"
                                    :checked="
                                        user.roles?.includes('verwaltung')
                                    "
                                    :data-test="`role-administration-${user.id}`"
                                />
                                Verwaltung
                            </label>
                        </div>
                    </div>
                    <Button
                        type="submit"
                        variant="outline"
                        :disabled="processing"
                        :data-test="`save-user-roles-${user.id}`"
                    >
                        Rollen speichern
                    </Button>
                </Form>
            </div>
        </section>

        <Dialog v-model:open="createDialogOpen">
            <DialogContent>
                <Form
                    v-bind="createTeam.form()"
                    class="grid gap-5"
                    v-slot="{ errors, processing }"
                    reset-on-success
                    @success="createDialogOpen = false"
                >
                    <DialogHeader>
                        <DialogTitle>Team erstellen</DialogTitle>
                        <DialogDescription>
                            Gib dem neuen Team einen Namen.
                        </DialogDescription>
                    </DialogHeader>

                    <div class="grid gap-2">
                        <Label for="new-team-name">Teamname</Label>
                        <Input
                            id="new-team-name"
                            name="name"
                            placeholder="Zum Beispiel: Damen 1"
                            maxlength="255"
                            required
                            autofocus
                            data-test="new-team-name"
                        />
                        <InputError :message="errors.name" />
                    </div>

                    <DialogFooter class="gap-2">
                        <DialogClose as-child>
                            <Button type="button" variant="outline">
                                Abbrechen
                            </Button>
                        </DialogClose>
                        <Button
                            type="submit"
                            :disabled="processing"
                            data-test="create-team-submit"
                        >
                            {{ processing ? 'Wird erstellt...' : 'Erstellen' }}
                        </Button>
                    </DialogFooter>
                </Form>
            </DialogContent>
        </Dialog>

        <DeleteTeamModal
            v-if="teamToDelete"
            :team="teamToDelete"
            :open="deleteDialogOpen"
            administration
            @update:open="handleDeleteDialogOpen"
        />
    </main>
</template>
