import { onMounted, readonly, ref, watch } from 'vue';

export const roles = ['Spieler', 'Trainer', 'Verwaltung'] as const;

export type Role = (typeof roles)[number];

const activeRole = ref<Role>('Spieler');
const storageKey = 'lazytown.active-role';
let hasInitialized = false;

export function useAuth() {
    onMounted(() => {
        if (hasInitialized) {
            return;
        }

        const storedRole = window.localStorage.getItem(storageKey);

        if (roles.includes(storedRole as Role)) {
            activeRole.value = storedRole as Role;
        }

        watch(activeRole, (role) => {
            window.localStorage.setItem(storageKey, role);
        });

        hasInitialized = true;
    });

    return {
        activeRole,
        roles: readonly(roles),
    };
}
