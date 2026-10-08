<script setup lang="ts">
import { Form, Link } from '@inertiajs/vue3';
import { ArrowLeft, CheckCircle, CreditCard } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import { edit as editTeam } from '@/routes/teams';
import { skip, update } from '@/routes/teams/payment';

type Props = {
    team: {
        name: string;
        slug: string;
        paymentStatus: string;
        paidAt: string | null;
        amount: string;
        allowSkip: boolean;
        checkoutState: 'cancelled' | 'pending' | null;
    };
};
const props = defineProps<Props>();
</script>

<template>
    <main class="mx-auto max-w-2xl space-y-6 p-6">
        <Link
            :href="editTeam(props.team.slug).url"
            class="inline-flex items-center gap-2 text-sm text-muted-foreground hover:text-foreground"
            ><ArrowLeft class="size-4" /> Zurück zum Verein</Link
        >
        <div>
            <h1 class="text-2xl font-semibold">Verein bezahlen</h1>
            <p class="text-muted-foreground">
                {{ props.team.name }} · Jahreszugang
            </p>
        </div>
        <section
            v-if="props.team.paymentStatus === 'not_required'"
            class="flex items-center gap-3 rounded-lg border border-green-200 bg-green-50 p-5 text-green-800"
        >
            <CheckCircle class="size-5" /><span
                >Für den kostenlosen Tarif ist keine Zahlung erforderlich.</span
            >
        </section>
        <section
            v-else-if="props.team.paymentStatus === 'paid'"
            class="flex items-center gap-3 rounded-lg border border-green-200 bg-green-50 p-5 text-green-800"
        >
            <CheckCircle class="size-5" /><span
                >Der Vereinszugang ist aktiv. Zahlungsreferenz wurde
                gespeichert.</span
            >
        </section>
        <section
            v-else-if="props.team.paymentStatus === 'skipped'"
            class="flex items-center gap-3 rounded-lg border border-amber-200 bg-amber-50 p-5 text-amber-900"
        >
            <CheckCircle class="size-5" /><span
                >Die Zahlung wurde vorübergehend übersprungen.</span
            >
        </section>
        <section v-else class="rounded-lg border border-sidebar-border/70 p-6">
            <div
                v-if="props.team.checkoutState === 'cancelled'"
                class="mb-5 rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900"
                role="status"
            >
                Die Zahlung wurde abgebrochen. Du kannst es erneut versuchen.
            </div>
            <div
                v-else-if="props.team.checkoutState === 'pending'"
                class="mb-5 rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900"
                role="status"
            >
                Die Zahlung wird noch bestätigt. Bitte lade diese Seite in Kürze
                erneut.
            </div>
            <div class="mb-6 flex items-center gap-3">
                <CreditCard class="size-5" />
                <h2 class="text-lg font-semibold">Sichere Zahlung</h2>
            </div>
            <div class="mb-5 flex items-center justify-between gap-4">
                <span class="text-sm text-muted-foreground">Jahreszugang</span>
                <span class="text-lg font-semibold">{{ props.team.amount }}</span>
            </div>
            <Form v-bind="update.form(props.team.slug)" v-slot="{ processing }">
                <Button type="submit" class="w-full" :disabled="processing">
                    {{
                        processing
                            ? 'Weiterleitung zu Stripe...'
                            : 'Sicher über Stripe bezahlen'
                    }}
                </Button>
            </Form>
            <Form
                v-if="props.team.allowSkip"
                v-bind="skip.form(props.team.slug)"
                class="mt-3"
            >
                <Button type="submit" variant="outline" class="w-full">
                    Zahlung vorübergehend überspringen
                </Button>
            </Form>
            <p class="mt-4 text-xs text-muted-foreground">
                Die Zahlung wird sicher über Stripe abgewickelt. Diese
                Projektseite nutzt ausschließlich den Testmodus; es werden
                keine echten Zahlungen ausgelöst.
            </p>
        </section>
    </main>
</template>
