import { onMounted, ref } from 'vue';

const storageKey = 'lazytown.profile-avatar';
const avatarDataUrl = ref<string | null>(null);

export function useProfileAvatar(userId: number | null = null) {
    const userStorageKey = `${storageKey}.${userId ?? 'guest'}`;

    onMounted(() => {
        avatarDataUrl.value = window.localStorage.getItem(userStorageKey);
    });

    function setAvatar(file: File | null): void {
        if (!file || !file.type.startsWith('image/')) {
            return;
        }

        const reader = new FileReader();
        reader.addEventListener('load', () => {
            if (typeof reader.result !== 'string') {
                return;
            }

            avatarDataUrl.value = reader.result;
            window.localStorage.setItem(userStorageKey, reader.result);
        });
        reader.readAsDataURL(file);
    }

    return {
        avatarDataUrl,
        setAvatar,
    };
}
