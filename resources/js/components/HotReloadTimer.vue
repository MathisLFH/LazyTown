<script setup lang="ts">
import { useHotReloadTimer } from '@/composables/useHotReloadTimer';

const { elapsedSeconds } = useHotReloadTimer();
</script>

<template>
    <div class="fixed right-3 bottom-3 z-50 rounded-md border border-border/70 bg-background/90 px-2 py-1 text-[11px] text-muted-foreground shadow-sm backdrop-blur" data-test="hot-reload-timer">
        Hot Reload vor {{ elapsedSeconds }} s
    </div>
</template>