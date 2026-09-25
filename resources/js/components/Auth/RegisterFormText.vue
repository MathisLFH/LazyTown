<template>
    <div class="grid gap-2">
        <Label :for="field">{{ field }}</Label>
        <Input
            :id="field"
            :type="type"
            required
            autofocus
            :tabindex="1"
            :autocomplete="field"
            :name="field"
            :placeholder="placeholder"
            :disabled="disabled"
            :value="modelValue"
        />
    </div>
</template>

<script setup lang="ts">
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
// import InputError from '@/components/InputError.vue';

interface Props {
    field: string;
    placeholder: string;
    type: string;
    disabled?: boolean;
    modelValue?: string | number | null;
}

const modelValue = defineModel<string | number | null>();

const props = defineProps<Props>();
</script>
