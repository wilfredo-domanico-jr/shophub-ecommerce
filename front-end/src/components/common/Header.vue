<template>
  <header class="bg-white shadow-md sticky top-0 z-50">
    <div class="container mx-auto px-4 py-4 flex items-center justify-between">
      <!-- Logo & Mobile Menu Button -->
      <div class="flex items-center gap-2">
        <router-link
          to="/"
          class="text-gradient-primary font-display text-2xl md:text-3xl font-bold"
          >ShopHub</router-link
        >
      </div>

      <!-- Desktop Search Bar -->
      <div class="hidden md:flex flex-1 max-w-2xl mx-8">
        <SearchAutosuggest />
      </div>

      <!-- Desktop Header Icons / Account -->
      <div class="flex items-center gap-3">
        <!-- Track Order -->
        <button
          @click="emit('open-track-order')"
          class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-orange-50 hover:text-orange-600 transition"
          aria-label="Track My Order"
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
              d="M9 20l-5.447-2.724A2 2 0 013 15.382V6.618a2 2 0 011.553-1.944l5-1.25a2 2 0 01.894 0l5 1.25A2 2 0 0117 6.618v4.882m0 0l4 2v5.5a2 2 0 01-2 2h-2m0-9.5V11"
            />
          </svg>

          <span class="text-sm font-medium whitespace-nowrap">
            Track Order
          </span>
        </button>

        <!-- Cart -->
        <button
          @click="emit('open-cart')"
          class="relative flex items-center justify-center w-10 h-10 rounded-lg hover:bg-orange-50 hover:text-orange-600 transition"
          aria-label="Open Cart"
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
              d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"
            />
          </svg>

          <span
            v-if="cartStore.count() > 0"
            class="absolute -top-1 -right-1 bg-orange-500 text-white text-[10px] font-bold rounded-full min-w-[18px] h-[18px] flex items-center justify-center px-1"
          >
            {{ cartStore.count() }}
          </span>
        </button>
      </div>
    </div>

    <!-- Mobile Search -->
    <div class="md:hidden mt-3 px-4">
      <SearchAutosuggest placeholder="Search products..." />
    </div>
  </header>
</template>

<script setup lang="ts">
import { useCartStore } from "../../stores/cart";
import SearchAutosuggest from "./SearchAutosuggest.vue";

const cartStore = useCartStore();
const emit = defineEmits<{
  (e: "open-cart"): void;
  (e: "open-track-order"): void;
}>();
</script>
