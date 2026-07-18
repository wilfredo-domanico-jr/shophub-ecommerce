<template>
  <TopBanner v-if="!minimalChrome" />
  <Header
    :hide-search="minimalChrome"
    @open-cart="openCart"
    @open-track-order="openOrderTracking"
  />
  <CartModal
    v-if="showCartModal"
    @close-cart="closeCart"
    @open-checkout="openCheckout"
  />
  <CheckoutModal
    v-if="showCheckoutModal"
    @close-checkout="closeCheckout"
  />
  <OrderTrackingModal
    v-if="showOrderTrackingModal"
    @close-order-tracking="closeOrderTracking"
  />
  <router-view />
  <Footer @open-track-order="openOrderTracking" />
</template>

<script setup lang="ts">
import { computed, ref, watch } from "vue";
import { useRoute, useRouter } from "vue-router";

import TopBanner from "../components/common/TopBanner.vue";
import Header from "../components/common/Header.vue";
import CartModal from "../components/common/CartModal.vue";
import CheckoutModal from "../components/common/CheckoutModal.vue";
import OrderTrackingModal from "../components/common/OrderTrackingModal.vue";
import Footer from "../components/common/Footer.vue";
import { useAuthStore } from "../stores/auth";
import { useCartStore } from "../stores/cart";

const route = useRoute();
const router = useRouter();
const auth = useAuthStore();
const cartStore = useCartStore();

const minimalChrome = computed(() => route.meta.minimalChrome === true);

const showCartModal = ref(false);
const showCheckoutModal = ref(false);
const showOrderTrackingModal = ref(false);

function openCart() {
  showCartModal.value = true;
}

function closeCart() {
  showCartModal.value = false;
}

function openCheckout() {
  showCartModal.value = false;

  // Checkout requires an account; send guests to login and come back
  // with ?checkout=1 so the modal reopens automatically.
  if (!auth.isLoggedIn) {
    router.push({
      name: "CustomerLogin",
      query: { redirect: `${route.path}?checkout=1` },
    });
    return;
  }

  // Cart-initiated checkout must not pick up a stale buy-now item.
  cartStore.clearBuyNow();
  showCheckoutModal.value = true;
}

function closeCheckout() {
  showCheckoutModal.value = false;
  cartStore.clearBuyNow();
}

watch(
  () => route.query.checkout,
  (flag) => {
    if (flag !== "1") return;

    if (auth.isLoggedIn && (cartStore.buyNowItem || cartStore.items.length > 0)) {
      showCheckoutModal.value = true;
    }

    const { checkout: _checkout, ...rest } = route.query;
    router.replace({ query: rest });
  },
  { immediate: true }
);

function openOrderTracking() {
  showOrderTrackingModal.value = true;
}

function closeOrderTracking() {
  showOrderTrackingModal.value = false;
}
</script>
