<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { Bell, ChevronDown, CircleUserRound, Search } from '@lucide/vue';
import { computed, ref } from 'vue';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import type { Role } from '@/composables/useAuth';
import { useAuth } from '@/composables/useAuth';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { useProfileAvatar } from '@/composables/useProfileAvatar';
import {
    bezahlung,
    hallenplan,
    hallenplanBearbeiten,
    home,
    mannschaftBearbeiten,
    meinTeam,
    paesseBeantragen,
    spielendeHinzufuegen,
    spielplan,
} from '@/routes';
import { edit as profile } from '@/routes/profile';

type NavigationItem = {
    label: string;
    href: string;
    minimumRole?: Role;
};

const searchQuery = ref('');
const { isCurrentUrl } = useCurrentUrl();
const { activeRole } = useAuth();
const { avatarDataUrl } = useProfileAvatar();
const isAuthenticated = computed(() => Boolean(usePage().props.auth.user));
const profileUrl = profile().url;
const roleLevel: Record<Role, number> = {
    Spieler: 1,
    Trainer: 2,
    Verwaltung: 3,
};

const navigationItems: NavigationItem[] = [
    { label: 'Startseite', href: home().url },
    { label: 'Spielplan', href: spielplan().url },
    { label: 'Hallenplan', href: hallenplan().url },
    { label: 'Mein Team', href: meinTeam().url },
];

const managementNavigationItems: NavigationItem[] = [
    { label: 'Pässe beantragen', href: paesseBeantragen().url, minimumRole: 'Trainer' },
    { label: 'Spielende hinzufügen', href: spielendeHinzufuegen().url, minimumRole: 'Trainer' },
    { label: 'Mannschaft bearbeiten', href: mannschaftBearbeiten().url, minimumRole: 'Trainer' },
    { label: 'Hallenplan bearbeiten', href: hallenplanBearbeiten().url, minimumRole: 'Verwaltung' },
    { label: 'Bezahlung für das Tool', href: bezahlung().url, minimumRole: 'Verwaltung' },
];

const managementItems = computed<NavigationItem[]>(() =>
    managementNavigationItems.filter(canAccess),
);

function canAccess(item: NavigationItem): boolean {
    return !item.minimumRole || roleLevel[activeRole.value] >= roleLevel[item.minimumRole];
}

const isManagementActive = computed(() =>
    managementItems.value.some((item) => isCurrentUrl(item.href)),
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

                <DropdownMenu v-if="managementItems.length > 0">
                    <DropdownMenuTrigger as-child>
                        <button
                            type="button"
                            class="inline-flex items-center gap-1 rounded-md px-3 py-2 text-sm font-medium text-muted-foreground transition hover:bg-accent hover:text-foreground focus-visible:ring-2 focus-visible:ring-ring/50 focus-visible:outline-none"
                            :class="{
                                'bg-accent text-foreground': isManagementActive,
                            }"
                        >
                            Verwaltung
                            <ChevronDown class="size-4" aria-hidden="true" />
                        </button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="start" class="w-56">
                        <DropdownMenuItem
                            v-for="item in managementItems"
                            :key="item.label"
                            as-child
                        >
                            <Link
                                :href="item.href"
                                class="w-full"
                                :class="{
                                    'bg-accent text-accent-foreground': isCurrentUrl(item.href),
                                }"
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
                    class="inline-flex size-10 items-center justify-center overflow-hidden rounded-full border border-input text-muted-foreground transition hover:bg-accent hover:text-foreground focus-visible:ring-2 focus-visible:ring-ring/50 focus-visible:outline-none"
                    aria-label="Profil öffnen"
                >
                    <img
                        v-if="isAuthenticated && avatarDataUrl"
                        :src="avatarDataUrl"
                        alt=""
                        class="size-full object-cover"
                    />
                    <CircleUserRound v-else class="size-5" aria-hidden="true" />
                </Link>
            </div>
        </div>
    </nav>
</template>