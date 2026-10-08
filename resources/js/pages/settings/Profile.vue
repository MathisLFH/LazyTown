<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import ProfileSettingsPageContent from '@/components/Settings/ProfileSettingsPageContent.vue';
import { edit } from '@/routes/profile';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Profile settings',
                href: edit(),
            },
        ],
    },
});
</script>

<template>
    <Head title="Profileinstellungen" />
    <ProfileSettingsPageContent />
</template>
