<script setup lang="ts">
import { Download, Pencil, Upload, X } from '@lucide/vue';
import { Button } from '@/components/ui/button';

type Option = {
    value: string | number;
    label: string;
};

type Props = {
    title: string;
    canEdit: boolean;
    isEditing: boolean;
    isSaving?: boolean;
    hasChanges: boolean;
    changeCount: number;
    teamOptions?: Option[];
    seasonOptions?: Option[];
    gymFilterOptions?: Option[];
    teamFilterOptions?: Option[];
    selectedTeamId?: number | null;
    selectedSeason?: string | null;
    selectedGymFilter?: number | 'all';
    selectedTeamFilter?: number | 'all';
};

const props = withDefaults(defineProps<Props>(), {
    teamOptions: () => [],
    seasonOptions: () => [],
    gymFilterOptions: () => [],
    teamFilterOptions: () => [],
    isSaving: false,
    selectedTeamId: null,
    selectedSeason: null,
    selectedGymFilter: 'all',
    selectedTeamFilter: 'all',
});

const emit = defineEmits<{
    'update:teamId': [value: number];
    'update:season': [value: string];
    'update:gymFilter': [value: number | 'all'];
    'update:teamFilter': [value: number | 'all'];
    edit: [];
    discard: [];
    save: [];
    csv: [];
    export: [];
}>();

function filterValue(value: string): number | 'all' {
    return value === 'all' ? 'all' : Number(value);
}
</script>

<template>
    <div class="flex flex-col gap-4 rounded-lg border bg-card p-4">
        <div
            class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between"
        >
            <h1 class="text-2xl font-semibold">{{ props.title }}</h1>

            <div
                v-if="props.isEditing"
                class="flex flex-wrap items-center gap-2"
            >
                <Button
                    variant="outline"
                    type="button"
                    @click="emit('discard')"
                >
                    <X class="size-4" />
                    Verwerfen
                </Button>
                <Button
                    type="button"
                    :disabled="!props.hasChanges || props.isSaving"
                    :aria-busy="props.isSaving"
                    @click="emit('save')"
                >
                    {{
                        props.isSaving
                            ? 'Speichert …'
                            : `Speichern (${props.changeCount} Änderungen)`
                    }}
                </Button>
            </div>
            <div
                v-else-if="props.canEdit"
                class="flex flex-wrap items-center gap-2"
            >
                <Button type="button" @click="emit('edit')">
                    <Pencil class="size-4" />
                    Plan bearbeiten
                </Button>
                <Button variant="outline" type="button" @click="emit('csv')">
                    <Upload class="size-4" />
                    CSV importieren
                </Button>
                <Button variant="outline" type="button" @click="emit('export')">
                    <Download class="size-4" />
                    Exportieren
                </Button>
            </div>
        </div>

        <div
            v-if="
                props.teamOptions.length > 0 ||
                props.seasonOptions.length > 0 ||
                props.gymFilterOptions.length > 0 ||
                props.teamFilterOptions.length > 0
            "
            class="grid gap-3 md:grid-cols-2"
        >
            <label
                v-if="props.teamOptions.length > 0"
                class="grid gap-2 text-sm font-medium"
            >
                Mannschaft
                <select
                    class="h-10 rounded-md border border-input bg-background px-3 text-sm"
                    :value="props.selectedTeamId ?? ''"
                    @change="
                        emit(
                            'update:teamId',
                            Number(($event.target as HTMLSelectElement).value),
                        )
                    "
                >
                    <option
                        v-for="option in props.teamOptions"
                        :key="option.value"
                        :value="option.value"
                    >
                        {{ option.label }}
                    </option>
                </select>
            </label>

            <label
                v-if="props.seasonOptions.length > 0"
                class="grid gap-2 text-sm font-medium"
            >
                Saison
                <select
                    class="h-10 rounded-md border border-input bg-background px-3 text-sm"
                    :value="props.selectedSeason ?? ''"
                    @change="
                        emit(
                            'update:season',
                            ($event.target as HTMLSelectElement).value,
                        )
                    "
                >
                    <option
                        v-for="option in props.seasonOptions"
                        :key="option.value"
                        :value="option.value"
                    >
                        {{ option.label }}
                    </option>
                </select>
            </label>

            <label
                v-if="props.gymFilterOptions.length > 0"
                class="grid gap-2 text-sm font-medium"
            >
                Halle
                <select
                    class="h-10 rounded-md border border-input bg-background px-3 text-sm"
                    :value="props.selectedGymFilter"
                    @change="
                        emit(
                            'update:gymFilter',
                            filterValue(
                                ($event.target as HTMLSelectElement).value,
                            ),
                        )
                    "
                >
                    <option value="all">Alle Hallen</option>
                    <option
                        v-for="option in props.gymFilterOptions"
                        :key="option.value"
                        :value="option.value"
                    >
                        {{ option.label }}
                    </option>
                </select>
            </label>

            <label
                v-if="props.teamFilterOptions.length > 0"
                class="grid gap-2 text-sm font-medium"
            >
                Team
                <select
                    class="h-10 rounded-md border border-input bg-background px-3 text-sm"
                    :value="props.selectedTeamFilter"
                    @change="
                        emit(
                            'update:teamFilter',
                            filterValue(
                                ($event.target as HTMLSelectElement).value,
                            ),
                        )
                    "
                >
                    <option value="all">Alle Teams</option>
                    <option
                        v-for="option in props.teamFilterOptions"
                        :key="option.value"
                        :value="option.value"
                    >
                        {{ option.label }}
                    </option>
                </select>
            </label>
        </div>
    </div>
</template>
