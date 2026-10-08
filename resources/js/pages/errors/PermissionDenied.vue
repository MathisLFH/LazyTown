<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import PermissionDeniedPageContent from '@/components/Errors/PermissionDeniedPageContent.vue';
</script>

<template>
    <Head title="Keine Berechtigung" />
    <PermissionDeniedPageContent />
</template>