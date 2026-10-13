<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { Plus, Trash2, Users } from '@lucide/vue';
import { computed, ref } from 'vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    destroy as deleteMember,
    index as manageMembers,
    store as createMember,
} from '@/routes/admin/members';
import { update as updateMemberRoles } from '@/routes/admin/members/roles';

type Member = {
    id: number;
    name: string;
    email: string;
    roles: string[] | null;
    must_change_password: boolean;
    canManageRoles: boolean;
    canDelete: boolean;
    deleteBlockedReason: string | null;
};

type MemberSection = {
    title: string;
    description?: string;
    members: Member[];
};

const props = defineProps<{ members: Member[] }>();
const dialogOpen = ref(false);
const deleteDialogOpen = ref(false);
const memberToDelete = ref<Member | null>(null);
const deleteConfirmation = ref('');
const pageTitle = computed(() => `Mitglieder (${props.members.length})`);
const memberSections = computed<MemberSection[]>(() => {
    const activeMembers = props.members.filter(
        (member) => !member.must_change_password,
    );
    const pendingMembers = props.members.filter(
        (member) => member.must_change_password,
    );

    return [
        { title: 'Mitglieder', members: activeMembers },
        {
            title: 'Ausstehend',
            description:
                'Diese Mitglieder müssen ihr Profil beim ersten Login vervollständigen und ihr Passwort ändern.',
            members: pendingMembers,
        },
    ].filter((section) => section.members.length > 0);
});

function openDeleteDialog(member: Member): void {
    memberToDelete.value = member;
    deleteConfirmation.value = '';
    deleteDialogOpen.value = true;
}

function handleDeleteDialogOpen(open: boolean): void {
    deleteDialogOpen.value = open;

    if (!open) {
        memberToDelete.value = null;
        deleteConfirmation.value = '';
    }
}

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Mitglieder', href: manageMembers() }],
    },
});
</script>

