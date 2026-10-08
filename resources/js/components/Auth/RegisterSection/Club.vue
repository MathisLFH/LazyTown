<template>
    <section id="club-information" class="grid gap-6">
        <h3 class="text-lg font-medium">Vereinsname</h3>

        <RegisterFormText
            v-model="club.name"
            field="Vereinsname"
            name="club_name"
            type="text"
            placeholder="Ninjas in Pyjamas"
            autocomplete="organization"
            :error="errors?.club_name"
        />
        <RegisterFormText
            v-model="club.subdomain"
            field="Subdomain"
            name="subdomain"
            type="text"
            placeholder="ninjas-in-pyjamas"
            autocomplete="off"
            :error="errors?.subdomain"
        />
    </section>
</template>

<script setup lang="ts">
import RegisterFormText from '@/components/Auth/RegisterFormText.vue';

defineProps<{ errors?: Record<string, string> }>();

const club = defineModel<{ name: string; subdomain: string }>({
    required: true,
});
</script>
