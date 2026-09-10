<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import NoClubPageContent from '@/components/Onboarding/NoClubPageContent.vue';

defineProps<{ user: { name: string; email: string }; pendingMembers?: { team: { name: string; slug: string } }[] }>();
</script>

<template>
    <Head title="Noch kein Verein" />
    <NoClubPageContent :user="user" :pending-members="pendingMembers" />
</template>