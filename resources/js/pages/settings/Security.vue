<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import type { Props as ManagePasskeysProps } from '@/components/ManagePasskeys.vue';
import type { Props as ManageTwoFactorProps } from '@/components/ManageTwoFactor.vue';
import SecuritySettingsPageContent from '@/components/Settings/SecuritySettingsPageContent.vue';
import { edit } from '@/routes/security';

type Props = {
    passwordRules: string;
} & ManagePasskeysProps &
    ManageTwoFactorProps;

defineProps<Props>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Security settings',
                href: edit(),
            },
        ],
    },
});
</script>

<template>
    <Head title="Security settings" />

    <SecuritySettingsPageContent
        :password-rules="passwordRules"
        :can-manage-passkeys="canManagePasskeys"
        :passkeys="passkeys"
        :can-manage-two-factor="canManageTwoFactor"
        :requires-confirmation="requiresConfirmation"
        :two-factor-enabled="twoFactorEnabled"
    />
</template>
