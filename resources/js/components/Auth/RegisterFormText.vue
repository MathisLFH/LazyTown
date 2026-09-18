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
        />
        <!-- <InputError :message="errors.name" /> -->
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
}

const props = defineProps<Props>();
</script>
