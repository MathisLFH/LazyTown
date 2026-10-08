<template>
    <RegisterFormText
        v-model="admin.first_name"
        field="Vorname"
        name="first_name"
        type="text"
        placeholder="Max"
        autocomplete="given-name"
        :error="errors?.first_name"
    />
    <RegisterFormText
        v-model="admin.last_name"
        field="Nachname"
        name="last_name"
        type="text"
        placeholder="Mustermann"
        autocomplete="family-name"
        :error="errors?.last_name"
    />
    <RegisterFormText
        v-model="admin.email"
        field="E-Mail-Adresse"
        name="email"
        type="email"
        placeholder="max@example.de"
        autocomplete="email"
        :error="errors?.email"
    />

    <RegisterFormText
        v-model="admin.birth_date"
        field="Geburtsdatum"
        name="birth_date"
        type="date"
        placeholder=""
        autocomplete="bday"
        :required="false"
        :error="errors?.birth_date"
    />

    <RegisterFormText
        v-model="admin.birth_place"
        field="Geburtsort"
        name="birth_place"
        type="text"
        placeholder="Berlin"
        :required="false"
        :error="errors?.birth_place"
    />

    <RegisterFormText
        v-model="admin.nationality"
        field="Nationalität"
        name="nationality"
        type="text"
        placeholder="Deutsch"
        autocomplete="country-name"
        :required="false"
        :error="errors?.nationality"
    />

    <RegisterFormText
        v-model="admin.address"
        field="Anschrift"
        name="address"
        type="text"
        placeholder="Musterstraße 1"
        autocomplete="street-address"
        :required="false"
        :error="errors?.address"
    />

    <RegisterFormText
        v-model="admin.postcode"
        field="Postleitzahl"
        name="postcode"
        type="text"
        placeholder="01234"
        autocomplete="postal-code"
        :required="false"
        :error="errors?.postcode"
    />
    <RegisterFormText
        v-model="admin.city"
        field="Ort"
        name="city"
        type="text"
        placeholder="Musterstadt"
        autocomplete="address-level2"
        :required="false"
        :error="errors?.city"
    />
</template>

<script setup lang="ts">
import RegisterFormText from '@/components/Auth/RegisterFormText.vue';

defineProps<{ errors?: Record<string, string> }>();

const admin = defineModel<{
    first_name: string;
    last_name: string;
    email: string;
    birth_date: string;
    birth_place: string;
    nationality: string;
    address: string;
    postcode: string;
    city: string;
}>({ required: true });
</script>
