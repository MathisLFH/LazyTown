<template>
    <section id="club-responsible" class="grid gap-6">
        <h3 class="text-lg font-medium">Vereinsverantwortlicher</h3>
        <MemberForm />

        <slot />
    </section>
</template>

<script setup lang="ts">
import MemberForm from '@/components/Auth/MemberForm.vue';
</script>
