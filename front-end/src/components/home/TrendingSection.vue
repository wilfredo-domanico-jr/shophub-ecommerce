<template>
  <div class="container mx-auto px-4 py-8">
    <div class="flex items-center justify-between mb-6">
      <h3 class="font-display text-2xl md:text-3xl font-bold text-gray-800">
        Trending Now
      </h3>
      <router-link
        to="/products"
        class="text-orange-500 font-medium hover:text-orange-600 transition flex items-center gap-1"
      >
        View All
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
            d="M9 5l7 7-7 7"
          ></path>
        </svg>
      </router-link>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
      <TrendingCard
        v-for="trending in trendings"
        :key="trending.id"
        :trending="trending"
      />
    </div>
  </div>
</template>

<script lang="ts" setup>
import { ref, onMounted } from "vue";
import TrendingCard from "../common/TrendingCard.vue";
import { getFeaturedProducts } from "../../services/products";

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

const trendings = ref<TrendingCard[]>([]);

async function fetchTrendings() {
  const res = await getFeaturedProducts();
  trendings.value = res.data.map((p) => {
    const price = Number(p.price);
    const originalPrice = Number(p.original_price ?? p.price);
    const discount = originalPrice > 0 ? Math.round(((originalPrice - price) / originalPrice) * 100) : 0;

    return {
      id: p.id,
      slug: p.slug,
      name: p.name,
      price,
      originalPrice,
      image: p.image ?? "",
      rating: Number(p.rating),
      sold: p.sold_count,
      discount,
    };
  });
}

onMounted(() => {
  // Homepage sections fail quietly — an empty section beats a broken page.
  fetchTrendings().catch(() => {});
});
</script>
