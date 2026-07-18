<template>
  <div class="container mx-auto px-4 py-8">
    <div class="gradient-primary rounded-2xl p-6 shadow-xl">
      <!-- Header -->
      <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-3">
          <div class="bg-white rounded-lg p-2">
            <svg
              class="w-6 h-6 text-orange-500"
              fill="currentColor"
              viewBox="0 0 24 24"
            >
              <path d="M13 10V3L4 14h7v7l9-11h-7z"></path>
            </svg>
          </div>
          <h3 class="font-display text-2xl md:text-3xl font-bold text-white">
            Flash Sale
          </h3>
        </div>

        <!-- Countdown Timer -->
        <div class="flex items-center gap-2 text-white">
          <span class="text-sm hidden md:block">Ends In:</span>
          <div class="flex gap-1">
            <div class="timer-box px-2 py-1 rounded text-center min-w-[40px]">
              <div class="font-bold text-lg">{{ hours }}</div>
              <div class="text-xs opacity-80">HRS</div>
            </div>
            <div class="text-2xl font-bold">:</div>
            <div class="timer-box px-2 py-1 rounded text-center min-w-[40px]">
              <div class="font-bold text-lg">{{ minutes }}</div>
              <div class="text-xs opacity-80">MIN</div>
            </div>
            <div class="text-2xl font-bold">:</div>
            <div class="timer-box px-2 py-1 rounded text-center min-w-[40px]">
              <div class="font-bold text-lg">{{ seconds }}</div>
              <div class="text-xs opacity-80">SEC</div>
            </div>
          </div>
        </div>
      </div>

      <!-- Products Grid -->
      <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
        <FlashSaleProductCard
          v-for="product in products"
          :key="product.id"
          :product="product"
          @select="onProductSelect"
        />
      </div>
    </div>
  </div>
</template>

<script lang="ts" setup>
import { ref, onMounted, onUnmounted } from "vue";
import FlashSaleProductCard from "../common/FlashSaleProductCard.vue";
import { getFlashSaleProducts } from "../../services/products";
import { useAddToCart } from "../../composables/useAddToCart";

const { addToCart } = useAddToCart();

interface Product {
  id: number;
  name: string;
  price: number;
  originalPrice: number;
  sold: number;
  discount: number;
  image: string;
  progress: number;
}

const products = ref<Product[]>([]);

async function fetchProducts() {
  const res = await getFlashSaleProducts();
  products.value = res.data.map((p) => {
    const price = Number(p.price);
    const originalPrice = Number(p.original_price ?? p.price);
    const discount = originalPrice > 0 ? Math.round(((originalPrice - price) / originalPrice) * 100) : 0;
    const goal = p.flash_sale_goal ?? Math.max(p.sold_count, 1) * 1.5;

    return {
      id: p.id,
      name: p.name,
      price,
      originalPrice,
      sold: p.sold_count,
      discount,
      image: p.image ?? "",
      progress: Math.min(100, Math.round((p.sold_count / goal) * 100)),
    };
  });
}

// ** Countdown Timer **
const hours = ref("00");
const minutes = ref("00");
const seconds = ref("00");

const saleEndTime = new Date(Date.now() + 2 * 60 * 60 * 1000); // 2 hours from now
let timerInterval: number;

function updateTimer() {
  const now = new Date();
  const diff = saleEndTime.getTime() - now.getTime();
  if (diff <= 0) {
    clearInterval(timerInterval);
    hours.value = minutes.value = seconds.value = "00";
    return;
  }
  const h = Math.floor(diff / 1000 / 60 / 60);
  const m = Math.floor((diff / 1000 / 60) % 60);
  const s = Math.floor((diff / 1000) % 60);
  hours.value = h.toString().padStart(2, "0");
  minutes.value = m.toString().padStart(2, "0");
  seconds.value = s.toString().padStart(2, "0");
}

async function onProductSelect(product: Product) {
  await addToCart(product, 1, "Sign in to grab flash sale deals.");
}

onMounted(() => {
  fetchProducts();
  updateTimer();
  timerInterval = setInterval(updateTimer, 1000);
});

onUnmounted(() => {
  clearInterval(timerInterval);
});
</script>
