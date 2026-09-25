<template>
    <section class="payment-section">
        <div class="payment-section__header">
            <h2>Payment method</h2>
            <p>Choose how you would like to pay for your subscription.</p>
        </div>

        <div
            class="payment-methods"
            role="tablist"
            aria-label="Payment methods"
        >
            <button
                type="button"
                class="payment-method"
                :class="{ 'payment-method--active': method === 'paypal' }"
                :aria-selected="method === 'paypal'"
                role="tab"
                @click="method = 'paypal'"
            >
                <strong>PayPal</strong>
                <span>Pay securely with PayPal</span>
            </button>
            <button
                type="button"
                class="payment-method"
                :class="{ 'payment-method--active': method === 'card' }"
                :aria-selected="method === 'card'"
                role="tab"
                @click="method = 'card'"
            >
                <strong>Credit card</strong>
                <span>Visa, Mastercard or Amex</span>
            </button>
        </div>

        <div v-if="method === 'paypal'" class="paypal-payment" role="tabpanel">
            <div class="paypal-payment__logo">Pay<span>Pal</span></div>
            <p>
                You will be redirected to PayPal to complete your payment
                securely.
            </p>
        </div>

        <div v-else class="card-payment" role="tabpanel">
            <label>
                Cardholder name
                <input
                    v-model.trim="card.name"
                    type="text"
                    autocomplete="cc-name"
                    required
                />
            </label>
            <label>
                Card number
                <input
                    v-model="card.number"
                    type="text"
                    inputmode="numeric"
                    autocomplete="cc-number"
                    placeholder="1234 5678 9012 3456"
                    maxlength="19"
                    required
                />
            </label>
            <div class="card-payment__row">
                <label>
                    Expiry date
                    <input
                        v-model="card.expiry"
                        type="text"
                        inputmode="numeric"
                        autocomplete="cc-exp"
                        placeholder="MM/YY"
                        maxlength="5"
                        required
                    />
                </label>
                <label>
                    CVC
                    <input
                        v-model="card.cvc"
                        type="text"
                        inputmode="numeric"
                        autocomplete="cc-csc"
                        placeholder="123"
                        maxlength="4"
                        required
                    />
                </label>
            </div>
        </div>

        <button class="payment-submit" type="submit">
            {{ method === 'paypal' ? 'Continue with PayPal' : 'Pay securely' }}
        </button>
    </section>
</template>

<script setup lang="ts">
import { ref, reactive } from 'vue';

interface CreditCard {
    name: string;
    number: string;
    expiry: string;
    cvc: string;
}

const method = ref<string>('paypal');
const card = reactive<CreditCard>({
    name: '',
    number: '',
    expiry: '',
    cvc: '',
});

const submit = () => {
    // Emit the submit event with the selected payment method and card details
    const paymentData = {
        method: method.value,
        ...(method.value === 'card' ? { card: { ...card } } : {}),
    };
    // You can emit this data to the parent component or handle it as needed
    console.log('Payment submitted:', paymentData);
};
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
.payment-section h2 {
    margin: 0 0 8px;
    font-size: 1.5rem;
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
    border-radius: 8px;
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
.payment-form label {
    display: block;
    margin-bottom: 16px;
    font-weight: 600;
    font-size: 0.875rem;
}
.payment-form input {
    box-sizing: border-box;
    width: 100%;
    margin-top: 6px;
    padding: 11px 12px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font: inherit;
}
.card-payment__row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
}
.paypal-payment {
    padding: 28px 20px;
    margin-bottom: 24px;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    text-align: center;
}
.paypal-payment__logo {
    margin-bottom: 12px;
    color: #003087;
    font-size: 1.5rem;
    font-weight: 700;
}
.paypal-payment__logo span {
    color: #009cde;
}
.payment-submit {
    width: 100%;
    padding: 13px 18px;
    border: 0;
    border-radius: 6px;
    background: #2563eb;
    color: #fff;
    font-weight: 600;
    cursor: pointer;
}
@media (max-width: 480px) {
    .payment-methods,
    .card-payment__row {
        grid-template-columns: 1fr;
    }
}
</style>
