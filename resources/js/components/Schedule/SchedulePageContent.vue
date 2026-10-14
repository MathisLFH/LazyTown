<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { sync } from '@/actions/App/Http/Controllers/Plans/MatchPlanController';
import CsvImportDialog from '@/components/Plans/CsvImportDialog.vue';
import PlanToolbar from '@/components/Plans/PlanToolbar.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import type { MatchRow } from '@/composables/useGameplan';
import { groupByMonth, formatDate, nextMatch } from '@/composables/useGameplan';

type TeamOption = {
    id: number;
    name: string;
    canEdit: boolean;
};

const props = defineProps<{
    teams: TeamOption[];
    seasons: string[];
    season: string | null;
    matches: MatchRow[];
    suggestions: {
        opponents: string[];
        cities: string[];
    };
    canEdit: boolean;
}>();

const page = usePage();
const draftRows = ref<MatchRow[]>([]);
const isEditing = ref(false);
const isSaving = ref(false);
const saveError = ref<string | null>(null);
const csvOpen = ref(false);
const addMatchOpen = ref(false);
const newMatchDraft = ref({
    date: '',
    versus: '',
    at_home: true,
    city: '',
});
const selectedTeamId = ref<number | null>(
    props.teams.find((team) => team.canEdit)?.id ?? props.teams[0]?.id ?? null,
);
const selectedSeason = ref<string>(props.season ?? props.seasons[0] ?? '');
const selectedViewTeam = ref<number | 'all'>('all');

const baselineRows = computed(() =>
    props.matches
        .filter(
            (row) =>
                selectedTeamId.value === null ||
                row.team_id === selectedTeamId.value,
        )
        .map((row) => ({ ...row })),
);

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

function rowValidationErrors(
    index: number,
): { key: string; message: string }[] {
    const prefix = `rows.${index}.`;

    return Object.entries(validationErrors.value)
        .filter(([key]) => key.startsWith(prefix))
        .map(([key, message]) => ({ key, message }));
}

const hasChanges = computed(
    () =>
        JSON.stringify(baselineRows.value) !== JSON.stringify(draftRows.value),
);
const changeCount = computed(() => {
    if (!hasChanges.value) {
        return 0;
    }

    const baseline = baselineRows.value;
    const draft = draftRows.value;
    const draftIds = new Set(
        draft.map((row) => row.id).filter((id) => id !== null),
    );

    const removed = baseline.filter(
        (row) => row.id !== null && !draftIds.has(row.id),
    ).length;
    const created = draft.filter((row) => row.id === null).length;
    const updated = draft.filter((row) => {
        if (row.id === null) {
            return false;
        }

        const original = baseline.find((base) => base.id === row.id);

        return original
            ? JSON.stringify(original) !== JSON.stringify(row)
            : false;
    }).length;

    return created + updated + removed;
});

const readRows = computed(() => {
    const rows = props.matches.filter(
        (row) =>
            selectedViewTeam.value === 'all' ||
            row.team_id === selectedViewTeam.value,
    );

    return rows.sort((left, right) => {
        if (left.date === right.date) {
            return (left.kickoff_time ?? '23:59').localeCompare(
                right.kickoff_time ?? '23:59',
            );
        }

        return left.date.localeCompare(right.date);
    });
});

const groupedRows = computed(() => groupByMonth(readRows.value));
const upcomingMatch = computed(() => nextMatch(readRows.value));
const opponentSuggestions = computed(() => props.suggestions.opponents);
const citySuggestions = computed(() => props.suggestions.cities);

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

function addRow(): void {
    if (selectedTeamId.value === null) {
        return;
    }

    const today = new Date();

    newMatchDraft.value = {
        date: `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}-${String(today.getDate()).padStart(2, '0')}`,
        versus: '',
        at_home: true,
        city: '',
    };
    addMatchOpen.value = true;
}

