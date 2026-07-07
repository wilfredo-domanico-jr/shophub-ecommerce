<template>
  <div class="container mx-auto px-4 py-8">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
      <div>
        <h1 class="font-display text-2xl md:text-3xl font-bold text-gray-800">
          {{ search ? `Results for "${search}"` : "All Products" }}
        </h1>
        <p class="text-gray-500 text-sm mt-1" v-if="!loading">{{ meta.total }} products found</p>
      </div>

      <select
        v-model="sort"
        class="border rounded-lg px-4 py-2 text-sm focus:outline-none focus:border-orange-500"
      >
        <option value="">Sort: Name (A–Z)</option>
        <option value="price_asc">Price: Low to High</option>
        <option value="price_desc">Price: High to Low</option>
        <option value="newest">Newest Arrivals</option>
      </select>
    </div>

    <div class="flex flex-col md:flex-row gap-6">
      <!-- Category filter sidebar -->
      <aside class="md:w-56 shrink-0">
        <div class="bg-white rounded-xl shadow p-4">
          <h3 class="font-semibold text-sm text-gray-700 mb-3">Categories</h3>
          <div class="flex flex-col gap-1">
            <button
              class="text-left px-3 py-2 rounded-lg text-sm transition"
              :class="!category ? 'gradient-primary text-white' : 'hover:bg-orange-50 text-gray-600'"
              @click="category = ''"
            >
              All Products
            </button>
            <button
              v-for="c in categories"
              :key="c.id"
              class="text-left px-3 py-2 rounded-lg text-sm transition flex justify-between"
              :class="category === c.slug ? 'gradient-primary text-white' : 'hover:bg-orange-50 text-gray-600'"
              @click="category = c.slug"
            >
              <span>{{ c.name }}</span>
              <span class="opacity-70">{{ c.products_count }}</span>
            </button>
          </div>
        </div>
      </aside>

      <!-- Product grid -->
      <div class="flex-1">
        <div v-if="loading" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
          <div v-for="i in 8" :key="i" class="skeleton rounded-xl h-64"></div>
        </div>

        <div v-else-if="products.length === 0" class="bg-white rounded-xl shadow p-12 text-center text-gray-400">
          No products found. Try a different search or category.
        </div>

        <div v-else class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
          <ProductCard v-for="p in products" :key="p.id" :product="p" />
        </div>

        <div class="bg-white rounded-xl shadow mt-4" v-if="!loading">
          <Pagination
            :current-page="meta.current_page"
            :last-page="meta.last_page"
            :total="meta.total"
            :from="meta.from"
            :to="meta.to"
            @change="goToPage"
          />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, watch, onMounted } from "vue";
import { useRoute, useRouter } from "vue-router";
import { getProducts, type Product } from "../services/products";
import { getCategories, type Category } from "../services/categories";
import ProductCard from "../components/common/ProductCard.vue";
import Pagination from "../components/common/Pagination.vue";

const route = useRoute();
const router = useRouter();

const search = ref(String(route.query.search ?? ""));
const category = ref(String(route.query.category ?? ""));
const sort = ref(String(route.query.sort ?? ""));
const page = ref(Number(route.query.page ?? 1));

const products = ref<Product[]>([]);
const categories = ref<Category[]>([]);
const loading = ref(false);
const meta = ref({ current_page: 1, last_page: 1, total: 0, from: 0 as number | null, to: 0 as number | null });

async function loadProducts() {
  loading.value = true;
  try {
    const res = await getProducts({
      search: search.value || undefined,
      category: category.value || undefined,
      sort: (sort.value || undefined) as "price_asc" | "price_desc" | "newest" | undefined,
      page: page.value,
    });

    products.value = res.data;
    meta.value = {
      current_page: res.current_page,
      last_page: res.last_page,
      total: res.total,
      from: res.from,
      to: res.to,
    };
  } finally {
    loading.value = false;
  }
}

function goToPage(p: number) {
  page.value = p;
  window.scrollTo({ top: 0, behavior: "smooth" });
}

function syncUrl() {
  router.replace({
    query: {
      ...(search.value ? { search: search.value } : {}),
      ...(category.value ? { category: category.value } : {}),
      ...(sort.value ? { sort: sort.value } : {}),
      ...(page.value > 1 ? { page: String(page.value) } : {}),
    },
  });
}

watch([category, sort], () => {
  page.value = 1;
});

watch([search, category, sort, page], () => {
  syncUrl();
  loadProducts();
});

watch(
  () => route.query.search,
  (value) => {
    search.value = String(value ?? "");
  }
);

onMounted(async () => {
  categories.value = await getCategories();
  await loadProducts();
});
</script>
