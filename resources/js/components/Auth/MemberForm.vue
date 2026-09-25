<template>
    <RegisterFormText field="Vorname" type="text" placeholder="Max" />
    <RegisterFormText field="Nachname" type="text" placeholder="Mustermann" />

    <RegisterFormText
        field="Geburtsdatum"
        type="date"
        placeholder="dd.mm.yyyy"
    />

    <RegisterFormText field="Geburtsort" type="text" placeholder="Musterland" />

    <RegisterFormText field="Nationalität" type="text" placeholder="Deutsch" />

    <RegisterFormText
        field="Adresse"
        type="text"
        placeholder="Musterstraße 1"
    />

    <div class="grid gap-2">
        <Label for="plz">PLZ</Label>
        <Input
            id="plz"
            type="number"
            required
            autofocus
            :tabindex="1"
            autocomplete="plz"
            v-model="data.plz"
            placeholder="12345"
            @blur="updateCity"
        />
    </div>
    <RegisterFormText
        field="Ort"
        type="text"
        placeholder="Musterstadt"
        disabled
    />
</template>

<script setup lang="ts">
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import RegisterFormText from '@/components/Auth/RegisterFormText.vue';
import { reactive } from 'vue';

const data = reactive({
    firstName: '',
    lastName: '',
    dateOfBirth: '',
    placeOfBirth: '',
    nationality: '',
    address: '',
    plz: '',
    city: '',
});

const updateCity = async () => {
    const response = await fetch(
        `https://openplzapi.org/de/Localities?postalCode=${data.plz}`,
    );

    if (response.ok) {
        const result = await response.json();
        data.city = result.localities[0].name;
    } else {
        data.city = '';
    }

    console.log(data.plz, data.city);
};
</script>
