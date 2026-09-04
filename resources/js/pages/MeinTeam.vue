<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Mail, Phone } from '@lucide/vue';
import { computed } from 'vue';
import Heading from '@/components/Heading.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { useInitials } from '@/composables/useInitials';
import { meinTeam } from '@/routes';
import type { Team, TeamMember } from '@/types';

type Props = {
    team: Team;
    members: TeamMember[];
};

const props = defineProps<Props>();
const { getInitials } = useInitials();

const trainers = computed(() => props.members.filter((member) => member.role !== 'member'));
const players = computed(() => props.members.filter((member) => member.role === 'member'));

const memberGroups = computed(() => [
    { title: 'Trainer', members: trainers.value },
    { title: 'Spieler', members: players.value },
]);

defineOptions({
    layout: () => ({
        breadcrumbs: [{ title: 'Mein Team', href: meinTeam() }],
    }),
});
</script>

<template>
    <Head title="Mein Team" />

    <div class="space-y-10">
        <Heading
            :title="team.name"
            description="Übersicht über die Mitglieder und ihre Kontaktdaten."
        />

        <section v-for="group in memberGroups" :key="group.title" class="space-y-5">
            <Heading variant="small" :title="group.title" />
            <div v-if="group.members.length" class="space-y-3">
                <article
                    v-for="member in group.members"
                    :key="member.id"
                    class="flex flex-col gap-4 rounded-lg border p-4 sm:flex-row sm:items-center sm:justify-between"
                >
                    <div class="flex items-center gap-4">
                        <Avatar class="h-10 w-10">
                            <AvatarImage v-if="member.avatar" :src="member.avatar" :alt="member.name" />
                            <AvatarFallback>{{ getInitials(member.name) }}</AvatarFallback>
                        </Avatar>
                        <div>
                            <h2 class="font-medium">{{ member.name }}</h2>
                            <Badge variant="secondary" class="mt-1">{{ member.role_label }}</Badge>
                        </div>
                    </div>
                    <div class="flex flex-col gap-2 text-sm sm:items-end">
                        <a :href="`mailto:${member.email}`" class="inline-flex items-center gap-2 text-muted-foreground transition hover:text-foreground">
                            <Mail class="h-4 w-4" />
                            {{ member.email }}
                        </a>
                        <a v-if="member.phone" :href="`tel:${member.phone}`" class="inline-flex items-center gap-2 text-muted-foreground transition hover:text-foreground">
                            <Phone class="h-4 w-4" />
                            {{ member.phone }}
                        </a>
                    </div>
                </article>
            </div>
            <p v-else class="rounded-lg border border-dashed p-6 text-center text-muted-foreground">
                Noch keine {{ group.title.toLowerCase() }} im Team.
            </p>
        </section>
    </div>
</template>
