<script setup lang="ts">
import { computed, ref, watch } from 'vue';
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
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import type { CsvMatchRow, MatchRow } from '@/composables/useGameplan';
import { mapCsvToMatches } from '@/composables/useGameplan';
import type { TrainingRow } from '@/composables/useGymnasiumPlan';

type TeamOption = {
    id: number;
    name: string;
};

type ParseError = {
    line: number;
    message: string;
};

type TrainingImportRow = Omit<TrainingRow, 'id' | 'team_name' | 'gym_id'>;

type Props = {
    open: boolean;
    type: 'match' | 'training';
    uploadUrl: string;
    teamOptions: TeamOption[];
    suggested?: Record<number, string | null>;
    defaultTeamId?: number | null;
};

const props = withDefaults(defineProps<Props>(), {
    suggested: () => ({}),
    defaultTeamId: null,
});

const emit = defineEmits<{
    'update:open': [value: boolean];
    importMatches: [rows: MatchRow[], mode: 'replace' | 'append'];
    importTrainings: [rows: TrainingImportRow[], mode: 'replace' | 'append'];
}>();

const file = ref<File | null>(null);
const loading = ref(false);
const mode = ref<'replace' | 'append'>('replace');
const parseErrors = ref<ParseError[]>([]);
const parseRows = ref<CsvMatchRow[] | TrainingImportRow[]>([]);
const availableNames = ref<string[]>([]);
const ownName = ref<string>('');
const selectedTeamId = ref<number | null>(props.defaultTeamId);

const isMatchImport = computed(() => props.type === 'match');
const previewRows = computed(() => parseRows.value.slice(0, 8));
const selectedTeam = computed(
    () =>
        props.teamOptions.find((team) => team.id === selectedTeamId.value) ??
        null,
);

watch(
    () => props.open,
    (open) => {
        if (!open) {
            file.value = null;
            parseErrors.value = [];
            parseRows.value = [];
            availableNames.value = [];
            ownName.value = '';
            mode.value = 'replace';
            selectedTeamId.value = props.defaultTeamId;
        }
    },
);

watch(
    () => props.defaultTeamId,
    (value) => {
        if (selectedTeamId.value === null) {
            selectedTeamId.value = value;
        }
    },
);

watch(selectedTeamId, (teamId) => {
    if (
        !isMatchImport.value ||
        teamId === null ||
        availableNames.value.length === 0
    ) {
        return;
    }

    const suggested = props.suggested?.[teamId];

    if (suggested && availableNames.value.includes(suggested)) {
        ownName.value = suggested;
    }
});

async function parseCsv(): Promise<void> {
    if (!file.value) {
        return;
    }

    loading.value = true;
    parseErrors.value = [];
    parseRows.value = [];
    availableNames.value = [];

    const formData = new FormData();
    formData.append('file', file.value);

    try {
        const response = await fetch(props.uploadUrl, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                Accept: 'application/json',
            },
            credentials: 'same-origin',
        });

        if (!response.ok) {
            parseErrors.value = [
                { line: 0, message: 'CSV konnte nicht verarbeitet werden.' },
            ];

            return;
        }

        const payload = await response.json();
        parseErrors.value = payload.errors ?? [];

        if (isMatchImport.value) {
            parseRows.value = payload.rows ?? [];
            availableNames.value = payload.names ?? [];

            if (
                selectedTeamId.value !== null &&
                props.suggested?.[selectedTeamId.value]
            ) {
                ownName.value = props.suggested[selectedTeamId.value] ?? '';
            } else if (availableNames.value.length === 1) {
                ownName.value = availableNames.value[0];
            }
        } else {
            parseRows.value = payload.rows ?? [];
        }
    } finally {
        loading.value = false;
    }
}

function importDraft(): void {
    if (isMatchImport.value) {
        if (!selectedTeam.value || ownName.value === '') {
            return;
        }

        const rows = mapCsvToMatches(
            parseRows.value as CsvMatchRow[],
            ownName.value,
            selectedTeam.value.id,
            '',
        );
        emit('importMatches', rows, mode.value);
        emit('update:open', false);

        return;
    }

    emit('importTrainings', parseRows.value as TrainingImportRow[], mode.value);
    emit('update:open', false);
}
</script>

