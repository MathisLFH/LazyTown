<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import ConfirmPasswordPageContent from '@/components/Auth/ConfirmPasswordPageContent.vue';

defineOptions({
    layout: {
        title: 'Confirm password',
        description:
            'This is a secure area of the application. Please confirm your password before continuing.',
    },
});
</script>

<template>
    <Head title="Confirm password" />

    <ConfirmPasswordPageContent />
</template>
