<script setup lang="ts">
import { Form, Head, Link, usePage } from '@inertiajs/vue3';
import { computed, onMounted, ref, watch } from 'vue';
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import DeleteUser from '@/components/DeleteUser.vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useAuth } from '@/composables/useAuth';
import { useProfileAvatar } from '@/composables/useProfileAvatar';
import { logout } from '@/routes';
import { exportMethod as exportProfile } from '@/routes/profile';
import { edit } from '@/routes/profile';
import { send } from '@/routes/verification';

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

const page = usePage();
const user = computed(() => page.props.auth.user);
const { activeRole, roles } = useAuth();
const { avatarDataUrl, setAvatar } = useProfileAvatar();
const privacyConsent = ref(true);
const privacyMessage = ref('');
const privacyStorageKey = 'lazytown.privacy-consent';

onMounted(() => {
    const storedConsent = window.localStorage.getItem(privacyStorageKey);

    if (storedConsent !== null) {
        privacyConsent.value = storedConsent === 'true';
    }

    watch(privacyConsent, (consent) => {
        window.localStorage.setItem(privacyStorageKey, String(consent));
    });
});

function savePrivacyConsent(): void {
    window.localStorage.setItem(privacyStorageKey, String(privacyConsent.value));
    privacyMessage.value = privacyConsent.value
        ? 'Datenschutzeinstellungen gespeichert.'
        : 'Datenschutzeinstellungen aktualisiert.';
}

function handleAvatarChange(event: Event): void {
    const input = event.target as HTMLInputElement;
    setAvatar(input.files?.[0] ?? null);
}
</script>

<template>
    <Head title="Profile settings" />

    <h1 class="sr-only">Profile settings</h1>

    <div class="flex flex-col space-y-6">
        <Heading
            variant="small"
            title="Profile"
            description="Update your name and email address"
        />

        <section class="flex flex-col gap-4 border-b pb-6 sm:flex-row sm:items-center">
            <div class="flex size-24 shrink-0 items-center justify-center overflow-hidden rounded-full border border-input bg-muted text-muted-foreground">
                <img
                    v-if="avatarDataUrl"
                    :src="avatarDataUrl"
                    alt="Profilbild"
                    class="size-full object-cover"
                />
                <span v-else class="text-2xl">{{ user.name.charAt(0).toUpperCase() }}</span>
            </div>
            <label class="grid gap-2 text-sm">
                Profilbild auswählen
                <input
                    type="file"
                    accept="image/*"
                    class="block max-w-full text-sm"
                    @change="handleAvatarChange"
                />
            </label>
        </section>

        <Form
            v-bind="ProfileController.update.form()"
            class="space-y-6"
            v-slot="{ errors, processing }"
        >
            <div class="grid gap-2">
                <Label for="name">Name</Label>
                <Input
                    id="name"
                    class="mt-1 block w-full"
                    name="name"
                    :default-value="user.name"
                    required
                    autocomplete="name"
                    placeholder="Full name"
                />
                <InputError class="mt-2" :message="errors.name" />
            </div>
            <div class="grid gap-2">
                <Label for="birth_date">Geburtsdatum</Label>
                <Input
                    id="birth_date"
                    type="date"
                    class="mt-1 block w-full"
                    name="birth_date"
                    :default-value="user.birth_date ?? ''"
                    autocomplete="bday"
                />
                <InputError class="mt-2" :message="errors.birth_date" />
            </div>

            <div class="grid gap-2">
                <Label for="city">Wohnort</Label>
                <Input
                    id="city"
                    class="mt-1 block w-full"
                    name="city"
                    :default-value="user.city ?? ''"
                    autocomplete="address-level2"
                    placeholder="Wohnort"
                />
                <InputError class="mt-2" :message="errors.city" />
            </div>

            <div class="grid gap-2">
                <Label for="phone">Telefonnummer</Label>
                <Input
                    id="phone"
                    type="tel"
                    class="mt-1 block w-full"
                    name="phone"
                    :default-value="user.phone ?? ''"
                    autocomplete="tel"
                    placeholder="Telefonnummer"
                />
                <InputError class="mt-2" :message="errors.phone" />
            </div>

            <div class="grid gap-2">
                <Label for="email">Email address</Label>
                <Input
                    id="email"
                    type="email"
                    class="mt-1 block w-full"
                    name="email"
                    :default-value="user.email"
                    required
                    autocomplete="username"
                    placeholder="Email address"
                />
                <InputError class="mt-2" :message="errors.email" />
            </div>

            <div v-if="page.props.mustVerifyEmail && !user.email_verified_at">
                <p class="-mt-4 text-sm text-muted-foreground">
                    Your email address is unverified.
                    <Link
                        :href="send()"
                        as="button"
                        class="text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current! dark:decoration-neutral-500"
                    >
                        Click here to re-send the verification email.
                    </Link>
                </p>

                <div
                    v-if="page.props.status === 'verification-link-sent'"
                    class="mt-2 text-sm font-medium text-green-600"
                >
                    A new verification link has been sent to your email address.
                </div>
            </div>

            <div class="flex items-center gap-4">
                <Button :disabled="processing" data-test="update-profile-button"
                    >Save</Button
                >
            </div>
        </Form>

        <section class="space-y-4 border-t pt-6">
            <Heading
                variant="small"
                title="Rolle"
                description="Temporäre Rollen-Auswahl für die Entwicklung"
            />

            <label class="grid max-w-sm gap-2 text-sm" for="active-role">
                Aktive Rolle
                <select
                    id="active-role"
                    v-model="activeRole"
                    class="h-10 rounded-md border border-input bg-background px-3 outline-none focus:border-ring focus:ring-2 focus:ring-ring/30"
                >
                    <option v-for="role in roles" :key="role" :value="role">
                        {{ role }}
                    </option>
                </select>
            </label>
        </section>

        <section class="space-y-4 border-t pt-6">
            <Heading
                variant="small"
                title="Datenschutz (DSGVO)"
                description="Verwalte deine Einwilligung und deine persönlichen Daten."
            />

            <label class="flex max-w-xl items-start gap-3 text-sm">
                <input
                    v-model="privacyConsent"
                    type="checkbox"
                    class="mt-1"
                />
                <span>
                    Ich stimme der Verarbeitung meiner Daten zur Nutzung der
                    Vereinsverwaltung zu.
                </span>
            </label>

            <div class="flex flex-wrap gap-2">
                <button
                    type="button"
                    class="rounded-md bg-primary px-3 py-2 text-sm text-primary-foreground"
                    @click="savePrivacyConsent"
                >
                    Datenschutzeinstellung speichern
                </button>
                <a
                    :href="exportProfile().url"
                    download
                    class="rounded-md border px-3 py-2 text-sm font-medium transition hover:bg-accent"
                >
                    Meine Daten exportieren
                </a>
            </div>

            <p v-if="privacyMessage" class="text-sm text-muted-foreground">
                {{ privacyMessage }}
            </p>
        </section>

        <section class="border-t pt-6">
            <Link
                :href="logout()"
                method="post"
                as="button"
                class="rounded-md border px-3 py-2 text-sm font-medium transition hover:bg-accent"
                data-test="profile-logout-button"
            >
                Logout
            </Link>
        </section>
    </div>

    <DeleteUser />
</template>
