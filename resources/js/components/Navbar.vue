<script setup lang="ts">
import { HttpCancelledError } from '@inertiajs/core';
import { Form, Link, router, useHttp, usePage } from '@inertiajs/vue3';
import { Bell, ChevronDown, CircleUserRound, Search } from '@lucide/vue';
import { computed, onBeforeUnmount, ref, watch } from 'vue';
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
import { suggestions as searchSuggestions } from '@/routes/search';
import { edit as payment } from '@/routes/teams/payment';
import { read } from '@/routes/notifications';
import { single as markAsRead } from '@/routes/notifications/read';
import type { NotificationItem } from '@/types/auth';

type NavigationItem = {
    label: string;
    href: string;
};

type SearchSuggestion = {
    type: string;
    title: string;
    subtitle?: string;
    url: string;
};

type SearchGroup = {
    type: string;
    label: string;
    items: SearchSuggestion[];
};

type SearchResponse = {
    query: string;
    groups: SearchGroup[];
};

type SearchForm = {
    q: string;
    limit: number;
};

const searchQuery = ref('');
const searchResponse = ref<SearchResponse>({ query: '', groups: [] });
const showSearchSuggestions = ref(false);
const searchError = ref<string | null>(null);
const highlightedSuggestionIndex = ref(-1);
const searchRequestId = ref(0);
let searchDebounceTimer: ReturnType<typeof setTimeout> | undefined;
const searchHttp = useHttp<SearchForm, SearchResponse>({
    q: '',
    limit: 10,
});
const { isCurrentUrl } = useCurrentUrl();
const page = usePage();
const currentUser = computed(() => page.props.auth.user);
const currentTeam = computed(() => page.props.currentTeam);
const { avatarDataUrl } = useProfileAvatar(currentUser.value?.id ?? null);
const isAuthenticated = computed(() => Boolean(page.props.auth.user));
const userRoles = computed(() => (currentUser.value?.roles ?? []) as string[]);
const profileUrl = profile().url;
const flattenedSuggestions = computed(() =>
    searchResponse.value.groups.flatMap((group) =>
        group.items.map((item) => item),
    ),
);
const hasSearchSuggestions = computed(
    () => flattenedSuggestions.value.length > 0,
);

const cancelSearch = () => {
    searchRequestId.value += 1;
    searchHttp.cancel();

    if (searchDebounceTimer) {
        clearTimeout(searchDebounceTimer);
        searchDebounceTimer = undefined;
    }
};

const executeSearch = async (query: string, requestId: number) => {
    if (requestId !== searchRequestId.value) {
        return;
    }

    searchHttp.q = query;

    try {
        const response = await searchHttp.get(searchSuggestions.url());

        if (requestId !== searchRequestId.value) {
            return;
        }

        searchResponse.value = response;
        highlightedSuggestionIndex.value = -1;
        showSearchSuggestions.value = true;
    } catch (error) {
        if (error instanceof HttpCancelledError) {
            return;
        }

        if (requestId === searchRequestId.value) {
            searchError.value = 'Die Suche konnte nicht geladen werden.';
            showSearchSuggestions.value = true;
        }
    }
};

const scheduleSearch = (query: string) => {
    cancelSearch();
    searchError.value = null;
    searchResponse.value = { query, groups: [] };
    highlightedSuggestionIndex.value = -1;

    if (query.trim().length < 2) {
        showSearchSuggestions.value = false;

        return;
    }

    showSearchSuggestions.value = true;
    const requestId = searchRequestId.value;
    searchDebounceTimer = setTimeout(() => {
        void executeSearch(query.trim(), requestId);
    }, 250);
};

const selectSuggestion = (suggestion: SearchSuggestion) => {
    cancelSearch();
    searchQuery.value = '';
    showSearchSuggestions.value = false;
    router.visit(suggestion.url);
};

