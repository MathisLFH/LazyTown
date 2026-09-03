<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, CheckCircle, CreditCard } from '@lucide/vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { edit } from '@/routes/teams/payment';
import { update, skip } from '@/routes/teams/payment';

type Props = { team: { name: string; slug: string; paymentStatus: string; paidAt: string | null } };
const props = defineProps<Props>();
</script>

<template>
    <Head title="Bezahlung für das Tool" />

    <main class="mx-auto max-w-2xl space-y-6 p-6">
        <Link :href="edit(props.team.slug).url" class="inline-flex items-center gap-2 text-sm text-muted-foreground hover:text-foreground"><ArrowLeft class="size-4" /> Zurück zum Verein</Link>
        <div><h1 class="text-2xl font-semibold">Verein bezahlen</h1><p class="text-muted-foreground">{{ props.team.name }} · Jahreszugang</p></div>
        <section v-if="props.team.paymentStatus !== 'pending'" class="flex items-center gap-3 rounded-lg border border-green-200 bg-green-50 p-5 text-green-800">
            <CheckCircle class="size-5" /><span>Der Vereinszugang ist aktiv. Zahlungsreferenz wurde gespeichert.</span>
        </section>
        <section v-else class="rounded-lg border border-sidebar-border/70 p-6">
            <div class="mb-6 flex items-center gap-3"><CreditCard class="size-5" /><h2 class="text-lg font-semibold">Zahlungsdaten</h2></div>
            <Form v-bind="update.form(props.team.slug)" class="grid gap-5" v-slot="{ errors, processing }">
                <div class="grid gap-2"><Label for="cardholder">Name auf der Karte</Label><Input id="cardholder" name="cardholder" autocomplete="cc-name" required /><InputError :message="errors.cardholder" /></div>
                <div class="grid gap-2"><Label for="card_number">Kartennummer</Label><Input id="card_number" name="card_number" inputmode="numeric" autocomplete="cc-number" required /><InputError :message="errors.card_number" /></div>
                <div class="grid grid-cols-2 gap-4"><div class="grid gap-2"><Label for="expiry">Gültig bis</Label><Input id="expiry" name="expiry" placeholder="MM/JJ" autocomplete="cc-exp" required /><InputError :message="errors.expiry" /></div><div class="grid gap-2"><Label for="cvc">CVC</Label><Input id="cvc" name="cvc" inputmode="numeric" autocomplete="cc-csc" required /><InputError :message="errors.cvc" /></div></div>
                <Button type="submit" :disabled="processing">{{ processing ? 'Wird verarbeitet...' : 'Zahlung abschließen' }}</Button>
            </Form>
            <Form v-bind="skip.form(props.team.slug)" class="mt-3"><Button type="submit" variant="outline" class="w-full">Zahlung vorübergehend überspringen</Button></Form>
            <p class="mt-4 text-xs text-muted-foreground">Dies ist eine Test-Zahlungsseite. Es werden keine echten Zahlungen ausgelöst.</p>
        </section>
    </main>
</template>
