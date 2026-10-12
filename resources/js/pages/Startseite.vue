<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import StartseiteContent from '@/components/StartseiteContent.vue';

defineProps<{
    trainerName: string | null;
}>();
</script>

<template>
    <Head title="Startseite" />

    <StartseiteContent :trainer-name="trainerName" />
</template>