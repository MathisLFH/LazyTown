import { onMounted, ref } from 'vue';

const storageKey = 'lazytown.profile-avatar';
const avatarDataUrl = ref<string | null>(null);
let hasLoaded = false;

export function useProfileAvatar() {
    onMounted(() => {
        if (hasLoaded) {
            return;
        }

        avatarDataUrl.value = window.localStorage.getItem(storageKey);
        hasLoaded = true;
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
            window.localStorage.setItem(storageKey, reader.result);
        });
        reader.readAsDataURL(file);
    }

    return {
        avatarDataUrl,
        setAvatar,
    };
}
