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
    selectedTeamId?: number | null;
    selectedSeason?: string | null;
};

const props = withDefaults(defineProps<Props>(), {
    teamOptions: () => [],
    seasonOptions: () => [],
    isSaving: false,
    selectedTeamId: null,
    selectedSeason: null,
});

const emit = defineEmits<{
    'update:teamId': [value: number];
    'update:season': [value: string];
    edit: [];
    discard: [];
    save: [];
    csv: [];
    export: [];
}>();
</script>

<template>
    <div class="flex flex-col gap-4 rounded-lg border bg-card p-4">
        <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
            <h1 class="text-2xl font-semibold">{{ props.title }}</h1>

            <div v-if="props.isEditing" class="flex flex-wrap items-center gap-2">
                <Button variant="outline" type="button" @click="emit('discard')">
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
            <div v-else-if="props.canEdit" class="flex flex-wrap items-center gap-2">
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

        <div class="grid gap-3 md:grid-cols-2" v-if="props.teamOptions.length > 0 || props.seasonOptions.length > 0">
            <label v-if="props.teamOptions.length > 0" class="grid gap-2 text-sm font-medium">
                Mannschaft
                <select
                    class="h-10 rounded-md border border-input bg-background px-3 text-sm"
                    :value="props.selectedTeamId ?? ''"
                    @change="emit('update:teamId', Number(($event.target as HTMLSelectElement).value))"
                >
                    <option v-for="option in props.teamOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
                </select>
            </label>

            <label v-if="props.seasonOptions.length > 0" class="grid gap-2 text-sm font-medium">
                Saison
                <select
                    class="h-10 rounded-md border border-input bg-background px-3 text-sm"
                    :value="props.selectedSeason ?? ''"
                    @change="emit('update:season', ($event.target as HTMLSelectElement).value)"
                >
                    <option v-for="option in props.seasonOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
                </select>
            </label>
        </div>
    </div>
</template>
