<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import MyTeamPageContent from '@/components/Teams/MyTeamPageContent.vue';
import { meinTeam } from '@/routes';
import type { RoleOption, Team, TeamInvitation, TeamMember, TeamPermissions } from '@/types';

type Props = {
    team: Team;
    members: TeamMember[];
    invitations: TeamInvitation[];
    permissions: TeamPermissions;
    availableRoles: RoleOption[];
    availableInvitationRoles: RoleOption[];
};

defineProps<Props>();

defineOptions({
    layout: () => ({
        breadcrumbs: [{ title: 'Mein Team', href: meinTeam() }],
    }),
});
</script>

<template>
    <Head title="Mein Team" />

    <MyTeamPageContent
        :team="team"
        :members="members"
        :invitations="invitations"
        :permissions="permissions"
        :available-roles="availableRoles"
        :available-invitation-roles="availableInvitationRoles"
    />
</template>
