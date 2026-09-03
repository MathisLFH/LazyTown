import { onMounted, onUnmounted, ref } from 'vue';

const lastReloadAt = ref(Date.now());

if (import.meta.hot) {
    import.meta.hot.on('vite:afterUpdate', () => {
        lastReloadAt.value = Date.now();
    });
}

export function useHotReloadTimer() {
    const elapsedSeconds = ref(0);
    let intervalId: ReturnType<typeof setInterval> | undefined;

    const updateElapsedTime = () => {
        elapsedSeconds.value = Math.floor((Date.now() - lastReloadAt.value) / 1000);
    };

    onMounted(() => {
        updateElapsedTime();
        intervalId = setInterval(updateElapsedTime, 1000);
    });

    onUnmounted(() => {
        if (intervalId) {
            clearInterval(intervalId);
        }
    });

    return { elapsedSeconds };
}