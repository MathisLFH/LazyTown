<script setup lang="ts">
import { Form, Link, usePage } from '@inertiajs/vue3';
import { Bell, ChevronDown, CircleUserRound, Search } from '@lucide/vue';
import { computed, ref } from 'vue';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { useProfileAvatar } from '@/composables/useProfileAvatar';
import { hallenplan, home, meinTeam, spielplan } from '@/routes';
import { index as manageTeams } from '@/routes/admin/teams';
import { edit as profile } from '@/routes/profile';
import { edit as payment } from '@/routes/teams/payment';
import { read } from '@/routes/notifications';
import type { NotificationItem } from '@/types/auth';

type NavigationItem = {
    label: string;
    href: string;
};

const searchQuery = ref('');
const { isCurrentUrl } = useCurrentUrl();
const page = usePage();
const currentUser = computed(() => page.props.auth.user);
const currentTeam = computed(() => page.props.currentTeam);
const { avatarDataUrl } = useProfileAvatar(currentUser.value?.id ?? null);
const isAuthenticated = computed(() => Boolean(page.props.auth.user));
const userRoles = computed(() => (currentUser.value?.roles ?? []) as string[]);
const profileUrl = profile().url;

const navigationItems: NavigationItem[] = [
    { label: 'Startseite', href: home().url },
    { label: 'Spielplan', href: spielplan().url },
    { label: 'Hallenplan', href: hallenplan().url },
    { label: 'Mein Team', href: meinTeam().url },
];

const administrationItems = computed(() => {
    if (!userRoles.value.includes('verwaltung')) {
        return [];
    }

    const items: NavigationItem[] = [];

    if (currentTeam.value) {
        items.push({
            label: 'Bezahlung für das Tool',
            href: payment(currentTeam.value.slug).url,
        });
    }

    items.push(
        { label: 'Teams verwalten', href: manageTeams().url },
        { label: 'Team erstellen', href: manageTeams().url },
    );

    return items;
});

const isAdministrationActive = computed(() =>
    administrationItems.value.some((item) => isCurrentUrl(item.href)),
);
const notifications = computed(
    () => page.props.notifications as NotificationItem[],
);
const unreadNotificationsCount = computed(
    () => page.props.unreadNotificationsCount as number,
);

function formatNotificationDate(date: string | null): string {
    if (!date) {
        return '';
    }

    return new Intl.DateTimeFormat('de-DE', {
        dateStyle: 'short',
        timeStyle: 'short',
    }).format(new Date(date));
}
</script>

