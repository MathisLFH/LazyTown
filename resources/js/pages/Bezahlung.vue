<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import PaymentPageContent from '@/components/Payment/PaymentPageContent.vue';

type Props = { team: { name: string; slug: string; paymentStatus: string; paidAt: string | null } };
defineProps<Props>();
</script>

<template>
    <Head title="Bezahlung für das Tool" />

    <PaymentPageContent :team="team" />
</template>
