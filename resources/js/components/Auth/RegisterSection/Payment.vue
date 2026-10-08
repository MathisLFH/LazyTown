<template>
    <section class="payment-section">
        <div class="payment-section__header">
            <h3>Zahlungsart</h3>
            <p>Die Zahlungsabwicklung ist noch nicht verfügbar.</p>
        </div>

        <div
            class="payment-methods"
            role="group"
            aria-label="Zahlungsart auswählen"
        >
            <button
                type="button"
                class="payment-method"
                :class="{ 'payment-method--active': method === 'paypal' }"
                :aria-pressed="method === 'paypal'"
                @click="method = 'paypal'"
            >
                <strong>PayPal</strong>
                <span>Platzhalter</span>
            </button>
            <button
                type="button"
                class="payment-method"
                :class="{ 'payment-method--active': method === 'card' }"
                :aria-pressed="method === 'card'"
                @click="method = 'card'"
            >
                <strong>Kreditkarte</strong>
                <span>Platzhalter</span>
            </button>
        </div>

        <div class="payment-pseudo" aria-live="polite">
            {{ method === 'paypal' ? 'PayPal' : 'Kreditkarte' }} ist ausgewählt.
            Diese Auswahl wird nicht gespeichert und löst keine Zahlung aus.
        </div>
    </section>
</template>

<script setup lang="ts">
const method = defineModel<'paypal' | 'card'>({ required: true });
</script>

<style scoped>
.payment-section {
    max-width: 560px;
    margin: 0 auto;
    color: #1f2937;
}
.payment-section__header {
    margin-bottom: 24px;
}
.payment-section h3 {
    margin: 0 0 8px;
    font-size: 1.25rem;
    font-weight: 600;
}
.payment-section p {
    margin: 0;
    color: #6b7280;
}
.payment-methods {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
    margin-bottom: 24px;
}
.payment-method {
    padding: 16px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    background: #fff;
    text-align: left;
    cursor: pointer;
}
.payment-method--active {
    border-color: #2563eb;
    box-shadow: 0 0 0 1px #2563eb;
}
.payment-method strong,
.payment-method span {
    display: block;
}
.payment-method span {
    margin-top: 4px;
    color: #6b7280;
    font-size: 0.875rem;
}
.payment-pseudo {
    padding: 16px;
    border: 1px dashed #9ca3af;
    border-radius: 6px;
    color: #4b5563;
    font-size: 0.875rem;
}
@media (max-width: 480px) {
    .payment-methods {
        grid-template-columns: 1fr;
    }
}
</style>
