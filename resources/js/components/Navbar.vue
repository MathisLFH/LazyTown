<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { Bell, CircleUserRound, Search } from '@lucide/vue';
import { computed, ref } from 'vue';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { useProfileAvatar } from '@/composables/useProfileAvatar';
import {
    hallenplan,
    hallenplanBearbeiten,
    home,
    meinTeam,
    spielplan,
} from '@/routes';
import { edit as profile } from '@/routes/profile';
import { edit as payment } from '@/routes/teams/payment';

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

    const items: NavigationItem[] = [
        { label: 'Hallenplan bearbeiten', href: hallenplanBearbeiten().url },
    ];

    if (currentTeam.value) {
        items.push({
            label: 'Bezahlung für das Tool',
            href: payment(currentTeam.value.slug).url,
        });
    }

    return items;
});

const isAdministrationActive = computed(() =>
    administrationItems.value.some((item) => isCurrentUrl(item.href)),
);
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
                        class="h-10 w-full rounded-md border border-input bg-background pr-3 pl-9 text-sm outline-none transition focus:border-ring focus:ring-2 focus:ring-ring/30"
                    />
                </label>
            </div>

            <div class="flex min-w-0 items-center gap-1 overflow-x-auto">
                <template v-for="item in navigationItems" :key="item.label">
                    <Link
                        :href="item.href"
                        class="rounded-md px-3 py-2 text-sm font-medium text-muted-foreground transition hover:bg-accent hover:text-foreground"
                        :class="{
                            'bg-accent text-foreground': isCurrentUrl(item.href),
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
                            :class="{ 'bg-accent text-foreground': isAdministrationActive }"
                        >
                            Verwaltung
                            <ChevronDown class="size-4" aria-hidden="true" />
                        </button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="start" class="w-56">
                        <DropdownMenuItem v-for="item in administrationItems" :key="item.label" as-child>
                            <Link
                                :href="item.href"
                                class="w-full"
                                :class="{ 'bg-accent text-accent-foreground': isCurrentUrl(item.href) }"
                            >
                                {{ item.label }}
                            </Link>
                        </DropdownMenuItem>
                    </DropdownMenuContent>
                </DropdownMenu>
            </div>

            <div class="flex flex-1 items-center justify-end gap-1">
                <button
                    type="button"
                    class="inline-flex size-10 items-center justify-center rounded-md text-muted-foreground transition hover:bg-accent hover:text-foreground focus-visible:ring-2 focus-visible:ring-ring/50 focus-visible:outline-none"
                    aria-label="Benachrichtigungen"
                >
                    <Bell class="size-5" aria-hidden="true" />
                </button>
                <Link
                    :href="profileUrl"
                    class="inline-flex items-center gap-2 rounded-md border border-input px-2 py-1 text-sm font-medium text-foreground transition hover:bg-accent focus-visible:ring-2 focus-visible:ring-ring/50 focus-visible:outline-none"
                    aria-label="Profil öffnen"
                >
                    <span class="max-w-32 truncate">{{ currentUser?.name }}</span>
                    <span class="inline-flex size-8 items-center justify-center overflow-hidden rounded-full border border-input text-muted-foreground">
                        <img
                            v-if="isAuthenticated && avatarDataUrl"
                            :src="avatarDataUrl"
                            :alt="currentUser?.name ?? 'Profilbild'"
                            class="size-full object-cover"
                        />
                        <CircleUserRound v-else class="size-5" aria-hidden="true" />
                    </span>
                </Link>
            </div>
        </div>
    </nav>
</template>