const handleSearchKeydown = (event: KeyboardEvent) => {
    if (!showSearchSuggestions.value || !hasSearchSuggestions.value) {
        if (event.key === 'Escape') {
            showSearchSuggestions.value = false;
        }

        return;
    }

    if (event.key === 'ArrowDown') {
        event.preventDefault();
        highlightedSuggestionIndex.value = Math.min(
            highlightedSuggestionIndex.value + 1,
            flattenedSuggestions.value.length - 1,
        );
    } else if (event.key === 'ArrowUp') {
        event.preventDefault();
        highlightedSuggestionIndex.value = Math.max(
            highlightedSuggestionIndex.value - 1,
            0,
        );
    } else if (event.key === 'Enter') {
        event.preventDefault();
        const suggestion =
            flattenedSuggestions.value[highlightedSuggestionIndex.value];

        if (suggestion) {
            selectSuggestion(suggestion);
        }
    } else if (event.key === 'Escape') {
        event.preventDefault();
        showSearchSuggestions.value = false;
    }
};

watch(searchQuery, scheduleSearch);

onBeforeUnmount(cancelSearch);

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

function openNotification(notification: NotificationItem): void {
    router.post(
        markAsRead.url(notification.id),
        {},
        {
            preserveScroll: true,
            onSuccess: () => router.visit(notification.url),
        },
    );
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
                <div class="relative w-full max-w-xs">
                    <label class="relative block">
                        <span class="sr-only">Suche</span>
                        <Search
                            class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                            aria-hidden="true"
                        />
                        <input
                            v-model="searchQuery"
                            type="search"
                            placeholder="Suche..."
                            role="combobox"
                            :aria-expanded="showSearchSuggestions"
                            aria-controls="search-suggestions"
                            aria-autocomplete="list"
                            @focus="
                                showSearchSuggestions = searchQuery.length >= 2
                            "
                            @keydown="handleSearchKeydown"
                            class="h-10 w-full rounded-md border border-input bg-background pr-3 pl-9 text-sm transition outline-none focus:border-ring focus:ring-2 focus:ring-ring/30"
                        />
                    </label>

                    <div
                        v-if="showSearchSuggestions"
                        id="search-suggestions"
                        class="absolute top-full z-50 mt-2 w-full min-w-72 overflow-hidden rounded-md border border-border bg-popover text-popover-foreground shadow-md"
                    >
                        <p
                            v-if="searchHttp.processing"
                            class="px-3 py-3 text-sm text-muted-foreground"
                            role="status"
                        >
                            Suche läuft...
                        </p>
                        <p
                            v-else-if="searchError"
                            class="px-3 py-3 text-sm text-destructive"
                            role="alert"
                        >
                            {{ searchError }}
                        </p>
                        <p
                            v-else-if="!hasSearchSuggestions"
                            class="px-3 py-3 text-sm text-muted-foreground"
                        >
                            Keine passenden Ergebnisse.
                        </p>
                        <ul
                            v-else
                            role="listbox"
                            class="max-h-96 overflow-y-auto py-1"
                        >
                            <template
                                v-for="group in searchResponse.groups"
                                :key="group.type"
                            >
                                <li
                                    class="px-3 pt-2 pb-1 text-xs font-semibold tracking-wide text-muted-foreground uppercase"
                                    role="presentation"
                                >
                                    {{ group.label }}
                                </li>
                                <li
                                    v-for="item in group.items"
                                    :key="`${group.type}-${item.url}-${item.title}`"
                                    role="option"
                                    :aria-selected="
                                        flattenedSuggestions[
                                            highlightedSuggestionIndex
                                        ] === item
                                    "
                                >
                                    <button
                                        type="button"
                                        class="flex w-full flex-col px-3 py-2 text-left text-sm transition hover:bg-accent"
                                        :class="{
                                            'bg-accent':
                                                flattenedSuggestions[
                                                    highlightedSuggestionIndex
                                                ] === item,
                                        }"
                                        @mousedown.prevent
                                        @click="selectSuggestion(item)"
                                    >
                                        <span class="font-medium">{{
                                            item.title
                                        }}</span>
                                        <span
                                            v-if="item.subtitle"
                                            class="text-xs text-muted-foreground"
                                        >
                                            {{ item.subtitle }}
                                        </span>
                                    </button>
                                </li>
                            </template>
                        </ul>
                    </div>
                </div>
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
                                class="cursor-pointer rounded-sm px-2 py-2 text-sm transition-colors hover:bg-accent"
                                :class="{
                                    'bg-accent/60': !notification.read_at,
                                }"
                                role="button"
                                tabindex="0"
                                @click="openNotification(notification)"
                                @keydown.enter="openNotification(notification)"
                                @keydown.space.prevent="
                                    openNotification(notification)
                                "
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
