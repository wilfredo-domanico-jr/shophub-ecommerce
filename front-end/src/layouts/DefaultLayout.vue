<template>
  <TopBanner />
  <Header @open-cart="openCart" @open-track-order="openOrderTracking" />
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
import { ref } from "vue";

import TopBanner from "../components/common/TopBanner.vue";
import Header from "../components/common/Header.vue";
import CartModal from "../components/common/CartModal.vue";
import CheckoutModal from "../components/common/CheckoutModal.vue";
import OrderTrackingModal from "../components/common/OrderTrackingModal.vue";
import Footer from "../components/common/Footer.vue";

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
  showCheckoutModal.value = true;
}

function closeCheckout() {
  showCheckoutModal.value = false;
}

function openOrderTracking() {
  showOrderTrackingModal.value = true;
}

function closeOrderTracking() {
  showOrderTrackingModal.value = false;
}
</script>
