<template>
  <router-link
    :to="`/products/${product.slug}`"
    class="product-card group bg-white rounded-xl overflow-hidden shadow-md cursor-pointer block"
  >
    <div class="relative overflow-hidden">
      <img
        :src="product.image ?? ''"
        :alt="product.name"
        class="product-image w-full h-48 object-cover"
      />

      <div
        v-if="discount > 0"
        class="absolute top-2 left-2 bg-red-500 text-white text-xs font-bold px-2 py-1 rounded"
      >
        -{{ discount }}%
      </div>

      <span
        v-if="product.stock_quantity <= 0"
        class="absolute top-2 right-2 bg-gray-800 text-white text-xs font-bold px-2 py-1 rounded"
      >
        Out of stock
      </span>

      <button
        v-else
        @click.prevent.stop="addToCart"
        class="absolute top-2 right-2 bg-white p-2 rounded-full shadow-lg hover:bg-orange-500 hover:text-white transition opacity-0 group-hover:opacity-100"
        aria-label="Add to cart"
      >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
        </svg>
      </button>
    </div>

    <div class="p-4">
      <p v-if="product.category" class="text-xs text-gray-400 mb-1">{{ product.category.name }}</p>
      <h4 class="font-medium text-sm mb-2 line-clamp-2 h-10">{{ product.name }}</h4>

      <div class="flex items-center gap-1 mb-2">
        <StarRating :rating="Number(product.rating)" />
        <span class="text-xs text-gray-500">({{ product.sold_count }})</span>
      </div>

      <div class="flex items-center gap-2">
        <span class="text-orange-500 font-bold text-lg">₱{{ product.price }}</span>
        <span v-if="product.original_price" class="text-gray-400 text-sm line-through">
          ₱{{ product.original_price }}
        </span>
      </div>
    </div>
  </router-link>
</template>

<script lang="ts" setup>
import { computed } from "vue";
import StarRating from "./StarRating.vue";
import { useAddToCart } from "../../composables/useAddToCart";
import type { Product } from "../../services/products";

const props = defineProps<{ product: Product }>();
const { addToCart: addItem } = useAddToCart();

const discount = computed(() => {
  const price = Number(props.product.price);
  const original = Number(props.product.original_price ?? props.product.price);
  return original > 0 ? Math.round(((original - price) / original) * 100) : 0;
});

function addToCart() {
  addItem({
    id: props.product.id,
    name: props.product.name,
    price: Number(props.product.price),
    image: props.product.image ?? "",
  });
}
</script>
