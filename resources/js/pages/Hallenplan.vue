<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import HallPlanPageContent from '@/components/HallPlan/HallPlanPageContent.vue';

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

type TrainingRow = {
    id: number;
    team_id: number;
    team_name: string;
    gym_id: number | null;
    gym_name: string | null;
    weekday: string;
    starts_at: string;
    ends_at: string;
};

defineProps<{
    teams: TeamOption[];
    gyms: GymOption[];
    trainings: TrainingRow[];
    canEdit: boolean;
}>();
</script>

<template>
    <Head title="Hallenplan" />

    <HallPlanPageContent
        :teams="teams"
        :gyms="gyms"
        :trainings="trainings"
        :can-edit="canEdit"
    />
</template>