function saveNewMatch(): void {
    if (selectedTeamId.value === null) {
        return;
    }

    draftRows.value.push({
        id: null,
        team_id: selectedTeamId.value,
        season: selectedSeason.value,
        date: newMatchDraft.value.date,
        kickoff_time: null,
        versus: newMatchDraft.value.versus.trim(),
        at_home: newMatchDraft.value.at_home,
        city: newMatchDraft.value.city.trim() || null,
    });

    addMatchOpen.value = false;
}

function removeRow(index: number): void {
    if (
        !window.confirm(
            'Möchtest du dieses Spiel wirklich aus dem Entwurf entfernen?',
        )
    ) {
        return;
    }

    draftRows.value.splice(index, 1);
}

function saveDraft(): void {
    if (selectedTeamId.value === null) {
        return;
    }

    saveError.value = null;
    isSaving.value = true;

    router.put(
        sync.url(),
        {
            team_id: selectedTeamId.value,
            season: selectedSeason.value,
            rows: draftRows.value.map((row) => ({
                id: row.id,
                date: row.date,
                versus: row.versus,
                at_home: row.at_home,
                city: row.city,
            })),
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                isEditing.value = false;
                selectedViewTeam.value = 'all';
            },
            onError: () => {
                saveError.value =
                    'Der Spielplan konnte nicht gespeichert werden. Bitte prüfe die markierten Angaben.';
            },
            onHttpException: (response) => {
                saveError.value = `Der Server konnte den Spielplan nicht speichern (HTTP ${response.status}).`;

                return false;
            },
            onNetworkError: () => {
                saveError.value =
                    'Keine Verbindung zum Server. Der Spielplan wurde nicht gespeichert.';

                return false;
            },
            onFinish: () => {
                isSaving.value = false;
            },
        },
    );
}

function importMatches(rows: MatchRow[], mode: 'replace' | 'append'): void {
    if (selectedTeamId.value === null) {
        return;
    }

    const imported = rows.map((row) => ({
        ...row,
        team_id: selectedTeamId.value as number,
        season: selectedSeason.value,
        kickoff_time: null,
    }));

    if (!isEditing.value) {
        openEditMode();
    }

    draftRows.value =
        mode === 'replace' ? imported : [...draftRows.value, ...imported];
}

function exportCsv(): void {
    const header = 'Datum;Mannschaft 1;Mannschaft 2;Ort;Saison\r\n';
    const lines = readRows.value.map((row) => {
        const team =
            props.teams.find((candidate) => candidate.id === row.team_id)
                ?.name ?? 'Unbekannt';
        const home = row.at_home ? team : row.versus;
        const away = row.at_home ? row.versus : team;

        return `${row.date};${home};${away};${row.city ?? ''};${row.season}`;
    });
    const csv = `\uFEFF${header}${lines.join('\r\n')}`;
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');

    link.href = url;
    link.download = `spielplan-${selectedSeason.value || 'export'}.csv`;
    link.click();

    URL.revokeObjectURL(url);
}

