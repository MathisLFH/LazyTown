<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ChevronDown, Mail, UserPlus, X } from '@lucide/vue';
import { ref } from 'vue';
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
import { spielendeHinzufuegen } from '@/routes';
import { update as updateMember } from '@/routes/teams/members';
import type {
    RoleOption,
    Team,
    TeamInvitation,
    TeamMember,
    TeamPermissions,
} from '@/types';

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

defineOptions({
    layout: () => ({
        breadcrumbs: [{ title: 'Verein verwalten', href: spielendeHinzufuegen() }],
    }),
});

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
    <Head title="Verein verwalten" />

    <div class="space-y-10">
        <div>
            <Heading
                :title="team.name"
                description="Verwalte die Spielenden deines Vereins und ihre Einladungen."
            />
        </div>

        <section class="space-y-6">
            <div class="flex items-center justify-between gap-4">
                <Heading variant="small" title="Aktive Spielende" />
                <Button v-if="permissions.canCreateInvitation" @click="inviteDialogOpen = true">
                    <UserPlus /> Spielende hinzufügen
                </Button>
            </div>

            <div v-if="members.length" class="space-y-3">
                <div v-for="member in members" :key="member.id" class="flex items-center justify-between rounded-lg border p-4">
                    <div class="flex items-center gap-4">
                        <Avatar class="h-10 w-10">
                            <AvatarImage v-if="member.avatar" :src="member.avatar" :alt="member.name" />
                            <AvatarFallback>{{ getInitials(member.name) }}</AvatarFallback>
                        </Avatar>
                        <div>
                            <div class="font-medium">{{ member.name }}</div>
                            <div class="text-sm text-muted-foreground">{{ member.email }}</div>
                            <div class="text-xs text-muted-foreground">Benutzer-ID: {{ member.public_id }}</div>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <DropdownMenu v-if="member.role !== 'owner' && permissions.canUpdateMember">
                            <DropdownMenuTrigger as-child>
                                <Button variant="outline" size="sm">
                                    {{ member.role_label }} <ChevronDown class="ml-2 h-4 w-4 opacity-50" />
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent>
                                <DropdownMenuItem v-for="role in availableRoles" :key="role.value" @click="updateMemberRole(member, role.value)">
                                    {{ role.label }}
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>
                        <Badge v-else variant="secondary">{{ member.role_label }}</Badge>
                        <TooltipProvider v-if="member.role !== 'owner' && permissions.canRemoveMember">
                            <Tooltip>
                                <TooltipTrigger as-child>
                                    <Button variant="ghost" size="sm" @click="confirmRemoveMember(member)"><X class="h-4 w-4" /></Button>
                                </TooltipTrigger>
                                <TooltipContent>Spielendes entfernen</TooltipContent>
                            </Tooltip>
                        </TooltipProvider>
                    </div>
                </div>
            </div>
            <p v-else class="rounded-lg border border-dashed p-6 text-center text-muted-foreground">
                Noch keine Spieler im Verein. Füge das erste Mitglied hinzu.
            </p>
        </section>

        <section v-if="invitations.length" class="space-y-6">
            <Heading variant="small" title="Ausstehende Einladungen" description="Diese Accounts müssen ihre Einladung noch annehmen." />
            <div class="space-y-3">
                <div v-for="invitation in invitations" :key="invitation.code" class="flex items-center justify-between rounded-lg border p-4">
                    <div class="flex items-center gap-4">
                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-muted"><Mail class="h-5 w-5 text-muted-foreground" /></div>
                        <div>
                            <div class="font-medium">{{ invitation.email }}</div>
                            <div class="text-sm text-muted-foreground">{{ invitation.role_label }} · Einladung ausstehend</div>
                        </div>
                    </div>
                    <TooltipProvider v-if="permissions.canCancelInvitation">
                        <Tooltip>
                            <TooltipTrigger as-child><Button variant="ghost" size="sm" @click="confirmCancelInvitation(invitation)"><X class="h-4 w-4" /></Button></TooltipTrigger>
                            <TooltipContent>Einladung zurückziehen</TooltipContent>
                        </Tooltip>
                    </TooltipProvider>
                </div>
            </div>
        </section>
    </div>

    <InviteMemberModal v-if="permissions.canCreateInvitation" :team="team" :available-invitation-roles="availableInvitationRoles" :open="inviteDialogOpen" @update:open="inviteDialogOpen = $event" />
    <RemoveMemberModal :team="team" :member="memberToRemove" :open="removeMemberDialogOpen" @update:open="removeMemberDialogOpen = $event" />
    <CancelInvitationModal :team="team" :invitation="invitationToCancel" :open="cancelInvitationDialogOpen" @update:open="cancelInvitationDialogOpen = $event" />
</template>
