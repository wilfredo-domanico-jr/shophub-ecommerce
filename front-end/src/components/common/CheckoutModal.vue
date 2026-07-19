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

        <p
          v-if="isDemoAccount"
          class="text-xs text-gray-500 bg-orange-50 border border-orange-200 rounded-lg p-3"
        >
          Demo account — contact and shipping details are locked to the demo profile.
        </p>

        <p
          v-else-if="profileIncomplete"
          class="text-xs text-gray-500 bg-orange-50 border border-orange-200 rounded-lg p-3"
        >
          Tip: save your contact number and shipping address in
          <router-link to="/account" class="text-orange-600 underline" @click="close">
            your profile
          </router-link>
          so they're filled in automatically next time.
        </p>

        <div>
          <label class="text-sm font-medium text-gray-700">Full Name</label>
          <input v-model="form.customer_name" type="text" required :readonly="isDemoAccount" :class="lockedFieldClass" class="w-full mt-1 px-4 py-2 border rounded-lg focus:outline-none focus:border-orange-500" />
        </div>

        <div>
          <label class="text-sm font-medium text-gray-700">Email</label>
          <input v-model="form.customer_email" type="email" required :readonly="isDemoAccount" :class="lockedFieldClass" class="w-full mt-1 px-4 py-2 border rounded-lg focus:outline-none focus:border-orange-500" />
        </div>

        <div>
          <label class="text-sm font-medium text-gray-700">Phone Number</label>
          <input v-model="form.customer_phone" type="tel" required :readonly="isDemoAccount" :class="lockedFieldClass" class="w-full mt-1 px-4 py-2 border rounded-lg focus:outline-none focus:border-orange-500" />
        </div>

        <div>
          <label class="text-sm font-medium text-gray-700">Shipping Address</label>
          <textarea v-model="form.shipping_address" required rows="2" :readonly="isDemoAccount" :class="lockedFieldClass" class="w-full mt-1 px-4 py-2 border rounded-lg focus:outline-none focus:border-orange-500"></textarea>
        </div>

        <div class="bg-gray-50 border rounded-lg p-3 text-sm">
          <div class="flex justify-between font-semibold">
            <span>Total</span>
            <span class="text-orange-500">₱{{ cartStore.checkoutTotal().toLocaleString() }}</span>
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
import { computed, ref } from "vue";
import { useCartStore } from "../../stores/cart";
import { useAuthStore } from "../../stores/auth";
import { useDemoAccount } from "../../composables/useDemoAccount";
import { createOrder, type Order } from "../../services/orders";

const cartStore = useCartStore();
const auth = useAuthStore();
const { isDemoAccount } = useDemoAccount();

// Demo orders always use the seeded demo identity (backend enforces this too).
const lockedFieldClass = computed(() =>
  isDemoAccount.value ? "bg-gray-50 text-gray-500 cursor-not-allowed" : ""
);
const emit = defineEmits<{ (e: "close-checkout"): void; (e: "order-placed"): void }>();

const profileIncomplete = computed(
  () => !auth.user?.phone || !auth.user?.default_shipping_address
);

// Modal is v-if-mounted, so pre-filling at setup picks up the logged-in user
const form = ref({
  customer_name: auth.user?.name ?? "",
  customer_email: auth.user?.email ?? "",
  customer_phone: auth.user?.phone ?? "",
  shipping_address: auth.user?.default_shipping_address ?? "",
});

const submitting = ref(false);
const error = ref("");
const placedOrder = ref<Order | null>(null);

function close() {
  emit("close-checkout");
}

async function submit() {
  error.value = "";

  if (
    !form.value.customer_name.trim() ||
    !form.value.customer_phone.trim() ||
    !form.value.shipping_address.trim()
  ) {
    error.value = "Please complete your name, contact number, and shipping address.";
    return;
  }

  submitting.value = true;

  try {
    const wasBuyNow = !!cartStore.buyNowItem;

    const order = await createOrder({
      ...form.value,
      items: cartStore.checkoutItems().map((item) => ({
        product_id: item.id,
        quantity: item.quantity || 1,
      })),
    });

    placedOrder.value = order;

    // Only clear what was purchased: a buy-now order leaves the cart intact.
    if (wasBuyNow) {
      cartStore.clearBuyNow();
    } else {
      cartStore.items = [];
    }

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
