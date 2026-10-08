<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { ChevronDown, Mail, Phone, UserPlus, X } from '@lucide/vue';
import { ref, computed } from 'vue';
import CancelInvitationModal from '@/components/CancelInvitationModal.vue';
import Heading from '@/components/Heading.vue';
import InviteMemberModal from '@/components/InviteMemberModal.vue';
import RemoveMemberModal from '@/components/RemoveMemberModal.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { useInitials } from '@/composables/useInitials';
import { update as updateMember } from '@/routes/teams/members';
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
const { getInitials } = useInitials();

const inviteDialogOpen = ref(false);
const removeMemberDialogOpen = ref(false);
const memberToRemove = ref<TeamMember | null>(null);
const cancelInvitationDialogOpen = ref(false);
const invitationToCancel = ref<TeamInvitation | null>(null);

const trainers = computed(() => props.members.filter((member) => member.role !== 'member'));
const players = computed(() => props.members.filter((member) => member.role === 'member'));
const memberGroups = computed(() => [
    { title: 'Trainer', members: trainers.value },
    { title: 'Spieler', members: players.value },
]);

const updateMemberRole = (member: TeamMember, newRole: string) => {
    router.visit(updateMember([props.team.slug, member.id]), {
        data: { role: newRole },
        preserveScroll: true,
    });
};

const confirmRemoveMember = (member: TeamMember) => {
    memberToRemove.value = member;
    removeMemberDialogOpen.value = true;
};

const confirmCancelInvitation = (invitation: TeamInvitation) => {
    invitationToCancel.value = invitation;
    cancelInvitationDialogOpen.value = true;
};
</script>

<template>
    <div class="space-y-8 p-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <Heading :title="team.name" description="Übersicht über die Mitglieder und ihre Kontaktdaten." />
            <Button v-if="permissions.canCreateInvitation" data-test="invite-member-button" @click="inviteDialogOpen = true">
                <UserPlus /> Mitglied hinzufügen
            </Button>
        </div>

        <section v-for="group in memberGroups" :key="group.title" class="space-y-5">
            <div class="flex items-center justify-between gap-4"><Heading variant="small" :title="group.title" /></div>
            <div v-if="group.members.length" class="space-y-3">
                <article v-for="member in group.members" :key="member.id" class="grid gap-4 rounded-lg border p-4 sm:grid-cols-[minmax(0,1fr)_auto_auto] sm:items-center">
                    <div class="flex items-center gap-4">
                        <Avatar class="h-10 w-10"><AvatarImage v-if="member.avatar" :src="member.avatar" :alt="member.name" /><AvatarFallback>{{ getInitials(member.name) }}</AvatarFallback></Avatar>
                        <div><h2 class="font-medium">{{ member.name }}</h2><Badge variant="secondary" class="mt-1">{{ member.role_label }}</Badge></div>
                    </div>
                    <div class="flex flex-col gap-2 text-sm sm:items-end">
                        <a :href="`mailto:${member.email}`" class="inline-flex items-center gap-2 text-muted-foreground transition hover:text-foreground"><Mail class="h-4 w-4" />{{ member.email }}</a>
                        <a v-if="member.phone" :href="`tel:${member.phone}`" class="inline-flex items-center gap-2 text-muted-foreground transition hover:text-foreground"><Phone class="h-4 w-4" />{{ member.phone }}</a>
                    </div>
                    <div class="flex items-center gap-2 sm:justify-self-end">
                        <Button v-if="permissions.canCreateInvitation" variant="outline" size="sm" data-test="request-pass-button">Pass beantragen</Button>
                        <DropdownMenu v-if="member.role !== 'owner' && permissions.canUpdateMember">
                            <DropdownMenuTrigger as-child><Button variant="outline" size="sm">{{ member.role_label }}<ChevronDown class="ml-2 h-4 w-4 opacity-50" /></Button></DropdownMenuTrigger>
                            <DropdownMenuContent><DropdownMenuItem v-for="role in availableRoles" :key="role.value" @click="updateMemberRole(member, role.value)">{{ role.label }}</DropdownMenuItem></DropdownMenuContent>
                        </DropdownMenu>
                        <TooltipProvider v-if="member.role !== 'owner' && permissions.canRemoveMember"><Tooltip><TooltipTrigger as-child><Button variant="ghost" size="sm" @click="confirmRemoveMember(member)"><X class="h-4 w-4" /></Button></TooltipTrigger><TooltipContent>Mitglied entfernen</TooltipContent></Tooltip></TooltipProvider>
                    </div>
                </article>
            </div>
            <p v-else class="rounded-lg border border-dashed p-6 text-center text-muted-foreground">Noch keine {{ group.title }} im Team.</p>
        </section>

        <section v-if="permissions.canCreateInvitation && invitations.length" class="space-y-5">
            <Heading variant="small" title="Ausstehende Einladungen" description="Diese Accounts müssen ihre Einladung noch annehmen." />
            <div class="space-y-3">
                <article v-for="invitation in invitations" :key="invitation.code" class="flex items-center justify-between rounded-lg border p-4">
                    <div><p class="font-medium">{{ invitation.email }}</p><p class="text-sm text-muted-foreground">{{ invitation.role_label }}</p></div>
                    <TooltipProvider v-if="permissions.canCancelInvitation"><Tooltip><TooltipTrigger as-child><Button variant="ghost" size="sm" @click="confirmCancelInvitation(invitation)"><X class="h-4 w-4" /></Button></TooltipTrigger><TooltipContent>Einladung zurückziehen</TooltipContent></Tooltip></TooltipProvider>
                </article>
            </div>
        </section>
    </div>

    <InviteMemberModal v-if="permissions.canCreateInvitation" :team="team" :available-invitation-roles="availableInvitationRoles" :open="inviteDialogOpen" @update:open="inviteDialogOpen = $event" />
    <RemoveMemberModal :team="team" :member="memberToRemove" :open="removeMemberDialogOpen" @update:open="removeMemberDialogOpen = $event" />
    <CancelInvitationModal :team="team" :invitation="invitationToCancel" :open="cancelInvitationDialogOpen" @update:open="cancelInvitationDialogOpen = $event" />
</template>
