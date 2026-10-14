<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import NotificationSettingsController from '@/actions/App/Http/Controllers/Settings/NotificationSettingsController';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { test as createTestNotification } from '@/routes/notifications';

type Category = {
    label: string;
    description: string;
};

defineProps<{
    categories: Record<string, Category>;
    notificationSettings: Record<string, boolean>;
    canCreateTestNotification: boolean;
}>();
</script>

<template>
    <h1 class="sr-only">Benachrichtigungseinstellungen</h1>
    <div class="space-y-6">
        <Heading
            variant="small"
            title="Benachrichtigungen"
            description="Wähle aus, über welche Ereignisse du später benachrichtigt werden möchtest."
        />
        <Form
            v-bind="NotificationSettingsController.update.form()"
            class="space-y-4"
            v-slot="{ processing }"
        >
            <label
                v-for="(category, key) in categories"
                :key="key"
                class="flex items-start gap-3 rounded-lg border p-4 transition-colors hover:bg-muted/50"
            >
                <input
                    type="checkbox"
                    :name="`notification_settings[${key}]`"
                    value="1"
                    :checked="notificationSettings[key]"
                    class="mt-1 size-4 rounded border-input accent-primary"
                />
                <span class="grid gap-1">
                    <span class="font-medium">{{ category.label }}</span>
                    <span class="text-sm text-muted-foreground">{{
                        category.description
                    }}</span>
                </span>
            </label>
            <Button :disabled="processing">
                {{ processing ? 'Speichern...' : 'Einstellungen speichern' }}
            </Button>
        </Form>
        <section v-if="canCreateTestNotification" class="border-t pt-6">
            <Heading
                variant="small"
                title="Testbenachrichtigung"
                description="Erstelle eine lokale Testbenachrichtigung, um die Glocke und das Dropdown zu prüfen."
            />
            <Form
                v-bind="createTestNotification.form()"
                class="mt-4"
                #default="{ processing }"
            >
                <Button variant="outline" :disabled="processing">
                    {{
                        processing
                            ? 'Wird erstellt...'
                            : 'Testbenachrichtigung erstellen'
                    }}
                </Button>
            </Form>
        </section>
    </div>
</template>