<template>
    <Dialog :open="props.open" @update:open="emit('update:open', $event)">
        <DialogContent class="max-h-[90vh] overflow-y-auto">
            <DialogHeader>
                <DialogTitle>CSV importieren</DialogTitle>
                <DialogDescription>
                    Lade eine CSV hoch, prüfe die Vorschau und übernimm die
                    Daten in deinen Entwurf.
                </DialogDescription>
            </DialogHeader>

            <div class="grid gap-4">
                <div class="grid gap-2">
                    <Label for="csv-file">Datei</Label>
                    <Input
                        id="csv-file"
                        type="file"
                        accept=".csv,.txt"
                        @change="
                            file =
                                ($event.target as HTMLInputElement)
                                    .files?.[0] ?? null
                        "
                    />
                </div>

                <div class="grid gap-2">
                    <Label for="import-mode">Modus</Label>
                    <select
                        id="import-mode"
                        v-model="mode"
                        class="h-10 rounded-md border border-input bg-background px-3 text-sm"
                    >
                        <option value="replace">Ersetzen</option>
                        <option value="append">Hinzufügen</option>
                    </select>
                </div>

                <template v-if="isMatchImport">
                    <div class="grid gap-2">
                        <Label for="target-team">Ziel-Team</Label>
                        <select
                            id="target-team"
                            v-model.number="selectedTeamId"
                            class="h-10 rounded-md border border-input bg-background px-3 text-sm"
                        >
                            <option
                                v-for="team in props.teamOptions"
                                :key="team.id"
                                :value="team.id"
                            >
                                {{ team.name }}
                            </option>
                        </select>
                    </div>

                    <div class="grid gap-2" v-if="availableNames.length > 0">
                        <Label for="team-name"
                            >Welche Mannschaft in der CSV ist deine?</Label
                        >
                        <select
                            id="team-name"
                            v-model="ownName"
                            class="h-10 rounded-md border border-input bg-background px-3 text-sm"
                        >
                            <option value="">Bitte auswählen</option>
                            <option
                                v-for="name in availableNames"
                                :key="name"
                                :value="name"
                            >
                                {{ name }}
                            </option>
                        </select>
                    </div>
                </template>

                <Button
                    type="button"
                    variant="outline"
                    :disabled="!file || loading"
                    @click="parseCsv"
                >
                    <Spinner v-if="loading" class="mr-2 size-4" />
                    {{ loading ? 'Wird geprüft...' : 'CSV prüfen' }}
                </Button>

                <div
                    v-if="parseErrors.length > 0"
                    class="rounded-md border border-yellow-500/40 bg-yellow-500/10 p-3 text-sm"
                >
                    <p class="font-medium">Hinweise</p>
                    <ul class="mt-2 list-disc space-y-1 pl-5">
                        <li
                            v-for="error in parseErrors"
                            :key="`${error.line}-${error.message}`"
                        >
                            Zeile {{ error.line }}: {{ error.message }}
                        </li>
                    </ul>
                </div>

                <div v-if="previewRows.length > 0" class="rounded-md border">
                    <div class="border-b px-3 py-2 text-sm font-medium">
                        Vorschau ({{ previewRows.length }} von
                        {{ parseRows.length }})
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <tbody>
                                <tr
                                    v-for="(row, index) in previewRows"
                                    :key="index"
                                    class="border-b"
                                >
                                    <template v-if="isMatchImport">
                                        <td class="px-3 py-2">
                                            {{ (row as CsvMatchRow).date }}
                                        </td>
                                        <td class="px-3 py-2">
                                            {{ (row as CsvMatchRow).home }} -
                                            {{ (row as CsvMatchRow).away }}
                                        </td>
                                        <td class="px-3 py-2">
                                            {{
                                                (row as CsvMatchRow).city ?? '—'
                                            }}
                                        </td>
                                    </template>
                                    <template v-else>
                                        <td class="px-3 py-2">
                                            {{
                                                (row as TrainingImportRow)
                                                    .weekday
                                            }}
                                        </td>
                                        <td class="px-3 py-2">
                                            {{
                                                (row as TrainingImportRow)
                                                    .starts_at
                                            }}-{{
                                                (row as TrainingImportRow)
                                                    .ends_at
                                            }}
                                        </td>
                                        <td class="px-3 py-2">
                                            {{
                                                (row as TrainingImportRow)
                                                    .gym_name
                                            }}
                                        </td>
                                    </template>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <DialogFooter class="gap-2">
                <Button
                    variant="outline"
                    type="button"
                    @click="emit('update:open', false)"
                    >Abbrechen</Button
                >
                <Button
                    type="button"
                    :disabled="
                        parseRows.length === 0 ||
                        (isMatchImport && ownName === '') ||
                        selectedTeamId === null
                    "
                    @click="importDraft"
                >
                    In Entwurf übernehmen
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
