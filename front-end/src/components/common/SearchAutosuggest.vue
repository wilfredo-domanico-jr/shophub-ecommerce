<template>
  <div ref="root" class="relative w-full">
    <input
      v-model="query"
      type="text"
      :placeholder="placeholder"
      class="w-full px-4 py-3 pr-12 border-2 border-orange-400 rounded-lg focus:outline-none focus:border-orange-500 transition"
      @focus="open = true"
      @keydown.escape="open = false"
    />
    <button
      class="absolute right-2 top-1/2 -translate-y-1/2 gradient-primary text-white px-4 py-2 rounded-md hover:opacity-90 transition"
    >
      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
      </svg>
    </button>

    <!-- Suggestions dropdown -->
    <div
      v-if="open && query.length > 0"
      class="absolute left-0 right-0 mt-2 bg-white border rounded-lg shadow-xl z-50 max-h-96 overflow-y-auto"
    >
      <div v-if="loading" class="p-4 text-sm text-gray-400 text-center">
        Searching...
      </div>

      <div v-else-if="suggestions.length === 0" class="p-4 text-sm text-gray-400 text-center">
        No products found for "{{ query }}"
      </div>

      <button
        v-for="product in suggestions"
        :key="product.id"
        type="button"
        class="w-full flex items-center gap-3 p-3 hover:bg-orange-50 transition text-left border-b last:border-b-0"
        @click="select(product)"
      >
        <img :src="product.image ?? ''" class="w-10 h-10 object-cover rounded" />
        <div class="flex-1 min-w-0">
          <p class="text-sm font-medium truncate">{{ product.name }}</p>
          <p v-if="product.category" class="text-xs text-gray-400">{{ product.category.name }}</p>
        </div>
        <span class="text-orange-500 font-semibold text-sm shrink-0">₱{{ product.price }}</span>
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, watch, onMounted, onUnmounted } from "vue";
import { getProducts, type Product } from "../../services/products";
import { useCartStore } from "../../stores/cart";

withDefaults(defineProps<{ placeholder?: string }>(), {
  placeholder: "Search for products, brands, and more...",
});

const cartStore = useCartStore();
const root = ref<HTMLElement | null>(null);
const query = ref("");
const suggestions = ref<Product[]>([]);
const loading = ref(false);
const open = ref(false);

let debounceTimer: ReturnType<typeof setTimeout>;

function onQueryChange() {
  clearTimeout(debounceTimer);

  if (!query.value.trim()) {
    suggestions.value = [];
    return;
  }

  debounceTimer = setTimeout(async () => {
    loading.value = true;
    try {
      const res = await getProducts({ search: query.value.trim(), per_page: 6 });
      suggestions.value = res.data;
    } finally {
      loading.value = false;
    }
  }, 300);
}

function select(product: Product) {
  cartStore.addItem({
    id: product.id,
    name: product.name,
    price: Number(product.price),
    image: product.image ?? "",
  });
  query.value = "";
  suggestions.value = [];
  open.value = false;
}

function handleClickOutside(event: MouseEvent) {
  if (root.value && !root.value.contains(event.target as Node)) {
    open.value = false;
  }
}

onMounted(() => document.addEventListener("click", handleClickOutside));
onUnmounted(() => document.removeEventListener("click", handleClickOutside));

watch(query, onQueryChange);
</script>
