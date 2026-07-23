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
        <p v-if="payRedirectFailed" class="text-sm text-amber-600 bg-amber-50 border border-amber-200 rounded-lg p-3">
          Your order was saved, but we couldn't start the online payment. You can
          pay it anytime from
          <router-link to="/account/orders" class="underline font-medium" @click="close">My Orders</router-link>.
        </p>
        <p v-else class="text-sm text-gray-500">
          Your order has been received. A confirmation email is on its way to
          {{ placedOrder.customer_email }}.
        </p>
        <div class="bg-gray-50 border rounded-lg p-4">
          <p class="text-xs text-gray-500">Order Number</p>
          <p class="font-bold text-lg text-orange-500">{{ placedOrder.order_number }}</p>
        </div>
        <p v-if="Number(placedOrder.discount) > 0" class="text-sm text-green-600 font-medium">
          You saved ₱{{ Number(placedOrder.discount).toLocaleString() }} with {{ placedOrder.voucher_code }}!
        </p>
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

        <!-- Voucher -->
        <div>
          <label class="text-sm font-medium text-gray-700">Voucher Code</label>
          <div v-if="!appliedVoucher" class="flex gap-2 mt-1">
            <input
              v-model="voucherCode"
              type="text"
              placeholder="e.g. SAVE10"
              class="flex-1 px-4 py-2 border rounded-lg uppercase focus:outline-none focus:border-orange-500"
              @input="voucherCode = voucherCode.toUpperCase()"
              @keydown.enter.prevent="applyVoucher"
            />
            <button
              type="button"
              class="px-4 py-2 border-2 border-orange-500 text-orange-500 rounded-lg font-semibold hover:bg-orange-50 transition disabled:opacity-50"
              :disabled="applyingVoucher || !voucherCode.trim()"
              @click="applyVoucher"
            >
              {{ applyingVoucher ? "..." : "Apply" }}
            </button>
          </div>
          <p v-if="voucherError" class="text-red-500 text-xs mt-1">{{ voucherError }}</p>

          <!-- Publicly listed vouchers — tap to apply -->
          <div v-if="!appliedVoucher && availableVouchers.length" class="mt-2 space-y-1.5">
            <p class="text-xs text-gray-500">Available vouchers:</p>
            <button
              v-for="voucher in availableVouchers"
              :key="voucher.code"
              type="button"
              class="w-full flex items-center justify-between gap-2 border border-dashed border-orange-300 rounded-lg px-3 py-2 text-left hover:bg-orange-50 transition disabled:opacity-50"
              :disabled="applyingVoucher"
              @click="useVoucher(voucher.code)"
            >
              <span class="text-sm">
                <span class="font-mono font-bold text-orange-600">{{ voucher.code }}</span>
                <span class="text-gray-600"> — {{ voucherSummary(voucher) }}</span>
                <span v-if="voucher.min_spend" class="text-gray-400 text-xs block">
                  Min spend ₱{{ Number(voucher.min_spend).toLocaleString() }}
                </span>
              </span>
              <span class="text-xs font-semibold text-orange-500 shrink-0">Use</span>
            </button>
          </div>
        </div>

        <div class="bg-gray-50 border rounded-lg p-3 text-sm">
          <div
            v-for="item in cartStore.checkoutItems()"
            :key="item.key"
            class="flex justify-between text-gray-600 mb-1"
          >
            <span>
              {{ item.name }}
              <span v-if="item.variant_label" class="text-gray-400">({{ item.variant_label }})</span>
              × {{ item.quantity || 1 }}
            </span>
            <span>₱{{ (item.price * (item.quantity || 1)).toLocaleString() }}</span>
          </div>
          <div v-if="appliedVoucher" class="flex justify-between text-green-600 mb-1">
            <span>
              Discount ({{ appliedVoucher.code }})
              <button
                type="button"
                class="text-gray-400 hover:text-red-500 ml-1 p-1.5 -m-1"
                title="Remove voucher"
                aria-label="Remove voucher"
                @click="removeVoucher"
              >
                ✕
              </button>
            </span>
            <span>−₱{{ Number(appliedVoucher.discount).toLocaleString() }}</span>
          </div>
          <div class="flex justify-between font-semibold border-t pt-2 mt-2">
            <span>Total</span>
            <span class="text-orange-500">₱{{ displayTotal.toLocaleString() }}</span>
          </div>
        </div>

        <!-- Payment method -->
        <div>
          <label class="text-sm font-medium text-gray-700">Payment Method</label>
          <div class="mt-1 space-y-2">
            <label class="flex items-center gap-2 border rounded-lg px-3 py-2 cursor-pointer hover:border-orange-300 transition" :class="paymentMethod === 'Cash on Delivery' ? 'border-orange-500 bg-orange-50' : ''">
              <input v-model="paymentMethod" type="radio" value="Cash on Delivery" class="accent-orange-500" />
              <span class="text-sm">Cash on Delivery</span>
            </label>
            <label v-if="cardPaymentsEnabled" class="flex items-center gap-2 border rounded-lg px-3 py-2 cursor-pointer hover:border-orange-300 transition" :class="paymentMethod === 'Card' ? 'border-orange-500 bg-orange-50' : ''">
              <input v-model="paymentMethod" type="radio" value="Card" class="accent-orange-500" />
              <span class="text-sm">
                Card (Stripe)
                <span class="text-xs text-gray-400 block">You'll be redirected to Stripe's secure payment page.</span>
              </span>
            </label>
          </div>
        </div>

        <button
          type="submit"
          :disabled="submitting"
          class="w-full gradient-primary text-white py-3 rounded-lg font-semibold hover:opacity-90 transition disabled:opacity-50"
        >
          {{ submitting ? "Placing Order..." : paymentMethod === "Card" ? "Continue to Payment" : "Place Order" }}
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
import { createOrder, payOrder, type Order, type PaymentMethod } from "../../services/orders";
import { getAppConfig } from "../../services/config";
import {
  getPublicVouchers,
  previewVoucher,
  voucherSummary,
  type PublicVoucher,
  type VoucherPreview,
} from "../../services/vouchers";

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
const payRedirectFailed = ref(false);

