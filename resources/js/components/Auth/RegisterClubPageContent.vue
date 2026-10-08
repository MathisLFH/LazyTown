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
import { store as resumePayment } from '@/routes/register/payment';
import type { TeamInvitationContext } from '@/types';

interface PendingRegistration {
    club: { name: string; subdomain: string };
    admin: Record<string, string | null>;
    checkoutState: 'cancelled' | 'pending' | null;
}

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
}

const props = defineProps<{
    passwordRules: string;
    annualAccessPrice: string;
    teamInvitation?: TeamInvitationContext | null;
    pendingRegistration?: PendingRegistration | null;
}>();

const pending = props.pendingRegistration;
const registerSection = ref<number>(pending ? 3 : 0);
const createClubData = reactive<CreateClubData>({
    club: {
        name: pending?.club.name ?? '',
        subdomain: pending?.club.subdomain ?? '',
    },
    admin: {
        first_name: pending?.admin.first_name ?? '',
        last_name: pending?.admin.last_name ?? '',
        email: pending?.admin.email ?? '',
        birth_date: pending?.admin.birth_date?.slice(0, 10) ?? '',
        birth_place: pending?.admin.birth_place ?? '',
        nationality: pending?.admin.nationality ?? '',
        address: pending?.admin.address ?? '',
        postcode: pending?.admin.postcode ?? '',
        city: pending?.admin.city ?? '',
    },
    subscription: pending ? 'premium' : 'free',
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
            v-if="pendingRegistration"
            v-bind="resumePayment.form()"
            v-slot="{ processing }"
            class="flex flex-col gap-6"
        >
            <p
                v-if="pendingRegistration.checkoutState === 'cancelled'"
                class="rounded-md border border-amber-400 bg-amber-50 p-3 text-sm text-amber-950"
            >
                Die Zahlung wurde abgebrochen oder ist fehlgeschlagen. Deine
                Daten sind gespeichert, du kannst die Zahlung erneut starten.
            </p>
            <p
                v-else-if="pendingRegistration.checkoutState === 'pending'"
                class="rounded-md border p-3 text-sm"
            >
                Die Zahlung wird noch bestätigt. Aktualisiere diese Seite in
                Kürze.
            </p>
            <p class="text-sm">
                <strong>{{ createClubData.club.name }}</strong> ({{
                    createClubData.club.subdomain
                }}) – Verantwortlich:
                {{ createClubData.admin.first_name }}
                {{ createClubData.admin.last_name }},
                {{ createClubData.admin.email }}
            </p>
            <RegisterPaymentSection :price="annualAccessPrice" />
            <Button
                type="submit"
                class="w-full"
                :disabled="processing"
                data-test="resume-payment-button"
            >
                <Spinner v-if="processing" />
                Jetzt bezahlen
            </Button>
        </Form>

        <Form
            v-else
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
                    :price="annualAccessPrice"
                />
                <InputError :message="errors.subscription" />
            </div>
            <div
                v-if="createClubData.subscription === 'premium'"
                v-show="registerSection === 3"
                data-register-step="3"
            >
                <RegisterPaymentSection :price="annualAccessPrice" />
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
                {{
                    createClubData.subscription === 'premium'
                        ? 'Verein registrieren & bezahlen'
                        : 'Verein registrieren'
                }}
            </Button>
        </Form>
    </div>
</template>
