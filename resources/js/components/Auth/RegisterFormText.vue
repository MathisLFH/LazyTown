<template>
    <div class="grid gap-2">
        <Label :for="name">{{ field }}</Label>
        <Input
            :id="name"
            :type="type"
            v-model="modelValue"
            :required="required ?? true"
            :autocomplete="autocomplete ?? 'off'"
            :name="name"
            :placeholder="placeholder"
        />
        <InputError :message="error" />
    </div>
</template>

<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

interface Props {
    field: string;
    name: string;
    placeholder: string;
    type: string;
    required?: boolean;
    autocomplete?: string;
    error?: string;
}

const modelValue = defineModel<string | number>();

defineProps<Props>();
</script>
