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
        <h3 class="font-display text-xl font-bold">Shopping Cart</h3>
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
            ></path>
          </svg>
        </button>
      </div>

      <!-- Cart Items -->
      <div id="cartItems" class="p-4">
        <div
          v-if="cartStore.items.length === 0"
          class="text-center py-12 text-gray-400"
        >
          <svg
            class="w-16 h-16 mx-auto mb-4 opacity-50"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"
            ></path>
          </svg>
          <p class="font-medium">Your cart is empty</p>
          <p class="text-sm mt-1">Add some products to get started!</p>
        </div>

        <div v-else>
          <div
            v-for="item in cartStore.items"
            :key="item.key"
            class="flex gap-4 mb-4 pb-4 border-b"
          >
            <img
              :src="item.image ?? undefined"
              :alt="item.name"
              class="w-20 h-20 object-cover rounded-lg"
            />
            <div class="flex-1">
              <h4 class="font-medium text-sm mb-1">{{ item.name }}</h4>
              <p v-if="item.variant_label" class="text-xs text-gray-500 mb-1">
                {{ item.variant_label }}
              </p>
              <p class="text-orange-500 font-bold">
                ₱{{ (item.price * item.quantity).toLocaleString() }}
              </p>
              <div class="flex items-center gap-2 mt-2">
                <button
                  @click="decrease(item)"
                  class="w-8 h-8 rounded border border-gray-300 flex items-center justify-center hover:bg-gray-100"
                  aria-label="Decrease quantity"
                >
                  -
                </button>
                <span class="w-8 text-center">{{ item.quantity }}</span>
                <button
                  @click="increase(item)"
                  class="w-8 h-8 rounded border border-gray-300 flex items-center justify-center hover:bg-gray-100"
                  aria-label="Increase quantity"
                >
                  +
                </button>
              </div>
            </div>
            <button
              @click="remove(item.key)"
              class="text-gray-400 hover:text-red-500 w-8 h-8 flex items-center justify-center shrink-0"
              aria-label="Remove item"
            >
              <svg
                class="w-5 h-5"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                ></path>
              </svg>
            </button>
          </div>
        </div>
      </div>

      <!-- Footer -->
      <div class="sticky bottom-0 bg-white border-t p-4">
        <div class="flex items-center justify-between mb-4">
          <span class="font-semibold">Total:</span>
          <span class="font-bold text-xl text-orange-500"
            >₱{{ totalPrice }}</span
          >
        </div>
        <button
          class="w-full gradient-primary text-white py-3 rounded-lg font-semibold hover:opacity-90 transition disabled:opacity-50 disabled:cursor-not-allowed"
          :disabled="cartStore.count() === 0"
          @click="checkout"
        >
          Proceed to Checkout
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { useCartStore, type CartLine } from "../../stores/cart";

const cartStore = useCartStore();
const emit = defineEmits<{ (e: "close-cart"): void; (e: "open-checkout"): void }>();

function close() {
  emit("close-cart");
}

function checkout() {
  emit("open-checkout");
}

const totalPrice = computed(() => {
  return cartStore.total().toLocaleString();
});

function increase(item: CartLine) {
  cartStore.updateQuantity(item.key, item.quantity + 1);
}
function decrease(item: CartLine) {
  cartStore.updateQuantity(item.key, item.quantity - 1);
}

function remove(key: string) {
  cartStore.removeItem(key);
}
</script>
