<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import TeamIndexPageContent from '@/components/Teams/TeamIndexPageContent.vue';
import { index } from '@/routes/teams';
import type { Team } from '@/types';

type Props = {
    teams: Team[];
};

defineProps<Props>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Teams',
                href: index(),
            },
        ],
    },
});
</script>

<template>
    <Head title="Verein" />

    <TeamIndexPageContent :teams="teams" />
</template>
