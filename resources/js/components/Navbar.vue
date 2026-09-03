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
};

const searchQuery = ref('');
const { isCurrentUrl } = useCurrentUrl();
const { avatarDataUrl } = useProfileAvatar();
const page = usePage();
const isAuthenticated = computed(() => Boolean(page.props.auth.user));
const userRoles = computed(() => (page.props.auth.user?.roles ?? []) as string[]);
const profileUrl = profile().url;

const navigationItems: NavigationItem[] = [
    { label: 'Startseite', href: home().url },
    { label: 'Spielplan', href: spielplan().url },
    { label: 'Hallenplan', href: hallenplan().url },
    { label: 'Mein Team', href: meinTeam().url },
];

const trainerNavigationItems: NavigationItem[] = [
    { label: 'Pässe beantragen', href: paesseBeantragen().url },
    { label: 'Spielende hinzufügen', href: spielendeHinzufuegen().url },
    { label: 'Mannschaft bearbeiten', href: mannschaftBearbeiten().url },
];

const administrationNavigationItems: NavigationItem[] = [
    { label: 'Hallenplan bearbeiten', href: hallenplanBearbeiten().url },
    { label: 'Bezahlung für das Tool', href: bezahlung().url },
];

const trainerItems = computed(() => userRoles.value.includes('trainer') ? trainerNavigationItems : []);
const administrationItems = computed(() => userRoles.value.includes('verwaltung') ? administrationNavigationItems : []);

const isTrainerActive = computed(() =>
    trainerItems.value.some((item) => isCurrentUrl(item.href)),
);
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

                <DropdownMenu v-if="trainerItems.length > 0">
                    <DropdownMenuTrigger as-child>
                        <button
                            type="button"
                            class="inline-flex items-center gap-1 rounded-md px-3 py-2 text-sm font-medium text-muted-foreground transition hover:bg-accent hover:text-foreground focus-visible:ring-2 focus-visible:ring-ring/50 focus-visible:outline-none"
                            :class="{
                                'bg-accent text-foreground': isTrainerActive,
                            }"
                        >
                            Trainer
                            <ChevronDown class="size-4" aria-hidden="true" />
                        </button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="start" class="w-56">
                        <DropdownMenuItem
                            v-for="item in trainerItems"
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