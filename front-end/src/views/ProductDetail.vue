<template>
  <div class="container mx-auto px-4 py-8">
    <div v-if="loading" class="grid grid-cols-1 md:grid-cols-2 gap-8">
      <div class="skeleton rounded-xl h-96"></div>
      <div class="space-y-4">
        <div class="skeleton h-8 w-3/4 rounded"></div>
        <div class="skeleton h-6 w-1/4 rounded"></div>
        <div class="skeleton h-24 w-full rounded"></div>
      </div>
    </div>

    <div v-else-if="!product" class="bg-white rounded-xl shadow p-12 text-center text-gray-400">
      Product not found.
      <router-link to="/products" class="text-orange-500 font-medium block mt-2">
        Browse all products
      </router-link>
    </div>

    <div v-else>
      <!-- Breadcrumb -->
      <nav class="text-sm text-gray-500 mb-6 flex items-center gap-2">
        <router-link to="/" class="hover:text-orange-500">Home</router-link>
        <span>/</span>
        <router-link to="/products" class="hover:text-orange-500">Products</router-link>
        <template v-if="product.category">
          <span>/</span>
          <router-link :to="`/products?category=${product.category.slug}`" class="hover:text-orange-500">
            {{ product.category.name }}
          </router-link>
        </template>
        <span>/</span>
        <span class="text-gray-700 truncate">{{ product.name }}</span>
      </nav>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
        <!-- Image -->
        <div class="bg-white rounded-xl shadow overflow-hidden">
          <img :src="product.image ?? ''" :alt="product.name" class="w-full h-96 object-cover" />
        </div>

        <!-- Details -->
        <div>
          <p v-if="product.category" class="text-sm text-orange-500 font-medium mb-1">
            {{ product.category.name }}
          </p>
          <h1 class="font-display text-2xl md:text-3xl font-bold text-gray-800 mb-2">
            {{ product.name }}
          </h1>

          <div class="flex items-center gap-2 mb-4">
            <StarRating :rating="Number(product.rating)" />
            <span class="text-sm text-gray-500">({{ product.sold_count }} sold)</span>
          </div>

          <div class="flex items-center gap-3 mb-4">
            <span class="text-3xl font-bold text-orange-500">₱{{ product.price }}</span>
            <span v-if="product.original_price" class="text-lg text-gray-400 line-through">
              ₱{{ product.original_price }}
            </span>
            <span v-if="discount > 0" class="bg-red-500 text-white text-xs font-bold px-2 py-1 rounded">
              -{{ discount }}%
            </span>
          </div>

          <p class="text-gray-600 mb-6 whitespace-pre-line">
            {{ product.description || "No description available." }}
          </p>

          <p class="text-sm mb-4" :class="product.stock_quantity > 0 ? 'text-green-600' : 'text-red-500'">
            {{ product.stock_quantity > 0 ? `${product.stock_quantity} in stock` : "Out of stock" }}
          </p>

          <div v-if="product.stock_quantity > 0" class="flex items-center gap-4 mb-6">
            <div class="flex items-center border rounded-lg">
              <button class="w-10 h-10 flex items-center justify-center hover:bg-gray-100" @click="quantity = Math.max(1, quantity - 1)">
                -
              </button>
              <span class="w-12 text-center">{{ quantity }}</span>
              <button class="w-10 h-10 flex items-center justify-center hover:bg-gray-100" @click="quantity = Math.min(product.stock_quantity, quantity + 1)">
                +
              </button>
            </div>

            <button
              class="flex-1 border-2 border-orange-500 text-orange-500 py-3 rounded-lg font-semibold hover:bg-orange-50 transition"
              @click="addToCart"
            >
              {{ added ? "Added! ✓" : "Add to Cart" }}
            </button>

            <button
              class="flex-1 gradient-primary text-white py-3 rounded-lg font-semibold hover:opacity-90 transition"
              @click="buyNow"
            >
              Buy Now
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted } from "vue";
import { useRoute, useRouter } from "vue-router";
import { getProduct, type Product } from "../services/products";
import { useAddToCart } from "../composables/useAddToCart";
import StarRating from "../components/common/StarRating.vue";

const route = useRoute();
const router = useRouter();
const { addToCart: addItem } = useAddToCart();

const product = ref<Product | null>(null);
const loading = ref(false);
const quantity = ref(1);
const added = ref(false);

const discount = computed(() => {
  if (!product.value) return 0;
  const price = Number(product.value.price);
  const original = Number(product.value.original_price ?? product.value.price);
  return original > 0 ? Math.round(((original - price) / original) * 100) : 0;
});

async function loadProduct() {
  loading.value = true;
  product.value = null;
  quantity.value = 1;
  try {
    product.value = await getProduct(String(route.params.slug));
  } catch {
    product.value = null;
  } finally {
    loading.value = false;
  }
}

async function addToCart() {
  if (!product.value) return;

  const ok = await addItem(
    {
      id: product.value.id,
      name: product.value.name,
      price: Number(product.value.price),
      image: product.value.image ?? "",
    },
    quantity.value
  );
  if (!ok) return;

  added.value = true;
  setTimeout(() => (added.value = false), 1500);
}

async function buyNow() {
  if (!product.value) return;

  const ok = await addItem(
    {
      id: product.value.id,
      name: product.value.name,
      price: Number(product.value.price),
      image: product.value.image ?? "",
    },
    quantity.value
  );
  if (!ok) return;

  // ?checkout=1 is picked up by DefaultLayout, which opens the checkout modal.
  router.push({ path: route.path, query: { checkout: "1" } });
}

watch(() => route.params.slug, loadProduct);
onMounted(loadProduct);
</script>
