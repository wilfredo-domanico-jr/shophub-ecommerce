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
        <h3 class="font-display text-xl font-bold">
          {{ placedOrder ? "Order Placed!" : "Checkout" }}
        </h3>
        <button @click="close" class="text-gray-500 hover:text-gray-700">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <!-- Success state -->
      <div v-if="placedOrder" class="p-6 space-y-4 text-center">
        <div class="text-5xl">✅</div>
        <p class="font-semibold">Thank you, {{ placedOrder.customer_name }}!</p>
        <p class="text-sm text-gray-500">
          Your order has been received. A confirmation email is on its way to
          {{ placedOrder.customer_email }}.
        </p>
        <div class="bg-gray-50 border rounded-lg p-4">
          <p class="text-xs text-gray-500">Order Number</p>
          <p class="font-bold text-lg text-orange-500">{{ placedOrder.order_number }}</p>
        </div>
        <p class="text-xs text-gray-400">
          Save this order number and your email — you'll need both to track your order.
        </p>
        <button
          class="w-full gradient-primary text-white py-3 rounded-lg font-semibold hover:opacity-90 transition"
          @click="close"
        >
          Done
        </button>
      </div>

      <!-- Form state -->
      <form v-else class="p-4 space-y-4" @submit.prevent="submit">
        <p v-if="error" class="text-red-500 text-sm">{{ error }}</p>

        <div>
          <label class="text-sm font-medium text-gray-700">Full Name</label>
          <input v-model="form.customer_name" type="text" required class="w-full mt-1 px-4 py-2 border rounded-lg focus:outline-none focus:border-orange-500" />
        </div>

        <div>
          <label class="text-sm font-medium text-gray-700">Email</label>
          <input v-model="form.customer_email" type="email" required class="w-full mt-1 px-4 py-2 border rounded-lg focus:outline-none focus:border-orange-500" />
        </div>

        <div>
          <label class="text-sm font-medium text-gray-700">Phone Number</label>
          <input v-model="form.customer_phone" type="tel" required class="w-full mt-1 px-4 py-2 border rounded-lg focus:outline-none focus:border-orange-500" />
        </div>

        <div>
          <label class="text-sm font-medium text-gray-700">Shipping Address</label>
          <textarea v-model="form.shipping_address" required rows="2" class="w-full mt-1 px-4 py-2 border rounded-lg focus:outline-none focus:border-orange-500"></textarea>
        </div>

        <div class="bg-gray-50 border rounded-lg p-3 text-sm">
          <div class="flex justify-between font-semibold">
            <span>Total</span>
            <span class="text-orange-500">₱{{ cartStore.total().toLocaleString() }}</span>
          </div>
          <p class="text-xs text-gray-500 mt-1">Payment: Cash on Delivery</p>
        </div>

        <button
          type="submit"
          :disabled="submitting"
          class="w-full gradient-primary text-white py-3 rounded-lg font-semibold hover:opacity-90 transition disabled:opacity-50"
        >
          {{ submitting ? "Placing Order..." : "Place Order" }}
        </button>
      </form>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from "vue";
import { useCartStore } from "../../stores/cart";
import { createOrder, type Order } from "../../services/orders";

const cartStore = useCartStore();
const emit = defineEmits<{ (e: "close-checkout"): void; (e: "order-placed"): void }>();

const form = ref({
  customer_name: "",
  customer_email: "",
  customer_phone: "",
  shipping_address: "",
});

const submitting = ref(false);
const error = ref("");
const placedOrder = ref<Order | null>(null);

function close() {
  emit("close-checkout");
}

async function submit() {
  error.value = "";
  submitting.value = true;

  try {
    const order = await createOrder({
      ...form.value,
      items: cartStore.items.map((item) => ({
        product_id: item.id,
        quantity: item.quantity || 1,
      })),
    });

    placedOrder.value = order;
    cartStore.items = [];
    emit("order-placed");
  } catch (e: any) {
    error.value =
      e?.response?.data?.errors?.items?.[0] ??
      e?.response?.data?.message ??
      "Something went wrong placing your order. Please try again.";
  } finally {
    submitting.value = false;
  }
}
</script>
