<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import ForgotPasswordPageContent from '@/components/Auth/ForgotPasswordPageContent.vue';

defineOptions({
    layout: {
        title: 'Forgot password',
        description: 'Enter your email to receive a password reset link',
    },
});

defineProps<{
    status?: string;
}>();
</script>

<template>
    <Head title="Forgot password" />

    <ForgotPasswordPageContent :status="status" />
</template>
