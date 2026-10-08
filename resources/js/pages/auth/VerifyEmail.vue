<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import VerifyEmailPageContent from '@/components/Auth/VerifyEmailPageContent.vue';

defineOptions({
    layout: {
        title: 'Email verification',
        description:
            'Please verify your email address by clicking on the link we just emailed to you.',
    },
});

defineProps<{
    status?: string;
}>();
</script>

<template>
    <Head title="Email verification" />

    <VerifyEmailPageContent :status="status" />
</template>
