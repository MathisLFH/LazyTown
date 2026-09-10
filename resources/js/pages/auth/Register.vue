<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import RegisterPageContent from '@/components/Auth/RegisterPageContent.vue';
import type { TeamInvitationContext } from '@/types';

defineProps<{
    passwordRules: string;
    teamInvitation?: TeamInvitationContext | null;
}>();

defineOptions({
    layout: {
        title: 'Create an account',
        description: 'Enter your details below to create your account',
    },
});
</script>

<template>
    <Head title="Register" />

    <RegisterPageContent
        :password-rules="passwordRules"
        :team-invitation="teamInvitation"
    />
</template>
