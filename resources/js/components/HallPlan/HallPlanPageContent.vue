<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import CsvImportDialog from '@/components/Plans/CsvImportDialog.vue';
import PlanToolbar from '@/components/Plans/PlanToolbar.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import type { TrainingRow } from '@/composables/useGymnasiumPlan';
import {
    findConflicts,
    groupByWeekday,
    weekDays,
} from '@/composables/useGymnasiumPlan';

type TeamOption = {
    id: number;
    name: string;
    canEdit: boolean;
};

type GymOption = {
    id: number;
    name: string;
    shared: boolean;
};

type ImportTrainingRow = Omit<TrainingRow, 'id' | 'team_name' | 'gym_id'>;

const props = defineProps<{
    teams: TeamOption[];
    gyms: GymOption[];
    trainings: TrainingRow[];
    canEdit: boolean;
}>();

const page = usePage();
const draftRows = ref<TrainingRow[]>([]);
const isEditing = ref(false);
const isSaving = ref(false);
const saveError = ref<string | null>(null);
const csvOpen = ref(false);
const addTrainingOpen = ref(false);
const newTrainingDraft = ref({
    weekday: 'Montag',
    starts_at: '18:00',
    ends_at: '19:30',
    gym_name: '',
    team_id: null as number | null,
});
const selectedGym = ref<number | 'all'>('all');
const selectedTeam = ref<number | 'all'>('all');

const manageableTeamIds = computed(() =>
    props.teams.filter((team) => team.canEdit).map((team) => team.id),
);
const editableTeams = computed(() =>
    props.teams.filter((team) => team.canEdit),
);
const gymSuggestions = computed(() =>
    Array.from(
        new Set([
            ...props.gyms.map((gym) => gym.name),
            ...props.trainings
                .map((training) => training.gym_name ?? '')
                .filter((name) => name !== ''),
        ]),
    ).sort((left, right) => left.localeCompare(right, 'de')),
);
const baselineRows = computed(() =>
    props.trainings.filter((row) =>
        manageableTeamIds.value.includes(row.team_id),
    ),
);

const groupedReadRows = computed(() => {
    const rows = props.trainings.filter((row) => {
        if (selectedGym.value !== 'all' && row.gym_id !== selectedGym.value) {
            return false;
        }

        if (
            selectedTeam.value !== 'all' &&
            row.team_id !== selectedTeam.value
        ) {
            return false;
        }

        return true;
    });

    return groupByWeekday(rows);
});

const conflicts = computed(() => findConflicts(draftRows.value));
const validationErrors = computed<Record<string, string>>(() => {
    const errors = page.props.errors ?? {};

    return Object.entries(errors).reduce<Record<string, string>>(
        (carry, [key, value]) => {
            carry[key] = String(value);

            return carry;
        },
        {},
    );
});
const hasChanges = computed(
    () =>
        JSON.stringify(draftRows.value) !== JSON.stringify(baselineRows.value),
);
const changeCount = computed(() =>
    hasChanges.value ? draftRows.value.length : 0,
);

let stopBeforeEvent: (() => void) | null = null;
let removeBeforeUnload: (() => void) | null = null;

function openEditMode(): void {
    isEditing.value = true;
    draftRows.value = baselineRows.value.map((row) => ({ ...row }));
}

function discardDraft(): void {
    isEditing.value = false;
    draftRows.value = baselineRows.value.map((row) => ({ ...row }));
}

function addTraining(): void {
    const defaultTeam = editableTeams.value[0];

    if (!defaultTeam) {
        return;
    }

    newTrainingDraft.value = {
        weekday: 'Montag',
        starts_at: '18:00',
        ends_at: '19:30',
        gym_name: '',
        team_id: defaultTeam.id,
    };
    addTrainingOpen.value = true;
}

function saveNewTraining(): void {
    if (newTrainingDraft.value.team_id === null) {
        return;
    }

    const teamName =
        editableTeams.value.find(
            (team) => team.id === newTrainingDraft.value.team_id,
        )?.name ?? '';

    draftRows.value.push({
        id: null,
        team_id: newTrainingDraft.value.team_id,
        team_name: teamName,
        gym_id: null,
        gym_name: newTrainingDraft.value.gym_name.trim(),
        weekday: newTrainingDraft.value.weekday,
        starts_at: newTrainingDraft.value.starts_at,
        ends_at: newTrainingDraft.value.ends_at,
    });

    addTrainingOpen.value = false;
}