<template>
    <nav
        class="border-b border-sidebar-border/80 bg-background"
        aria-label="Globale Navigation"
    >
        <div
            class="mx-auto flex min-h-16 max-w-7xl items-center gap-4 px-4 sm:px-6"
        >
            <div class="flex min-w-0 flex-1 items-center">
                <label class="relative block w-full max-w-xs">
                    <span class="sr-only">Suche</span>
                    <Search
                        class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                        aria-hidden="true"
                    />
                    <input
                        v-model="searchQuery"
                        type="search"
                        placeholder="Suche..."
                        class="h-10 w-full rounded-md border border-input bg-background pr-3 pl-9 text-sm transition outline-none focus:border-ring focus:ring-2 focus:ring-ring/30"
                    />
                </label>
            </div>

            <div class="flex min-w-0 items-center gap-1 overflow-x-auto">
                <template v-for="item in navigationItems" :key="item.label">
                    <Link
                        :href="item.href"
                        class="rounded-md px-3 py-2 text-sm font-medium text-muted-foreground transition hover:bg-accent hover:text-foreground"
                        :class="{
                            'bg-accent text-foreground': isCurrentUrl(
                                item.href,
                            ),
                        }"
                    >
                        {{ item.label }}
                    </Link>
                </template>

                <DropdownMenu v-if="administrationItems.length > 0">
                    <DropdownMenuTrigger as-child>
                        <button
                            type="button"
                            class="inline-flex items-center gap-1 rounded-md px-3 py-2 text-sm font-medium text-muted-foreground transition hover:bg-accent hover:text-foreground focus-visible:ring-2 focus-visible:ring-ring/50 focus-visible:outline-none"
                            :class="{
                                'bg-accent text-foreground':
                                    isAdministrationActive,
                            }"
                        >
                            Verwaltung
                            <ChevronDown class="size-4" aria-hidden="true" />
                        </button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="start" class="w-56">
                        <DropdownMenuItem
                            v-for="item in administrationItems"
                            :key="item.label"
                            as-child
                        >
                            <Link
                                :href="item.href"
                                class="w-full"
                                :class="{
                                    'bg-accent text-accent-foreground':
                                        isCurrentUrl(item.href),
                                }"
                            >
                                {{ item.label }}
                            </Link>
                        </DropdownMenuItem>
                    </DropdownMenuContent>
                </DropdownMenu>
            </div>

            <div class="flex flex-1 items-center justify-end gap-1">
                <DropdownMenu>
                    <DropdownMenuTrigger as-child>
                        <button
                            type="button"
                            class="relative inline-flex size-10 items-center justify-center rounded-md text-muted-foreground transition hover:bg-accent hover:text-foreground focus-visible:ring-2 focus-visible:ring-ring/50 focus-visible:outline-none"
                            aria-label="Benachrichtigungen"
                        >
                            <Bell class="size-5" aria-hidden="true" />
                            <span
                                v-if="unreadNotificationsCount > 0"
                                class="absolute top-2 right-2 size-2 rounded-full bg-red-500"
                                aria-label="Ungelesene Benachrichtigungen"
                            />
                        </button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end" class="w-80">
                        <div
                            class="flex items-center justify-between px-2 py-1.5"
                        >
                            <span class="text-sm font-semibold"
                                >Benachrichtigungen</span
                            >
                            <Form
                                v-if="unreadNotificationsCount > 0"
                                :action="read.url()"
                                method="post"
                                class="shrink-0"
                                #default="{ processing }"
                            >
                                <button
                                    type="submit"
                                    class="text-xs text-muted-foreground underline-offset-4 hover:text-foreground hover:underline"
                                    :disabled="processing"
                                >
                                    Als gelesen markieren
                                </button>
                            </Form>
                        </div>
                        <div
                            v-if="notifications.length === 0"
                            class="px-2 py-6 text-center text-sm text-muted-foreground"
                        >
                            Keine Benachrichtigungen vorhanden.
                        </div>
                        <div v-else class="grid gap-1">
                            <div
                                v-for="notification in notifications"
                                :key="notification.id"
                                class="rounded-sm px-2 py-2 text-sm"
                                :class="{
                                    'bg-accent/60': !notification.read_at,
                                }"
                            >
                                <p class="font-medium">
                                    {{ notification.title }}
                                </p>
                                <p class="mt-0.5 text-muted-foreground">
                                    {{ notification.text }}
                                </p>
                                <p class="mt-1 text-xs text-muted-foreground">
                                    {{
                                        formatNotificationDate(
                                            notification.created_at,
                                        )
                                    }}
                                </p>
                            </div>
                        </div>
                    </DropdownMenuContent>
                </DropdownMenu>
                <Link
                    :href="profileUrl"
                    class="inline-flex items-center gap-2 rounded-md border border-input px-2 py-1 text-sm font-medium text-foreground transition hover:bg-accent focus-visible:ring-2 focus-visible:ring-ring/50 focus-visible:outline-none"
                    aria-label="Profil öffnen"
                >
                    <span class="max-w-32 truncate">{{
                        currentUser?.name
                    }}</span>
                    <span
                        class="inline-flex size-8 items-center justify-center overflow-hidden rounded-full border border-input text-muted-foreground"
                    >
                        <img
                            v-if="isAuthenticated && avatarDataUrl"
                            :src="avatarDataUrl"
                            :alt="currentUser?.name ?? 'Profilbild'"
                            class="size-full object-cover"
                        />
                        <CircleUserRound
                            v-else
                            class="size-5"
                            aria-hidden="true"
                        />
                    </span>
                </Link>
            </div>
        </div>
    </nav>
</template>
