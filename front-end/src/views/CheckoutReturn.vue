<template>
  <div class="max-w-lg mx-auto px-4 py-16">
    <div class="bg-white rounded-2xl shadow p-8 text-center space-y-4">
      <!-- Cancelled on Stripe's page -->
      <template v-if="cancelled">
        <div class="text-5xl">🛒</div>
        <h1 class="font-display text-xl font-bold text-gray-800">Payment cancelled</h1>
        <p class="text-sm text-gray-500">
          No charge was made. Your order
          <span class="font-semibold text-gray-700">{{ orderNumber }}</span>
          is saved but still unpaid.
        </p>
        <button
          class="w-full gradient-primary text-white py-3 rounded-lg font-semibold hover:opacity-90 transition disabled:opacity-50"
          :disabled="paying"
          @click="payNow"
        >
          {{ paying ? "Redirecting..." : "Pay Now" }}
        </button>
        <p v-if="payError" class="text-sm text-red-500">{{ payError }}</p>
        <router-link to="/account/orders" class="block text-sm text-orange-500 hover:underline">
          View My Orders
        </router-link>
      </template>

      <!-- Checking payment after returning from Stripe -->
      <template v-else-if="state === 'checking'">
        <div class="text-5xl animate-pulse">💳</div>
        <h1 class="font-display text-xl font-bold text-gray-800">Confirming your payment…</h1>
        <p class="text-sm text-gray-500">
          Hang tight — we're waiting for Stripe to confirm order
          <span class="font-semibold text-gray-700">{{ orderNumber }}</span>.
        </p>
      </template>

      <template v-else-if="state === 'paid'">
        <div class="text-5xl">✅</div>
        <h1 class="font-display text-xl font-bold text-gray-800">Payment successful!</h1>
        <p class="text-sm text-gray-500">
          Thank you! Your order is confirmed and a confirmation email is on its way.
        </p>
        <div class="bg-gray-50 border rounded-lg p-4">
          <p class="text-xs text-gray-500">Order Number</p>
          <p class="font-bold text-lg text-orange-500">{{ orderNumber }}</p>
        </div>
        <router-link
          to="/account/orders"
          class="block w-full gradient-primary text-white py-3 rounded-lg font-semibold hover:opacity-90 transition"
        >
          View My Orders
        </router-link>
      </template>

      <!-- Webhook hasn't landed yet (or the status check failed) — never claim failure -->
      <template v-else>
        <div class="text-5xl">⏳</div>
        <h1 class="font-display text-xl font-bold text-gray-800">Payment is being confirmed</h1>
        <p class="text-sm text-gray-500">
          Your payment for order
          <span class="font-semibold text-gray-700">{{ orderNumber }}</span>
          is still being processed. This usually takes just a moment — check My
          Orders shortly.
        </p>
        <router-link
          to="/account/orders"
          class="block w-full gradient-primary text-white py-3 rounded-lg font-semibold hover:opacity-90 transition"
        >
          View My Orders
        </router-link>
      </template>
    </div>
  </div>
</template>

<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref } from "vue";
import { useRoute } from "vue-router";
import { getPaymentStatus, payOrder } from "../services/orders";

const route = useRoute();

const orderNumber = String(route.query.order ?? "");
const cancelled = route.query.cancelled === "1";

const state = ref<"checking" | "paid" | "pending">("checking");
const paying = ref(false);
const payError = ref("");

let stopped = false;
onBeforeUnmount(() => (stopped = true));

// The webhook is the source of truth for payment_status, and it can land a
// beat after Stripe redirects back — poll briefly instead of trusting the
// redirect itself.
async function pollStatus(attempts = 6, delayMs = 2000) {
  for (let i = 0; i < attempts && !stopped; i++) {
    try {
      const status = await getPaymentStatus(orderNumber);
      if (status.payment_status === "paid") {
        state.value = "paid";
        return;
      }
    } catch {
      // Transient failure — keep polling; the fallback state covers the rest.
    }
    await new Promise((resolve) => setTimeout(resolve, delayMs));
  }
  if (!stopped) state.value = "pending";
}

async function payNow() {
  if (paying.value) return;
  paying.value = true;
  payError.value = "";

  try {
    const status = await getPaymentStatus(orderNumber);
    const { url } = await payOrder(status.id);
    window.location.href = url;
  } catch {
    payError.value = "Could not start the payment. Please try again from My Orders.";
    paying.value = false;
  }
}

onMounted(() => {
  if (!orderNumber) {
    state.value = "pending";
    return;
  }
  if (!cancelled) pollStatus();
});
</script>
