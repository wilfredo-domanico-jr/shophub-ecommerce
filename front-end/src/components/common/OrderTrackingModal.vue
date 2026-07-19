<template>
  <div
    class="flex fixed inset-0 bg-black/50 z-50 items-center justify-center p-4"
  >
    <div
      class="bg-white rounded-2xl max-w-md w-full max-h-[90vh] overflow-y-auto animate-scaleIn"
    >
      <!-- Header -->
      <div
        class="sticky top-0 bg-white border-b p-4 flex items-center justify-between"
      >
        <h3 class="font-display text-xl font-bold">Track Your Order</h3>

        <button @click="close" class="text-gray-500 hover:text-gray-700">
          <svg
            class="w-6 h-6"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M6 18L18 6M6 6l12 12"
            />
          </svg>
        </button>
      </div>

      <!-- Body -->
      <div class="p-4 space-y-4">
        <p class="text-sm text-gray-500">
          Enter your tracking or reference number to check your order status.
        </p>

        <!-- Input -->
        <div>
          <label class="text-sm font-medium text-gray-700">
            Order Number
          </label>

          <input
            v-model="trackingNumber"
            type="text"
            placeholder="e.g. SHP-20260707153012-4F2A"
            class="w-full mt-2 px-4 py-3 border rounded-lg focus:outline-none focus:border-orange-500"
          />
        </div>

        <div>
          <label class="text-sm font-medium text-gray-700">
            Email used at checkout
          </label>

          <input
            v-model="email"
            type="email"
            placeholder="you@example.com"
            class="w-full mt-2 px-4 py-3 border rounded-lg focus:outline-none focus:border-orange-500"
          />
        </div>

        <!-- Button -->
        <button
          @click="track"
          class="w-full gradient-primary text-white py-3 rounded-lg font-semibold hover:opacity-90 transition disabled:opacity-50"
          :disabled="!trackingNumber || !email || loading"
        >
          {{ loading ? "Tracking..." : "Track Order" }}
        </button>

        <!-- Result -->
        <div v-if="result" class="mt-4 p-4 border rounded-lg bg-gray-50 space-y-2">
          <p class="font-semibold text-sm">Order Status:</p>
          <p class="text-orange-500 font-bold mt-1 capitalize">
            {{ result.status }}
          </p>
          <p class="text-xs text-gray-500">
            Placed: {{ new Date(result.created_at).toLocaleString() }}
          </p>
          <ul class="text-sm divide-y">
            <li v-for="item in result.items" :key="item.id" class="py-1 flex justify-between">
              <span>
                {{ item.product_name }}
                <span v-if="item.variant_label" class="text-gray-400">({{ item.variant_label }})</span>
                × {{ item.quantity }}
              </span>
              <span>₱{{ item.subtotal }}</span>
            </li>
          </ul>
          <p class="text-sm font-semibold text-right">Total: ₱{{ result.total }}</p>
        </div>

        <!-- Error -->
        <p v-if="error" class="text-red-500 text-sm">
          {{ error }}
        </p>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from "vue";
import { trackOrder as trackOrderApi, type TrackedOrder } from "../../services/orders";

const emit = defineEmits<{ (e: "close-order-tracking"): void }>();

const trackingNumber = ref("");
const email = ref("");
const result = ref<TrackedOrder | null>(null);
const error = ref("");
const loading = ref(false);

function close() {
  emit("close-order-tracking");
}

async function track() {
  error.value = "";
  result.value = null;
  loading.value = true;

  try {
    result.value = await trackOrderApi({
      order_number: trackingNumber.value,
      email: email.value,
    });
  } catch (e: any) {
    error.value = e?.response?.data?.message ?? "No matching order found.";
  } finally {
    loading.value = false;
  }
}
</script>