// Card option appears only when the backend has Stripe configured.
const paymentMethod = ref<PaymentMethod>("Cash on Delivery");
const cardPaymentsEnabled = ref(false);
getAppConfig()
  .then((config) => (cardPaymentsEnabled.value = config.card_payments_enabled))
  .catch(() => {}); // COD-only fallback — checkout works without the flag

// Voucher state is checkout-scoped: it lives and dies with this modal.
const voucherCode = ref("");
const appliedVoucher = ref<VoucherPreview | null>(null);
const voucherError = ref("");
const applyingVoucher = ref(false);

// With a voucher applied, the server-priced total is the source of truth —
// the client cart may hold stale prices.
const displayTotal = computed(() =>
  appliedVoucher.value ? Number(appliedVoucher.value.total) : cartStore.checkoutTotal()
);

function checkoutItemsPayload() {
  return cartStore.checkoutItems().map((item) => ({
    product_id: item.id,
    variant_id: item.variant_id ?? undefined,
    quantity: item.quantity || 1,
  }));
}

async function applyVoucher() {
  const code = voucherCode.value.trim();
  if (!code || applyingVoucher.value) return;

  voucherError.value = "";
  applyingVoucher.value = true;

  try {
    appliedVoucher.value = await previewVoucher({ code, items: checkoutItemsPayload() });
  } catch (e: any) {
    voucherError.value =
      e?.response?.data?.errors?.voucher_code?.[0] ??
      e?.response?.data?.errors?.items?.[0] ??
      "Could not apply that voucher. Please try again.";
  } finally {
    applyingVoucher.value = false;
  }
}

function removeVoucher() {
  appliedVoucher.value = null;
  voucherCode.value = "";
  voucherError.value = "";
}

// The modal is v-if-mounted, so this fetches fresh on every checkout open.
const availableVouchers = ref<PublicVoucher[]>([]);
getPublicVouchers()
  .then((vouchers) => (availableVouchers.value = vouchers))
  .catch(() => {}); // discovery is optional — checkout works without it

function useVoucher(code: string) {
  voucherCode.value = code;
  applyVoucher();
}

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
      payment_method: paymentMethod.value,
      ...(appliedVoucher.value ? { voucher_code: appliedVoucher.value.code } : {}),
      items: checkoutItemsPayload(),
    });

    // Only clear what was purchased: a buy-now order leaves the cart intact.
    // Awaited so the server cart is empty before any Stripe redirect leaves
    // the page.
    if (wasBuyNow) {
      cartStore.clearBuyNow();
    } else {
      await cartStore.clearItems();
    }

    if (paymentMethod.value === "Card") {
      try {
        const { url } = await payOrder(order.id);
        // Keep submitting=true — the whole page navigates to Stripe.
        window.location.href = url;
        return;
      } catch {
        // The order exists; fall through to the success panel with a
        // pay-from-My-Orders note instead of losing it.
        payRedirectFailed.value = true;
      }
    }

    placedOrder.value = order;
    emit("order-placed");
  } catch (e: any) {
    error.value =
      e?.response?.data?.errors?.items?.[0] ??
      e?.response?.data?.errors?.voucher_code?.[0] ??
      e?.response?.data?.message ??
      "Something went wrong placing your order. Please try again.";
  } finally {
    submitting.value = false;
  }
}
</script>
