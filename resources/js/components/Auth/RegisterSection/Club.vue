<template>
    <section id="club-information" class="grid gap-6">
        <h3 class="text-lg font-medium">Vereinsname</h3>

        <RegisterFormText
            field="Name"
            type="text"
            placeholder="Ninjas in Pyjamas"
        />
    </section>
</template>

<script setup lang="ts">
import RegisterFormText from '@/components/Auth/RegisterFormText.vue';
</script>
