<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import logo from '../../images/logo.png';

defineOptions({
    inheritAttrs: false,
});

type Props = {
    className?: HTMLAttributes['class'];
};

defineProps<Props>();
</script>

<template>
    <img :src="logo" class="h-30 w-auto object-contain" />
</template>
