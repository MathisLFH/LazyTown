<script setup lang="ts">
import { reactive, ref } from 'vue';
import { Form } from '@inertiajs/vue3';
import TeamInvitationAlert from '@/components/TeamInvitationAlert.vue';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { store } from '@/routes/register';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import RegisterClubSection from '@/components/Auth/RegisterSection/Club.vue';
import RegisterAdminSection from '@/components/Auth/RegisterSection/Admin.vue';
import RegisterSubscriptionSection from '@/components/Auth/RegisterSection/Subcription.vue';
import RegisterPaymentSection from '@/components/Auth/RegisterSection/Payment.vue';
import type { TeamInvitationContext } from '@/types';

interface CreateClubData {
    club: {
        name: string;
        subdomain: string;
    };
    admin: {
        firstname: string;
        lastname: string;
        email: string;
        birthdate: Date | null;
        birthplace: string;
        nationality: string;
        adresse: string;
        plz: number | null;
        city: string;
        password: string;
        password_confirmation: string;
    };
    subscriptionmodel: 'Free' | 'Premium';
    paymenttype: 'Paypal' | 'Creditcard';
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
        firstname: '',
        lastname: '',
        email: '',
        birthdate: null,
        birthplace: '',
        nationality: '',
        adresse: '',
        plz: null,
        city: '',
        password: '',
        password_confirmation: '',
    },
    subscriptionmodel: 'Free',
    paymenttype: 'Paypal',
});
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
        <RegisterClubSection
            v-show="registerSection === 0"
            v-model="createClubData.club"
        />
        <RegisterAdminSection
            v-show="registerSection === 1"
            v-model="createClubData.admin"
        >
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
                <InputError :message="errors.password" />
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
        </RegisterAdminSection>

        <RegisterSubscriptionSection
            v-show="registerSection === 2"
            v-model="createClubData.subscriptionmodel"
        />
        <RegisterPaymentSection
            v-show="registerSection === 3"
            v-model="createClubData.paymenttype"
        />

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
