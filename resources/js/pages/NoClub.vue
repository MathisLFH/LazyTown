<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { LogOut, UserRound } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import { logout } from '@/routes';
import { confirm } from '@/routes/teams/members';

const props = defineProps<{ user: { name: string; email: string }; pendingMembers?: { team: { name: string; slug: string } }[] }>();
</script>

<template>
    <Head title="Noch kein Verein" />
    <main class="mx-auto max-w-xl space-y-6 p-6">
        <section class="space-y-5 rounded-lg border p-6">
            <UserRound class="size-8 text-primary" aria-hidden="true" />
            <h1 class="text-2xl font-semibold">Sie wurden noch keinem Verein hinzugefügt.</h1>
            <p class="text-muted-foreground">Bitte melden Sie sich mit diesen Daten bei Ihrem Vereinsinhaber:</p>
            <dl class="grid gap-3 rounded-md bg-muted/50 p-4 text-sm">
                <div><dt class="font-medium">Name</dt><dd>{{ user.name }}</dd></div>
                <div><dt class="font-medium">E-Mail</dt><dd>{{ user.email }}</dd></div>
            </dl>
            <div v-if="props.pendingMembers?.length" class="space-y-3 border-t pt-5">
                <h2 class="font-semibold">Möchten Sie Verein X beitreten?</h2>
                <div v-for="membership in props.pendingMembers" :key="membership.team.slug" class="flex items-center justify-between gap-4 rounded-md bg-muted/50 p-3 text-sm">
                    <span>{{ membership.team.name }}</span>
                    <Form v-bind="confirm.form(membership.team.slug)"><Button type="submit">Beitritt bestätigen</Button></Form>
                </div>
            </div>
            <Link
                :href="logout()"
                method="post"
                as="button"
                class="inline-flex items-center gap-2 rounded-md border px-4 py-2 text-sm font-medium hover:bg-accent"
                data-test="no-club-logout-button"
            >
                <LogOut class="size-4" aria-hidden="true" />
                Abmelden
            </Link>
        </section>
    </main>
</template>