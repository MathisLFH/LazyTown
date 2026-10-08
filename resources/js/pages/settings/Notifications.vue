<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import NotificationSettingsPageContent from '@/components/Settings/NotificationSettingsPageContent.vue';
import { edit } from '@/routes/notifications';

type Category = {
    label: string;
    description: string;
};

defineProps<{
    categories: Record<string, Category>;
    notificationSettings: Record<string, boolean>;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Benachrichtigungen',
                href: edit(),
            },
        ],
    },
});
</script>

<template>
    <Head title="Benachrichtigungen" />
    <NotificationSettingsPageContent
        :categories="categories"
        :notification-settings="notificationSettings"
    />
</template>
