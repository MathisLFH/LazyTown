<template>
    <section id="club-responsible" class="grid gap-6">
        <h3 class="text-lg font-medium">Vereinsverantwortlicher</h3>
        <MemberForm v-model="admin" :errors="errors" />

        <slot />
    </section>
</template>

<script setup lang="ts">
import MemberForm from '@/components/Auth/MemberForm.vue';

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
