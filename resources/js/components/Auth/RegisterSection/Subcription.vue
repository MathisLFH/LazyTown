<template>
    <section id="subscription-information" class="space-y-5">
        <div>
            <h3 class="text-xl font-semibold tracking-tight">Tarif wählen</h3>
            <p class="mt-1 text-sm text-muted-foreground">
                Wähle den passenden Tarif für deinen Verein.
            </p>
        </div>

        <input type="hidden" name="subscription" :value="subscription" />

        <div
            class="grid gap-4 sm:grid-cols-2"
            role="group"
            aria-label="Abo wählen"
        >
            <button
                type="button"
                class="group relative cursor-pointer rounded-lg border bg-card p-5 text-left transition-all hover:-translate-y-0.5 hover:border-primary hover:shadow-md"
                :class="
                    subscription === feature.name
                        ? 'border-primary ring-2 ring-primary/20'
                        : feature.name === 'premium'
                          ? 'border-amber-400 bg-amber-50 dark:border-amber-500 dark:bg-amber-950/30'
                          : ''
                "
                v-for="feature in features"
                :key="feature.name"
                :aria-pressed="subscription === feature.name"
                @click="subscription = feature.name"
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
            </button>
        </div>
    </section>
</template>

<script setup lang="ts">
const subscription = defineModel<'free' | 'premium'>({ required: true });

const features = [
    { name: 'free', label: 'Free', price: 'Kostenlos' },
    { name: 'premium', label: 'Premium', price: '€29,99' },
] as const;
</script>
