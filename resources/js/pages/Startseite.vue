<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import StartseiteContent from '@/components/StartseiteContent.vue';

defineProps<{
    hasClub: boolean;
    pendingMembers: { team: { name: string; slug: string } }[];
    trainerName: string | null;
}>();
</script>

<template>
    <Head title="Startseite" />

    <StartseiteContent :has-club="hasClub" :pending-members="pendingMembers" :trainer-name="trainerName" />
</template>