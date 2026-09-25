<template>
    <section id="subscription-information" class="space-y-5">
        <div>
            <h3 class="text-xl font-semibold tracking-tight">Feature wählen</h3>
            <p class="mt-1 text-sm text-muted-foreground">
                Wähle die Funktionen, die zu deinem Turnier passen.
            </p>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <article
                class="group relative cursor-pointer rounded-xl border bg-card p-5 transition-all hover:-translate-y-0.5 hover:border-primary hover:shadow-md"
                :class="
                    selectedFeatures.includes(feature.name)
                        ? 'border-primary ring-2 ring-primary/20'
                        : feature.name === 'premium'
                          ? 'border-amber-400 bg-amber-50 dark:border-amber-500 dark:bg-amber-950/30'
                          : ''
                "
                v-for="feature in features"
                :key="feature.name"
                role="radio"
                :aria-checked="selectedFeatures.includes(feature.name)"
                tabindex="0"
                @click="toggleFeature(feature.name)"
                @keydown.enter.prevent="toggleFeature(feature.name)"
                @keydown.space.prevent="toggleFeature(feature.name)"
            >
                <div class="flex flex-col items-center justify-between gap-4">
                    <div>
                        <h4 class="font-semibold">{{ feature.label }}</h4>
                    </div>
                    <span
                        :class="
                            feature.name === 'premium'
                                ? 'rounded-full bg-amber-400 px-3 py-1 text-sm font-medium whitespace-nowrap text-amber-950'
                                : 'rounded-full bg-muted px-3 py-1 text-sm font-medium whitespace-nowrap'
                        "
                    >
                        {{ feature.price }}
                    </span>
                </div>
            </article>
        </div>
    </section>
</template>

<script setup lang="ts">
import { ref } from 'vue';

const features = [
    { name: 'free', label: 'Free', price: 'Kostenlos' },
    { name: 'premium', label: 'Premium', price: '€29,99' },
];

const selectedFeatures = ref<string[]>([]);

const toggleFeature = (featureName: string) => {
    if (featureName === 'free') {
        selectedFeatures.value = selectedFeatures.value.includes('free')
            ? []
            : ['free'];
        return;
    }

    selectedFeatures.value = selectedFeatures.value.filter(
        (name) => name !== 'free',
    );

    if (selectedFeatures.value.includes(featureName)) {
        selectedFeatures.value = selectedFeatures.value.filter(
            (name) => name !== featureName,
        );
    } else {
        selectedFeatures.value.push(featureName);
    }
};
</script>
