<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import ResetPasswordPageContent from '@/components/Auth/ResetPasswordPageContent.vue';

defineOptions({
    layout: {
        title: 'Reset password',
        description: 'Please enter your new password below',
    },
});

defineProps<{
    token: string;
    email: string;
    passwordRules: string;
}>();

</script>

<template>
    <Head title="Reset password" />

    <ResetPasswordPageContent
        :token="token"
        :email="email"
        :password-rules="passwordRules"
    />
</template>
