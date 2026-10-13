<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { reactive } from 'vue';
import MemberForm from '@/components/Auth/MemberForm.vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { update } from '@/routes/member/profile-completion';

type MemberProfile = {
    first_name: string | null;
    last_name: string | null;
    email: string;
    birth_date: string | null;
    birth_place: string | null;
    nationality: string | null;
    address: string | null;
    postcode: string | null;
    city: string | null;
};

type ProfileForm = {
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

const props = defineProps<{ member: MemberProfile }>();
const profile = reactive<ProfileForm>({
    first_name: props.member.first_name ?? '',
    last_name: props.member.last_name ?? '',
    email: props.member.email,
    birth_date: props.member.birth_date ?? '',
    birth_place: props.member.birth_place ?? '',
    nationality: props.member.nationality ?? '',
    address: props.member.address ?? '',
    postcode: props.member.postcode ?? '',
    city: props.member.city ?? '',
});

const form = useForm({
    ...profile,
    password: '',
    password_confirmation: '',
});

function submit(): void {
    Object.assign(form, profile);
    form.patch(update().url, {
        onSuccess: () => form.reset('password', 'password_confirmation'),
    });
}
</script>

<template>
    <Head title="Profil vervollständigen" />

    <main class="mx-auto grid w-full max-w-2xl gap-6 px-4 py-8 sm:px-6">
        <Heading
            variant="small"
            title="Profil vervollständigen"
            description="Bitte ergänze deine Angaben und lege ein neues Passwort fest."
        />

        <form class="grid gap-6" @submit.prevent="submit">
            <MemberForm v-model="profile" :errors="form.errors" />

            <section class="grid gap-4 border-t pt-6">
                <h2 class="font-medium">Neues Passwort</h2>
                <div class="grid gap-2">
                    <Label for="new-password">Passwort</Label>
                    <PasswordInput
                        id="new-password"
                        v-model="form.password"
                        name="password"
                        autocomplete="new-password"
                        required
                    />
                    <InputError :message="form.errors.password" />
                </div>
                <div class="grid gap-2">
                    <Label for="new-password-confirmation">
                        Passwort wiederholen
                    </Label>
                    <PasswordInput
                        id="new-password-confirmation"
                        v-model="form.password_confirmation"
                        name="password_confirmation"
                        autocomplete="new-password"
                        required
                    />
                    <InputError :message="form.errors.password_confirmation" />
                </div>
            </section>

            <Button type="submit" :disabled="form.processing">
                {{ form.processing ? 'Wird gespeichert...' : 'Speichern' }}
            </Button>
        </form>
    </main>
</template>
