<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import LoginPageContent from '@/components/Auth/LoginPageContent.vue';
import type { TeamInvitationContext } from '@/types';

defineOptions({
    layout: {
        title: 'Log dich in deinen Account an',
        description: 'Gib deine E-Mail-Adresse und dein Passwort ein, um dich anzumelden',
    },
});

defineProps<{
    status?: string;
    canResetPassword: boolean;
    teamInvitation?: TeamInvitationContext | null;
}>();
</script>

<template>
    <Head title="Anmelden" />

    <LoginPageContent
        :status="status"
        :can-reset-password="canResetPassword"
        :team-invitation="teamInvitation"
    />
</template>
