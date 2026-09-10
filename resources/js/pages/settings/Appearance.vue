<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AppearanceSettingsPageContent from '@/components/Settings/AppearanceSettingsPageContent.vue';
import { edit } from '@/routes/appearance';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Appearance settings',
                href: edit(),
            },
        ],
    },
});
</script>

<template>
    <Head title="Appearance settings" />

    <AppearanceSettingsPageContent />
</template>
