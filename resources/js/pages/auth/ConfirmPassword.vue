<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import ConfirmPasswordPageContent from '@/components/Auth/ConfirmPasswordPageContent.vue';

defineOptions({
    layout: {
        title: 'Passwort bestätigen',
        description:
            'Dies ist ein sicherer Bereich der Anwendung. Bitte bestätigen Sie Ihr Passwort, bevor Sie fortfahren.',
    },
});
</script>

<template>
    <Head title="Passwort bestätigen" />

    <ConfirmPasswordPageContent />
</template>