<template>
    <Head title="Mitglieder" />

    <main class="space-y-6">
        <header class="flex flex-wrap items-end justify-between gap-4">
            <Heading
                variant="small"
                :title="pageTitle"
                description="Mitglieder sowie Trainer- und Verwaltungsrollen verwalten."
            />
            <Button type="button" @click="dialogOpen = true">
                <Plus class="size-4" aria-hidden="true" />
                Mitglieder
            </Button>
        </header>

        <section
            v-for="section in memberSections"
            :key="section.title"
            class="space-y-3"
        >
            <Heading
                variant="small"
                :title="section.title"
                :description="section.description"
            />
            <div class="divide-y border-y">
                <article
                    v-for="member in section.members"
                    :key="member.id"
                    class="grid gap-3 py-4 sm:grid-cols-[minmax(0,1fr)_auto] sm:items-center"
                    :data-test="`club-member-${member.id}`"
                >
                    <div class="flex min-w-0 items-center gap-3">
                        <Users
                            class="size-5 shrink-0 text-muted-foreground"
                            aria-hidden="true"
                        />
                        <div class="min-w-0">
                            <h2 class="truncate font-medium">
                                {{ member.name }}
                            </h2>
                            <p class="truncate text-sm text-muted-foreground">
                                {{ member.email }}
                            </p>
                            <p
                                v-if="member.deleteBlockedReason"
                                class="text-sm text-muted-foreground"
                            >
                                {{ member.deleteBlockedReason }}
                            </p>
                        </div>
                    </div>

                    <Form
                        v-if="member.canManageRoles"
                        v-bind="updateMemberRoles.form(member.id)"
                        class="grid gap-3 sm:grid-cols-[minmax(0,1fr)_auto] sm:items-center"
                        v-slot="{ processing }"
                        :data-test="`member-roles-${member.id}`"
                    >
                        <div class="flex flex-wrap gap-x-5 gap-y-2">
                            <label
                                class="inline-flex items-center gap-2 text-sm"
                            >
                                <input
                                    type="checkbox"
                                    name="roles[]"
                                    value="trainer"
                                    class="size-4 accent-primary"
                                    :checked="member.roles?.includes('trainer')"
                                    :data-test="`role-trainer-${member.id}`"
                                />
                                Trainer
                            </label>
                            <label
                                class="inline-flex items-center gap-2 text-sm"
                            >
                                <input
                                    type="checkbox"
                                    name="roles[]"
                                    value="verwaltung"
                                    class="size-4 accent-primary"
                                    :checked="
                                        member.roles?.includes('verwaltung')
                                    "
                                    :data-test="`role-administration-${member.id}`"
                                />
                                Verwaltung
                            </label>
                        </div>
                        <div class="flex flex-wrap gap-2 sm:justify-end">
                            <Button
                                type="submit"
                                variant="outline"
                                :disabled="processing"
                                :data-test="`save-member-roles-${member.id}`"
                            >
                                Rollen speichern
                            </Button>
                            <Button
                                type="button"
                                variant="destructive"
                                size="sm"
                                :disabled="!member.canDelete"
                                :title="member.deleteBlockedReason ?? undefined"
                                :data-test="`delete-member-${member.id}`"
                                @click="openDeleteDialog(member)"
                            >
                                <Trash2 class="size-4" aria-hidden="true" />
                                Löschen
                            </Button>
                        </div>
                    </Form>
                    <p v-else class="text-sm text-muted-foreground">
                        Eigene Rollen können nicht geändert werden.
                    </p>
                </article>
            </div>
        </section>

        <p
            v-if="!props.members.length"
            class="border-y py-8 text-center text-sm text-muted-foreground"
        >
            Noch keine Mitglieder angelegt.
        </p>

        <Dialog
            :open="deleteDialogOpen"
            @update:open="handleDeleteDialogOpen"
        >
            <DialogContent>
                <Form
                    v-if="memberToDelete"
                    :key="memberToDelete.id"
                    v-bind="deleteMember.form(memberToDelete.id)"
                    class="grid gap-5"
                    v-slot="{ errors, processing }"
                    @success="handleDeleteDialogOpen(false)"
                >
                    <DialogHeader>
                        <DialogTitle>Mitglied wirklich löschen?</DialogTitle>
                        <DialogDescription>
                            Dieser Vorgang kann nicht rückgängig gemacht
                            werden. Das Konto von
                            <strong>{{ memberToDelete.name }}</strong>
                            wird dauerhaft gelöscht.
                        </DialogDescription>
                    </DialogHeader>

                    <div class="grid gap-2">
                        <Label for="delete-member-confirmation">
                            Gib den Namen des Mitglieds zur Bestätigung ein.
                        </Label>
                        <Input
                            id="delete-member-confirmation"
                            v-model="deleteConfirmation"
                            name="name"
                            autocomplete="off"
                            required
                            data-test="delete-member-confirmation"
                        />
                        <InputError :message="errors.name" />
                    </div>

                    <DialogFooter class="gap-2">
                        <DialogClose as-child>
                            <Button type="button" variant="outline">
                                Abbrechen
                            </Button>
                        </DialogClose>
                        <Button
                            type="submit"
                            variant="destructive"
                            :disabled="
                                processing ||
                                deleteConfirmation !== memberToDelete.name
                            "
                            data-test="delete-member-confirm"
                        >
                            Mitglied löschen
                        </Button>
                    </DialogFooter>
                </Form>
            </DialogContent>
        </Dialog>

        <Dialog v-model:open="dialogOpen">
            <DialogContent>
                <Form
                    v-bind="createMember.form()"
                    class="grid gap-5"
                    v-slot="{ errors, processing }"
                    reset-on-success
                    @success="dialogOpen = false"
                >
                    <DialogHeader>
                        <DialogTitle>Mitglied hinzufügen</DialogTitle>
                        <DialogDescription>
                            Lege den Zugang an. Beim ersten Login ergänzt das
                            Mitglied seine Profildaten und ändert das Passwort.
                        </DialogDescription>
                    </DialogHeader>

                    <div class="grid gap-4">
                        <div class="grid gap-2">
                            <Label for="new-member-email">E-Mail-Adresse</Label>
                            <Input
                                id="new-member-email"
                                name="email"
                                type="email"
                                autocomplete="email"
                                required
                                autofocus
                                data-test="new-member-email"
                            />
                            <InputError :message="errors.email" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="new-member-password">Passwort</Label>
                            <Input
                                id="new-member-password"
                                name="password"
                                type="password"
                                autocomplete="new-password"
                                required
                                data-test="new-member-password"
                            />
                            <InputError :message="errors.password" />
                        </div>
                    </div>

                    <DialogFooter class="gap-2">
                        <DialogClose as-child>
                            <Button type="button" variant="outline">
                                Abbrechen
                            </Button>
                        </DialogClose>
                        <Button
                            type="submit"
                            :disabled="processing"
                            data-test="create-member-submit"
                        >
                            {{ processing ? 'Wird angelegt...' : 'Mitglied +' }}
                        </Button>
                    </DialogFooter>
                </Form>
            </DialogContent>
        </Dialog>
    </main>
</template>
