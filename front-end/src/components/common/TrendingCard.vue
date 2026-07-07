<template>
  <router-link
    :to="`/products/${trending.slug}`"
    class="product-card group bg-white rounded-xl overflow-hidden shadow-md cursor-pointer block"
  >
    <div class="relative overflow-hidden">
      <img
        :src="trending.image"
        :alt="trending.name"
        class="product-image w-full h-48 object-cover"
      />
      <div
        class="absolute top-2 left-2 bg-red-500 text-white text-xs font-bold px-2 py-1 rounded"
      >
        -{{ trending.discount }}%
      </div>
      <button
        @click.prevent.stop="addToCart()"
        class="absolute top-2 right-2 bg-white p-2 rounded-full shadow-lg hover:bg-orange-500 hover:text-white transition opacity-0 group-hover:opacity-100"
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
          ></path>
        </svg>
      </button>
    </div>
    <div class="p-4">
      <h4 class="font-medium text-sm mb-2 line-clamp-2 h-10">
        {{ trending.name }}
      </h4>
      <div class="flex items-center gap-1 mb-2">
        <StarRating :rating="trending.rating" />
        <span class="text-xs text-gray-500">({{ trending.sold }})</span>
      </div>
      <div class="flex items-center gap-2">
        <span class="text-orange-500 font-bold text-lg"
          >₱{{ trending.price }}</span
        >
        <span class="text-gray-400 text-sm line-through"
          >₱{{ trending.originalPrice }}</span
        >
      </div>
    </div>
  </router-link>
</template>

<script lang="ts" setup>
import StarRating from "../common/StarRating.vue";

import { useCartStore } from "../../stores/cart";

const cartStore = useCartStore();

interface TrendingCard {
  id: number;
  slug: string;
  name: string;
  price: number;
  originalPrice: number;
  image: string;
  rating: number;
  sold: number;
  discount: number;
}

const props = defineProps<{
  trending: TrendingCard;
}>();

function addToCart() {
  cartStore.addItem(props.trending);
  showNotification("Added to cart!");
}

function showNotification(message: string) {
  const notification = document.createElement("div");
  notification.className =
    "fixed top-24 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg z-50 animate-slideDown transition-opacity duration-300";
  notification.textContent = message;
  document.body.appendChild(notification);

  setTimeout(() => {
    notification.style.opacity = "0"; // fade out
    setTimeout(() => notification.remove(), 300);
  }, 2000);
}
</script>
