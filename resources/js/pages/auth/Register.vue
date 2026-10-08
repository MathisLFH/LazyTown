<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import RegisterClubPageContent from '@/components/Auth/RegisterClubPageContent.vue';
import type { TeamInvitationContext } from '@/types';

interface PendingRegistration {
    club: { name: string; subdomain: string };
    admin: Record<string, string | null>;
    checkoutState: 'cancelled' | 'pending' | null;
}

defineProps<{
    passwordRules: string;
    annualAccessPrice: string;
    teamInvitation?: TeamInvitationContext | null;
    pendingRegistration?: PendingRegistration | null;
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
        :annual-access-price="annualAccessPrice"
        :pending-registration="pendingRegistration"
    />
</template>
