<template>
    <section class="payment-section">
        <div class="payment-section__header">
            <h3>Zahlungsart</h3>
            <p>Einmalige Zahlung für deinen Premium-Jahreszugang.</p>
        </div>

        <div class="payment-method payment-method--active">
            <div class="payment-method__details">
                <strong>Sicher bezahlen mit Stripe Checkout</strong>
                <span>
                    Stripe zeigt dir im nächsten Schritt die verfügbaren
                    Zahlungsmethoden an. Deine Zahlungsdaten gibst du direkt
                    bei Stripe ein.
                </span>
            </div>
            <strong class="payment-method__price">29,99 €</strong>
        </div>

        <div class="payment-pseudo">
            Einmalzahlung für 12 Monate, keine automatische Verlängerung.
            Studienprojekt: Stripe-Testmodus, es wird kein echtes Geld
            eingezogen.
        </div>
    </section>
</template>

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
.payment-method {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 16px;
    padding: 16px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    background: #fff;
}
.payment-method--active {
    border-color: #2563eb;
    box-shadow: 0 0 0 1px #2563eb;
}
.payment-method__details {
    min-width: 0;
}
.payment-method__details strong,
.payment-method__details span {
    display: block;
}
.payment-method__details span {
    margin-top: 4px;
    color: #6b7280;
    font-size: 0.875rem;
}
.payment-method__price {
    flex-shrink: 0;
    white-space: nowrap;
}
.payment-pseudo {
    padding: 16px;
    border: 1px dashed #9ca3af;
    border-radius: 6px;
    color: #4b5563;
    font-size: 0.875rem;
}
</style>