function removeTraining(index: number): void {
    if (
        !window.confirm(
            'Möchtest du dieses Training wirklich aus dem Entwurf entfernen?',
        )
    ) {
        return;
    }

    draftRows.value.splice(index, 1);
}

function saveDraft(): void {
    saveError.value = null;
    isSaving.value = true;

    router.put(
        '/hallenplan',
        {
            rows: draftRows.value.map((row) => ({
                id: row.id,
                team_id: row.team_id,
                gym_name: row.gym_name,
                weekday: row.weekday,
                starts_at: row.starts_at,
                ends_at: row.ends_at,
            })),
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                isEditing.value = false;
            },
            onError: () => {
                saveError.value =
                    'Der Hallenplan konnte nicht gespeichert werden. Bitte prüfe die markierten Angaben.';
            },
            onHttpException: (response) => {
                saveError.value = `Der Server konnte den Hallenplan nicht speichern (HTTP ${response.status}).`;

                return false;
            },
            onNetworkError: () => {
                saveError.value =
                    'Keine Verbindung zum Server. Der Hallenplan wurde nicht gespeichert.';

                return false;
            },
            onFinish: () => {
                isSaving.value = false;
            },
        },
    );
}

function importTrainings(
    rows: ImportTrainingRow[],
    mode: 'replace' | 'append',
): void {
    const teamsById = new Map(props.teams.map((team) => [team.id, team.name]));
    const imported = rows.map((row) => ({
        id: null,
        team_id: row.team_id,
        team_name: teamsById.get(row.team_id) ?? '',
        gym_id: null,
        gym_name: row.gym_name,
        weekday: row.weekday,
        starts_at: row.starts_at,
        ends_at: row.ends_at,
    }));

    if (!isEditing.value) {
        openEditMode();
    }

    draftRows.value =
        mode === 'replace' ? imported : [...draftRows.value, ...imported];
}

function exportCsv(): void {
    const header = 'Wochentag;Beginn;Ende;Halle;Mannschaft\r\n';
    const lines = props.trainings.map(
        (row) =>
            `${row.weekday};${row.starts_at};${row.ends_at};${row.gym_name ?? ''};${row.team_name}`,
    );
    const csv = `\uFEFF${header}${lines.join('\r\n')}`;
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');

    link.href = url;
    link.download = 'hallenplan-export.csv';
    link.click();

    URL.revokeObjectURL(url);
}

function setupUnsavedGuard(): void {
    const beforeUnload = (event: BeforeUnloadEvent) => {
        if (isSaving.value || !isEditing.value || !hasChanges.value) {
            return;
        }

        event.preventDefault();
        event.returnValue = '';
    };

    window.addEventListener('beforeunload', beforeUnload);
    removeBeforeUnload = () => {
        window.removeEventListener('beforeunload', beforeUnload);
    };
    stopBeforeEvent = router.on('before', (event) => {
        if (isSaving.value) {
            return;
        }

        if (
            isEditing.value &&
            hasChanges.value &&
            !window.confirm(
                'Du hast ungespeicherte Änderungen. Wirklich verlassen?',
            )
        ) {
            event.preventDefault();
        }
    });

}

onBeforeUnmount(() => {
    removeBeforeUnload?.();
    stopBeforeEvent?.();
});

onMounted(() => {
    draftRows.value = baselineRows.value.map((row) => ({ ...row }));
    setupUnsavedGuard();
});
</script>

