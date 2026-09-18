<script setup lang="ts">
import { ref } from 'vue';
import { Form } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import TeamInvitationAlert from '@/components/TeamInvitationAlert.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { store } from '@/routes/register';
import type { TeamInvitationContext } from '@/types';
import RegisterFormText from '@/components/Auth/RegisterFormText.vue';

defineProps<{
    passwordRules: string;
    teamInvitation?: TeamInvitationContext | null;
}>();

const registerSection = ref<number>(0);
</script>

<template>
    <TeamInvitationAlert
        v-if="teamInvitation"
        :invitation="teamInvitation"
        action="Register"
    />
    <h2 class="text-center">
        <h2 class="text-2xl font-bold">Verein registrieren</h2>
        <p class="text-muted-foreground">
            Registriere deinen Verein, um die Funktionen von LazyTown zu nutzen.
        </p>
    </h2>

    <Form
        v-bind="store.form()"
        :reset-on-success="['password', 'password_confirmation']"
        v-slot="{ errors, processing }"
        class="flex flex-col gap-6"
    >
        <section
            id="club-information"
            class="grid gap-6"
            v-show="registerSection === 0"
        >
            <h3 class="text-lg font-medium">Vereinsname</h3>

            <RegisterFormText
                field="Name"
                type="text"
                placeholder="Ninjas in Pyjamas"
            />
        </section>
        <section
            id="club-responsible"
            class="grid gap-6"
            v-show="registerSection === 1"
        >
            <h3 class="text-lg font-medium">Vereinsverantwortlicher</h3>
            <RegisterFormText field="Vorname" type="text" placeholder="Max" />
            <!-- :error-message="errors.firstName" -->
            <RegisterFormText
                field="Nachname"
                type="text"
                placeholder="Mustermann"
            />
            <!-- :error-message="errors.lastName" -->

            <RegisterFormText
                field="Geburtsdatum"
                type="date"
                placeholder="dd.mm.yyyy"
            />
            <!-- :error-message="errors.dateOfBirth" -->
            <RegisterFormText
                field="Geburtsort"
                type="text"
                placeholder="Musterland"
            />
            <!-- :error-message="errors.placeOfBirth" -->
            <RegisterFormText
                field="Adresse"
                type="text"
                placeholder="Musterstraße 1"
            />

            <RegisterFormText
                field="Ort"
                type="text"
                placeholder="Musterstadt"
            />

            <RegisterFormText field="PLZ" type="number" placeholder="12345" />

            <div class="grid gap-2">
                <Label for="password">Password</Label>
                <PasswordInput
                    id="password"
                    required
                    :tabindex="3"
                    autocomplete="new-password"
                    name="password"
                    placeholder="Password"
                    :passwordrules="passwordRules"
                />
                <InputError />
            </div>

            <div class="grid gap-2">
                <Label for="password_confirmation">Confirm password</Label>
                <PasswordInput
                    id="password_confirmation"
                    required
                    :tabindex="4"
                    autocomplete="new-password"
                    name="password_confirmation"
                    placeholder="Confirm password"
                    :passwordrules="passwordRules"
                />
                <InputError :message="errors.password_confirmation" />
            </div>
        </section>
        <section
            id="subscription-information"
            class=""
            v-show="registerSection === 2"
        >
            <h3 class="text-lg font-medium">Feature wählen</h3>
            <div class="gap-2">
                <article
                    class="w-50 rounded-lg border p-4"
                    v-for="range in 5"
                    :key="range"
                >
                    <div
                        class="flex flex-col items-center justify-center gap-2"
                    >
                        <h4 class="text-lg font-medium">Basis</h4>
                        <p class="text-muted-foreground">Kostenlos</p>
                    </div>
                </article>
            </div>
        </section>
        <section id="button-section">
            <div class="flex justify-between">
                <Button
                    type="button"
                    class="w-1/3"
                    tabindex="5"
                    @click="registerSection--"
                    :disabled="registerSection === 0"
                >
                    Zurück
                </Button>
                <Button
                    type="button"
                    class="w-1/3"
                    tabindex="5"
                    :disabled="registerSection === 3"
                    @click="registerSection++"
                >
                    Weiter
                </Button>
            </div>
        </section>
        <Button
            v-if="registerSection === 2"
            type="submit"
            class="w-full"
            tabindex="5"
            :disabled="processing"
            data-test="register-user-button"
        >
            <Spinner v-if="processing" />
            Erstellen
        </Button>
    </Form>
</template>

<style scoped>
article {
    aspect-ratio: 7/5;
}
</style>