function updateSeason(value: string): void {
    selectedSeason.value = value;
    router.get('/spielplan', { season: value });
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
                title="Spielplan"
                :can-edit="props.canEdit"
                :is-editing="isEditing"
                :is-saving="isSaving"
                :has-changes="hasChanges"
                :change-count="changeCount"
                :team-options="
                    props.teams
                        .filter((team) => team.canEdit)
                        .map((team) => ({ value: team.id, label: team.name }))
                "
                :season-options="
                    props.seasons.map((seasonOption) => ({
                        value: seasonOption,
                        label: seasonOption,
                    }))
                "
                :selected-team-id="selectedTeamId"
                :selected-season="selectedSeason"
                @update:team-id="selectedTeamId = $event"
                @update:season="updateSeason"
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

            <div
                v-if="isEditing && props.seasons.length === 0"
                class="grid max-w-xs gap-2"
            >
                <label for="new-season" class="text-sm font-medium">
                    Saison
                </label>
                <Input
                    id="new-season"
                    v-model="selectedSeason"
                    type="text"
                    placeholder="2026/27"
                    :aria-invalid="Boolean(validationErrors.season)"
                    aria-describedby="new-season-hint"
                />
                <p
                    v-if="validationErrors.season"
                    id="new-season-hint"
                    role="alert"
                    class="text-sm text-destructive"
                >
                    {{ validationErrors.season }}
                </p>
                <p
                    v-else
                    id="new-season-hint"
                    class="text-sm text-muted-foreground"
                >
                    Bitte im Format Jahr/Jahr angeben, zum Beispiel 2026/27.
                </p>
            </div>

            <Card v-if="upcomingMatch">
                <CardHeader>
                    <CardTitle>Nächstes Spiel</CardTitle>
                </CardHeader>
                <CardContent class="space-y-1">
                    <p class="text-lg font-semibold">
                        {{ formatDate(upcomingMatch.date) }}
                        <span v-if="upcomingMatch.kickoff_time"
                            >· {{ upcomingMatch.kickoff_time }} Uhr</span
                        >
                    </p>
                    <p>
                        {{ upcomingMatch.at_home ? 'vs' : '@' }}
                        {{ upcomingMatch.versus }}
                    </p>
                    <p class="text-sm text-muted-foreground">
                        {{ upcomingMatch.city ?? 'Ort offen' }}
                    </p>
                </CardContent>
            </Card>

            <section v-if="isEditing" class="space-y-3">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-medium">Entwurf</h2>
                    <Button type="button" variant="outline" @click="addRow"
                        >+ Spiel hinzufügen</Button
                    >
                </div>
                <div class="grid gap-3">
                    <div
                        v-for="(row, index) in draftRows"
                        :key="`${row.id ?? 'new'}-${index}`"
                        class="rounded-lg border p-4"
                    >
                        <div class="grid gap-3 md:grid-cols-5">
                            <label class="grid gap-1 text-sm">
                                Datum
                                <Input v-model="row.date" type="date" />
                            </label>
                            <label class="grid gap-1 text-sm md:col-span-2">
                                Gegner
                                <Input
                                    v-model="row.versus"
                                    type="text"
                                    placeholder="Gegner"
                                    list="match-opponent-suggestions"
                                />
                            </label>
                            <label class="grid gap-1 text-sm">
                                Typ
                                <select
                                    v-model="row.at_home"
                                    class="h-10 rounded-md border border-input bg-background px-3 text-sm"
                                >
                                    <option :value="true">Heim</option>
                                    <option :value="false">Auswärts</option>
                                </select>
                            </label>
                            <label class="grid gap-1 text-sm">
                                Ort
                                <Input
                                    :model-value="row.city ?? ''"
                                    type="text"
                                    placeholder="Ort"
                                    list="match-city-suggestions"
                                    @update:model-value="
                                        row.city = ($event as string) || null
                                    "
                                />
                            </label>
                        </div>
                        <div
                            v-if="rowValidationErrors(index).length > 0"
                            role="alert"
                            class="mt-2 space-y-1 text-sm text-destructive"
                        >
                            <p
                                v-for="error in rowValidationErrors(index)"
                                :key="error.key"
                            >
                                {{ error.message }}
                            </p>
                        </div>
                        <Button
                            type="button"
                            variant="ghost"
                            class="mt-2"
                            :aria-label="`Spiel gegen ${row.versus || 'unbekannt'} entfernen`"
                            @click="removeRow(index)"
                        >
                            Zeile entfernen
                        </Button>
                    </div>
                </div>
            </section>

            <section v-else class="space-y-4">
                <label class="grid max-w-xs gap-2 text-sm font-medium">
                    Team-Filter
                    <select
                        v-model="selectedViewTeam"
                        class="h-10 rounded-md border border-input bg-background px-3 text-sm"
                    >
                        <option value="all">Ganzer Verein</option>
                        <option
                            v-for="team in props.teams"
                            :key="team.id"
                            :value="team.id"
                        >
                            {{ team.name }}
                        </option>
                    </select>
                </label>

                <div
                    v-if="groupedRows.length === 0"
                    class="rounded-lg border border-dashed p-6 text-sm text-muted-foreground"
                >
                    Noch kein Spielplan hinterlegt.
                </div>

                <div
                    v-for="month in groupedRows"
                    :key="month.monthKey"
                    class="space-y-2"
                >
                    <h3
                        class="text-sm font-semibold tracking-wide text-muted-foreground uppercase"
                    >
                        {{ month.monthLabel }}
                    </h3>
                    <div
                        v-for="(row, rowIndex) in month.rows"
                        :key="row.id ?? `${month.monthKey}-${rowIndex}`"
                        class="rounded-lg border p-3"
                    >
                        <div
                            class="flex flex-col gap-1 md:flex-row md:items-center md:justify-between"
                        >
                            <div>
                                <p class="font-medium">
                                    {{ formatDate(row.date) }}
                                    <span v-if="row.kickoff_time"
                                        >· {{ row.kickoff_time }}</span
                                    >
                                </p>
                                <p class="text-sm">
                                    {{ row.at_home ? 'vs' : '@' }}
                                    {{ row.versus }}
                                </p>
                            </div>
                            <p class="text-sm text-muted-foreground">
                                {{ row.city ?? 'Ort offen' }}
                            </p>
                        </div>
                    </div>
                </div>
            </section>

            <datalist id="match-opponent-suggestions">
                <option
                    v-for="opponent in opponentSuggestions"
                    :key="opponent"
                    :value="opponent"
                />
            </datalist>
            <datalist id="match-city-suggestions">
                <option
                    v-for="city in citySuggestions"
                    :key="city"
                    :value="city"
                />
            </datalist>
        </main>

        <Dialog v-model:open="addMatchOpen">
            <DialogContent>
                <form class="grid gap-5" @submit.prevent="saveNewMatch">
                    <DialogHeader>
                        <DialogTitle>Spiel hinzufügen</DialogTitle>
                        <DialogDescription
                            >Trage die Spieldaten ein. Das Spiel erscheint erst
                            nach dem Speichern im Entwurf.</DialogDescription
                        >
                    </DialogHeader>

                    <div class="grid gap-4">
                        <label class="grid gap-2 text-sm font-medium">
                            Datum
                            <Input
                                v-model="newMatchDraft.date"
                                type="date"
                                required
                            />
                        </label>
                        <label class="grid gap-2 text-sm font-medium">
                            Gegner
                            <Input
                                v-model="newMatchDraft.versus"
                                type="text"
                                placeholder="Gegner"
                                list="match-opponent-suggestions"
                                required
                            />
                        </label>
                        <label class="grid gap-2 text-sm font-medium">
                            Heim oder Auswärts
                            <select
                                v-model="newMatchDraft.at_home"
                                class="h-10 rounded-md border border-input bg-background px-3 text-sm"
                            >
                                <option :value="true">Heim</option>
                                <option :value="false">Auswärts</option>
                            </select>
                        </label>
                        <label class="grid gap-2 text-sm font-medium">
                            Ort
                            <Input
                                v-model="newMatchDraft.city"
                                type="text"
                                placeholder="Ort"
                                list="match-city-suggestions"
                            />
                        </label>
                    </div>

                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            @click="addMatchOpen = false"
                            >Abbrechen</Button
                        >
                        <Button
                            type="submit"
                            :disabled="
                                !newMatchDraft.date ||
                                !newMatchDraft.versus.trim()
                            "
                            >Speichern</Button
                        >
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <CsvImportDialog
            :open="csvOpen"
            type="match"
            upload-url="/spielplan/csv"
            :team-options="
                props.teams.map((team) => ({ id: team.id, name: team.name }))
            "
            :default-team-id="selectedTeamId ?? undefined"
            :suggested="{}"
            @update:open="csvOpen = $event"
            @import-matches="importMatches"
        />
    </div>
</template>