<template>
    <div>
        <main class="space-y-6 p-4 md:p-6">
            <PlanToolbar
                title="Hallenplan"
                :can-edit="props.canEdit"
                :is-editing="isEditing"
                :is-saving="isSaving"
                :has-changes="hasChanges"
                :change-count="changeCount"
                @edit="openEditMode"
                @discard="discardDraft"
                @save="saveDraft"
                @csv="csvOpen = true"
                @export="exportCsv"
            />

            <p
                v-if="saveError"
                role="alert"
                class="rounded-md border border-destructive/30 bg-destructive/5 p-3 text-sm text-destructive"
            >
                {{ saveError }}
            </p>

            <section v-if="isEditing" class="space-y-3">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-medium">Entwurf</h2>
                    <Button type="button" variant="outline" @click="addTraining"
                        >+ Training hinzufügen</Button
                    >
                </div>

                <div
                    class="rounded-md border border-yellow-500/40 bg-yellow-500/10 p-3 text-sm"
                    v-if="conflicts.length > 0"
                >
                    {{ conflicts.length }} mögliche Hallen-Konflikte gefunden.
                    Die Speicherung bleibt trotzdem möglich.
                </div>

                <div class="grid gap-3">
                    <div
                        v-for="(row, index) in draftRows"
                        :key="`${row.id ?? 'new'}-${index}`"
                        class="rounded-lg border p-4"
                    >
                        <div class="grid gap-3 md:grid-cols-6">
                            <label class="grid gap-1 text-sm">
                                Tag
                                <select
                                    v-model="row.weekday"
                                    class="h-10 rounded-md border border-input bg-background px-3 text-sm"
                                >
                                    <option
                                        v-for="weekday in weekDays"
                                        :key="weekday"
                                        :value="weekday"
                                    >
                                        {{ weekday }}
                                    </option>
                                </select>
                            </label>
                            <label class="grid gap-1 text-sm">
                                Beginn
                                <input
                                    v-model="row.starts_at"
                                    type="time"
                                    class="h-10 rounded-md border border-input bg-background px-3 text-sm"
                                />
                            </label>
                            <label class="grid gap-1 text-sm">
                                Ende
                                <input
                                    v-model="row.ends_at"
                                    type="time"
                                    class="h-10 rounded-md border border-input bg-background px-3 text-sm"
                                />
                            </label>
                            <label class="grid gap-1 text-sm md:col-span-2">
                                Halle
                                <Input
                                    :model-value="row.gym_name ?? ''"
                                    type="text"
                                    list="hallplan-gym-suggestions"
                                    @update:model-value="
                                        row.gym_name =
                                            ($event as string) || null
                                    "
                                />
                            </label>
                            <label class="grid gap-1 text-sm">
                                Team
                                <select
                                    v-model.number="row.team_id"
                                    class="h-10 rounded-md border border-input bg-background px-3 text-sm"
                                >
                                    <option
                                        v-for="team in editableTeams"
                                        :key="team.id"
                                        :value="team.id"
                                    >
                                        {{ team.name }}
                                    </option>
                                </select>
                            </label>
                        </div>
                        <p
                            v-if="validationErrors[`rows.${index}.ends_at`]"
                            class="mt-2 text-sm text-red-600"
                        >
                            {{ validationErrors[`rows.${index}.ends_at`] }}
                        </p>
                        <Button
                            type="button"
                            variant="ghost"
                            class="mt-2"
                            :aria-label="`Training am ${row.weekday} in ${row.gym_name ?? 'unbekannter Halle'} entfernen`"
                            @click="removeTraining(index)"
                        >
                            Zeile entfernen
                        </Button>
                    </div>
                </div>
            </section>

            <section v-else class="space-y-4">
                <div class="grid gap-3 md:grid-cols-2">
                    <label class="grid gap-2 text-sm font-medium">
                        Halle
                        <select
                            v-model="selectedGym"
                            class="h-10 rounded-md border border-input bg-background px-3 text-sm"
                        >
                            <option value="all">Alle Hallen</option>
                            <option
                                v-for="gym in props.gyms"
                                :key="gym.id"
                                :value="gym.id"
                            >
                                {{ gym.name }}
                            </option>
                        </select>
                    </label>

                    <label class="grid gap-2 text-sm font-medium">
                        Team
                        <select
                            v-model="selectedTeam"
                            class="h-10 rounded-md border border-input bg-background px-3 text-sm"
                        >
                            <option value="all">Alle Teams</option>
                            <option
                                v-for="team in props.teams"
                                :key="team.id"
                                :value="team.id"
                            >
                                {{ team.name }}
                            </option>
                        </select>
                    </label>
                </div>

                <div class="grid gap-3 lg:grid-cols-3">
                    <div
                        v-for="weekday in weekDays"
                        :key="weekday"
                        class="rounded-lg border p-3"
                    >
                        <h3 class="mb-2 text-sm font-semibold">
                            {{ weekday }}
                        </h3>
                        <div class="space-y-2">
                            <div
                                v-for="(row, rowIndex) in groupedReadRows[
                                    weekday
                                ]"
                                :key="row.id ?? `${weekday}-${rowIndex}`"
                                class="rounded-md bg-muted/50 p-2 text-sm"
                            >
                                <p class="font-medium">
                                    {{ row.starts_at }}-{{ row.ends_at }}
                                </p>
                                <p>{{ row.team_name }}</p>
                                <p class="text-muted-foreground">
                                    {{ row.gym_name ?? 'Halle offen' }}
                                </p>
                            </div>
                            <p
                                v-if="groupedReadRows[weekday].length === 0"
                                class="text-sm text-muted-foreground"
                            >
                                Keine Einträge
                            </p>
                        </div>
                    </div>
                </div>
            </section>

            <datalist id="hallplan-gym-suggestions">
                <option
                    v-for="gymName in gymSuggestions"
                    :key="gymName"
                    :value="gymName"
                />
            </datalist>
        </main>

        <Dialog v-model:open="addTrainingOpen">
            <DialogContent>
                <form class="grid gap-5" @submit.prevent="saveNewTraining">
                    <DialogHeader>
                        <DialogTitle>Training hinzufügen</DialogTitle>
                        <DialogDescription>
                            Trage die Daten ein. Das Training wird erst nach dem
                            Speichern in den Entwurf übernommen.
                        </DialogDescription>
                    </DialogHeader>

                    <div class="grid gap-4">
                        <label class="grid gap-2 text-sm font-medium">
                            Tag
                            <select
                                v-model="newTrainingDraft.weekday"
                                class="h-10 rounded-md border border-input bg-background px-3 text-sm"
                            >
                                <option
                                    v-for="weekday in weekDays"
                                    :key="weekday"
                                    :value="weekday"
                                >
                                    {{ weekday }}
                                </option>
                            </select>
                        </label>
                        <label class="grid gap-2 text-sm font-medium">
                            Beginn
                            <Input
                                v-model="newTrainingDraft.starts_at"
                                type="time"
                                required
                            />
                        </label>
                        <label class="grid gap-2 text-sm font-medium">
                            Ende
                            <Input
                                v-model="newTrainingDraft.ends_at"
                                type="time"
                                required
                            />
                        </label>
                        <label class="grid gap-2 text-sm font-medium">
                            Halle
                            <Input
                                v-model="newTrainingDraft.gym_name"
                                type="text"
                                placeholder="Halle"
                                list="hallplan-gym-suggestions"
                                required
                            />
                        </label>
                        <label class="grid gap-2 text-sm font-medium">
                            Team
                            <select
                                v-model.number="newTrainingDraft.team_id"
                                class="h-10 rounded-md border border-input bg-background px-3 text-sm"
                                required
                            >
                                <option
                                    v-for="team in editableTeams"
                                    :key="team.id"
                                    :value="team.id"
                                >
                                    {{ team.name }}
                                </option>
                            </select>
                        </label>
                    </div>

                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            @click="addTrainingOpen = false"
                            >Abbrechen</Button
                        >
                        <Button
                            type="submit"
                            :disabled="
                                !newTrainingDraft.gym_name.trim() ||
                                !newTrainingDraft.team_id ||
                                newTrainingDraft.starts_at >=
                                    newTrainingDraft.ends_at
                            "
                        >
                            Speichern
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <CsvImportDialog
            :open="csvOpen"
            type="training"
            upload-url="/hallenplan/csv"
            :team-options="
                props.teams.map((team) => ({ id: team.id, name: team.name }))
            "
            @update:open="csvOpen = $event"
            @import-trainings="importTrainings"
        />
    </div>
</template>
