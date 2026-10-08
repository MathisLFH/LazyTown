<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { computed, reactive, ref } from 'vue';
import RegisterAdminSection from '@/components/Auth/RegisterSection/Admin.vue';
import RegisterClubSection from '@/components/Auth/RegisterSection/Club.vue';
import RegisterPaymentSection from '@/components/Auth/RegisterSection/Payment.vue';
import RegisterSubscriptionSection from '@/components/Auth/RegisterSection/Subcription.vue';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import TeamInvitationAlert from '@/components/TeamInvitationAlert.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { store } from '@/routes/register';
import type { TeamInvitationContext } from '@/types';

interface CreateClubData {
    club: {
        name: string;
        subdomain: string;
    };
    admin: {
        first_name: string;
        last_name: string;
        email: string;
        birth_date: string;
        birth_place: string;
        nationality: string;
        address: string;
        postcode: string;
        city: string;
    };
    subscription: 'free' | 'premium';
    paymentMethod: 'paypal' | 'card';
}

defineProps<{
    passwordRules: string;
    teamInvitation?: TeamInvitationContext | null;
}>();

const registerSection = ref<number>(0);
const createClubData = reactive<CreateClubData>({
    club: {
        name: '',
        subdomain: '',
    },
    admin: {
        first_name: '',
        last_name: '',
        email: '',
        birth_date: '',
        birth_place: '',
        nationality: '',
        address: '',
        postcode: '',
        city: '',
    },
    subscription: 'free',
    paymentMethod: 'paypal',
});

const steps = computed(() =>
    createClubData.subscription === 'free'
        ? ['Verein', 'Verantwortlicher', 'Abo']
        : ['Verein', 'Verantwortlicher', 'Abo', 'Zahlungsart'],
);

function goToNextStep(event: MouseEvent): void {
    const button = event.currentTarget as HTMLButtonElement;
    const currentStep = button.form?.querySelector<HTMLElement>(
        `[data-register-step="${registerSection.value}"]`,
    );
    const invalidInput =
        currentStep?.querySelector<HTMLInputElement>(':invalid');

    if (invalidInput) {
        invalidInput.reportValidity();

        return;
    }

    registerSection.value = Math.min(
        registerSection.value + 1,
        steps.value.length - 1,
    );
}

function handleRegistrationErrors(errors: Record<string, string>): void {
    if ('club_name' in errors || 'subdomain' in errors) {
        registerSection.value = 0;
    } else if (
        [
            'first_name',
            'last_name',
            'email',
            'birth_date',
            'birth_place',
            'nationality',
            'address',
            'postcode',
            'city',
            'password',
            'password_confirmation',
        ].some((field) => field in errors)
    ) {
        registerSection.value = 1;
    } else if ('subscription' in errors) {
        registerSection.value = 2;
    }
}
</script>

<template>
    <div class="grid gap-6">
        <TeamInvitationAlert
            v-if="teamInvitation"
            :invitation="teamInvitation"
            action="Register"
        />
        <header class="text-center">
            <h2 class="text-2xl font-bold">Verein registrieren</h2>
            <p class="text-muted-foreground">
                Registriere deinen Verein, um die Funktionen von LazyTown zu
                nutzen.
            </p>
        </header>

        <Form
            v-bind="store.form()"
            :reset-on-success="['password', 'password_confirmation']"
            @error="handleRegistrationErrors"
            v-slot="{ errors, processing }"
            class="flex flex-col gap-6"
        >
            <ol
                class="grid gap-2"
                :class="steps.length === 3 ? 'grid-cols-3' : 'grid-cols-4'"
                aria-label="Registrierungsschritte"
            >
                <li
                    v-for="(step, index) in steps"
                    :key="step"
                    class="border-b-2 pb-2 text-center text-sm"
                    :class="
                        registerSection === index
                            ? 'border-primary font-medium'
                            : 'border-muted text-muted-foreground'
                    "
                    :aria-current="
                        registerSection === index ? 'step' : undefined
                    "
                >
                    {{ step }}
                </li>
            </ol>

            <div v-show="registerSection === 0" data-register-step="0">
                <RegisterClubSection
                    v-model="createClubData.club"
                    :errors="errors"
                />
            </div>
            <div v-show="registerSection === 1" data-register-step="1">
                <RegisterAdminSection
                    v-model="createClubData.admin"
                    :errors="errors"
                >
                    <div class="grid gap-2">
                        <Label for="password">Passwort</Label>
                        <PasswordInput
                            id="password"
                            required
                            :tabindex="3"
                            autocomplete="new-password"
                            name="password"
                            placeholder="Passwort"
                            :passwordrules="passwordRules"
                        />
                        <InputError :message="errors.password" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="password_confirmation"
                            >Passwort bestätigen</Label
                        >
                        <PasswordInput
                            id="password_confirmation"
                            required
                            :tabindex="4"
                            autocomplete="new-password"
                            name="password_confirmation"
                            placeholder="Passwort bestätigen"
                            :passwordrules="passwordRules"
                        />
                        <InputError :message="errors.password_confirmation" />
                    </div>
                </RegisterAdminSection>
            </div>

            <div v-show="registerSection === 2" data-register-step="2">
                <RegisterSubscriptionSection
                    v-model="createClubData.subscription"
                />
                <InputError :message="errors.subscription" />
            </div>
            <div
                v-if="createClubData.subscription === 'premium'"
                v-show="registerSection === 3"
                data-register-step="3"
            >
                <RegisterPaymentSection
                    v-model="createClubData.paymentMethod"
                />
            </div>

            <section id="button-section">
                <div class="flex justify-between">
                    <Button
                        type="button"
                        class="w-1/3"
                        tabindex="5"
                        @click="
                            registerSection = Math.max(registerSection - 1, 0)
                        "
                        :disabled="registerSection === 0"
                    >
                        Zurück
                    </Button>
                    <Button
                        type="button"
                        class="w-1/3"
                        tabindex="5"
                        :disabled="registerSection === steps.length - 1"
                        @click="goToNextStep"
                    >
                        Weiter
                    </Button>
                </div>
            </section>
            <Button
                v-if="registerSection === steps.length - 1"
                type="submit"
                class="w-full"
                tabindex="5"
                :disabled="processing"
                data-test="register-user-button"
            >
                <Spinner v-if="processing" />
                Verein registrieren
            </Button>
        </Form>
    </div>
</template>
