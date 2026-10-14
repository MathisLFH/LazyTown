<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import SchedulePageContent from '@/components/Schedule/SchedulePageContent.vue';

type TeamOption = {
    id: number;
    name: string;
    canEdit: boolean;
};

type MatchRow = {
    id: number;
    team_id: number;
    season: string;
    date: string;
    kickoff_time: string | null;
    versus: string;
    at_home: boolean;
    city: string | null;
};

defineProps<{
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
</script>

<template>
    <Head title="Spielplan" />

    <SchedulePageContent
        :teams="teams"
        :seasons="seasons"
        :season="season"
        :matches="matches"
        :suggestions="suggestions"
        :can-edit="canEdit"
    />
</template>
