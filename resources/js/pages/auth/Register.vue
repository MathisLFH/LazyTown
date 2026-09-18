<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import RegisterClubPageContent from '@/components/Auth/RegisterClubPageContent.vue';
import type { TeamInvitationContext } from '@/types';

defineProps<{
    passwordRules: string;
    teamInvitation?: TeamInvitationContext | null;
}>();

// defineOptions({
//     layout: {
//         title: 'Create an account',
//         description: 'Enter your details below to create your account',
//     },
// });
</script>

<template>
    <!-- <Head title="Register" /> -->

    <RegisterClubPageContent
        :password-rules="passwordRules"
        :team-invitation="teamInvitation"
    />
</template>
