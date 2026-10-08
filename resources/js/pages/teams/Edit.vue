<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';
import TeamEditPageContent from '@/components/Teams/TeamEditPageContent.vue';
import { edit, index } from '@/routes/teams';
import type { RoleOption, Team, TeamInvitation, TeamMember, TeamPermissions } from '@/types';

type Props = {
    team: Team;
    members: TeamMember[];
    invitations: TeamInvitation[];
    permissions: TeamPermissions;
    availableRoles: RoleOption[];
    availableInvitationRoles: RoleOption[];
};

const props = defineProps<Props>();
const pageTitle = computed(() => props.permissions.canUpdateTeam ? `Edit ${props.team.name}` : `View ${props.team.name}`);

defineOptions({
    layout: (layoutProps: { team: Team }) => ({
        breadcrumbs: [
            { title: 'Teams', href: index() },
            { title: layoutProps.team.name, href: edit(layoutProps.team.slug) },
        ],
    }),
});
</script>

<template>
    <Head :title="pageTitle" />
    <TeamEditPageContent
        :team="team"
        :members="members"
        :invitations="invitations"
        :permissions="permissions"
        :available-roles="availableRoles"
        :available-invitation-roles="availableInvitationRoles"
    />
</template>
