<script setup lang="ts">
import { CalendarDays } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import { Input } from '@/components/ui/input';

const props = withDefaults(
    defineProps<{
        id: string;
        name: string;
        modelValue: string;
        required?: boolean;
        autocomplete?: string;
        disabled?: boolean;
    }>(),
    {
        required: false,
        autocomplete: 'off',
        disabled: false,
    },
);

const emit = defineEmits<{
    'update:modelValue': [value: string];
}>();

function isValidDate(year: number, month: number, day: number): boolean {
    const date = new Date(Date.UTC(year, month - 1, day));

    return (
        date.getUTCFullYear() === year &&
        date.getUTCMonth() === month - 1 &&
        date.getUTCDate() === day
    );
}

function toIsoDate(value: string): string | null {
    const germanDate = /^(\d{2})\.(\d{2})\.(\d{4})$/.exec(value);

    if (germanDate) {
        const [, day, month, year] = germanDate;

        if (isValidDate(Number(year), Number(month), Number(day))) {
            return `${year}-${month}-${day}`;
        }

        return null;
    }

    const isoDate = /^(\d{4})-(\d{2})-(\d{2})$/.exec(value);

    if (isoDate) {
        const [, year, month, day] = isoDate;

        if (isValidDate(Number(year), Number(month), Number(day))) {
            return value;
        }
    }

    return null;
}

function toGermanDate(value: string): string {
    const isoDate = /^(\d{4})-(\d{2})-(\d{2})/.exec(value);

    return isoDate ? `${isoDate[3]}.${isoDate[2]}.${isoDate[1]}` : '';
}

function formatDateInput(value: string): string {
    const digits = value.replace(/\D/g, '').slice(0, 8);

    if (digits.length > 4) {
        return `${digits.slice(0, 2)}.${digits.slice(2, 4)}.${digits.slice(4)}`;
    }

    if (digits.length > 2) {
        return `${digits.slice(0, 2)}.${digits.slice(2)}`;
    }

    return digits;
}

const displayValue = ref(toGermanDate(props.modelValue));
const isoValue = ref(props.modelValue);
const lastEmittedValue = ref(props.modelValue);
const pattern = computed(() =>
    props.required
        ? '\\d{2}\\.\\d{2}\\.\\d{4}|\\d{4}-\\d{2}-\\d{2}'
        : '(\\d{2}\\.\\d{2}\\.\\d{4}|\\d{4}-\\d{2}-\\d{2})?',
);

watch(
    () => props.modelValue,
    (value) => {
        if (value === lastEmittedValue.value) {
            return;
        }

        lastEmittedValue.value = value;
        isoValue.value = value;
        displayValue.value = toGermanDate(value);
    },
);

function updateModel(value: string): void {
    lastEmittedValue.value = toIsoDate(value) ?? '';
    isoValue.value = lastEmittedValue.value;
    emit('update:modelValue', lastEmittedValue.value);
}

function handleTextInput(event: Event): void {
    const input = event.target as HTMLInputElement;
    const digitPosition = input.value
        .slice(0, input.selectionStart ?? input.value.length)
        .replace(/\D/g, '').length;
    const formattedValue = formatDateInput(input.value);
    const normalizedDate = toIsoDate(formattedValue);
    let cursorPosition = digitPosition;

    if (formattedValue.replace(/\D/g, '').length > 2 && digitPosition >= 2) {
        cursorPosition += 1;
    }

    if (formattedValue.replace(/\D/g, '').length > 4 && digitPosition >= 4) {
        cursorPosition += 1;
    }

    input.value = formattedValue;
    displayValue.value = formattedValue;
    input.setSelectionRange(cursorPosition, cursorPosition);

    input.setCustomValidity(
        formattedValue !== '' && normalizedDate === null
            ? 'Bitte ein gültiges Datum im Format TT.MM.JJJJ eingeben.'
            : '',
    );
    updateModel(formattedValue);
}

function handleBlur(): void {
    if (displayValue.value === '') {
        return;
    }

    const normalizedDate = toIsoDate(displayValue.value);

    if (normalizedDate) {
        displayValue.value = toGermanDate(normalizedDate);
    }
}

function handlePickerChange(event: Event): void {
    const input = event.target as HTMLInputElement;

    displayValue.value = toGermanDate(input.value);
    updateModel(input.value);
}
</script>

<template>
    <div class="relative">
        <Input
            :id="id"
            v-model="displayValue"
            type="text"
            :required="required"
            :autocomplete="autocomplete"
            :disabled="disabled"
            inputmode="text"
            placeholder="TT.MM.JJJJ"
            :pattern="pattern"
            class="pr-10"
            @input="handleTextInput"
            @blur="handleBlur"
        />
        <CalendarDays
            class="pointer-events-none absolute top-1/2 right-3 size-4 -translate-y-1/2 text-muted-foreground"
            aria-hidden="true"
        />
        <input
            :id="`${id}-picker`"
            type="date"
            :value="isoValue"
            :disabled="disabled"
            :aria-label="`${id} im Kalender auswählen`"
            tabindex="-1"
            class="absolute top-0 right-0 h-full w-10 cursor-pointer opacity-0"
            @change="handlePickerChange"
        />
        <input
            type="hidden"
            :name="name"
            :value="isoValue"
            :disabled="disabled"
        />
    </div>
</template>
