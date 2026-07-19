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
      <nav class="text-sm text-gray-500 mb-6 flex flex-wrap items-center gap-2">
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
          <img :src="effectiveImage" :alt="product.name" class="w-full h-96 object-cover" />
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
            <span class="text-3xl font-bold text-orange-500">₱{{ effectivePrice }}</span>
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

          <!-- Option pickers (Color, Size, ...) -->
          <div v-if="hasOptions" class="space-y-4 mb-6">
            <div v-for="option in options" :key="option.name">
              <p class="text-sm font-medium text-gray-700 mb-2">
                {{ option.name }}:
                <span class="text-gray-500 font-normal">{{ selected[option.name] ?? "Select" }}</span>
              </p>
              <div class="flex flex-wrap gap-2">
                <button
                  v-for="value in option.values"
                  :key="value"
                  type="button"
                  class="px-4 py-2 rounded-lg border text-sm font-medium transition"
                  :class="[
                    selected[option.name] === value
                      ? 'border-orange-500 bg-orange-50 text-orange-600'
                      : 'border-gray-300 text-gray-700 hover:border-orange-300',
                    !isValueAvailable(option.name, value) ? 'opacity-40 line-through cursor-not-allowed' : '',
                  ]"
                  :disabled="!isValueAvailable(option.name, value)"
                  @click="selectValue(option.name, value)"
                >
                  {{ value }}
                </button>
              </div>
            </div>
          </div>

          <p class="text-sm mb-4" :class="effectiveStock > 0 ? 'text-green-600' : 'text-red-500'">
            {{ effectiveStock > 0 ? `${effectiveStock} in stock` : "Out of stock" }}
          </p>

          <div v-if="product.stock_quantity > 0" class="mb-6">
            <div class="flex flex-wrap items-center gap-3">
              <div class="flex items-center border rounded-lg">
                <button class="w-10 h-10 flex items-center justify-center hover:bg-gray-100" @click="quantity = Math.max(1, quantity - 1)">
                  -
                </button>
                <span class="w-12 text-center">{{ quantity }}</span>
                <button class="w-10 h-10 flex items-center justify-center hover:bg-gray-100" @click="quantity = Math.min(effectiveStock || 1, quantity + 1)">
                  +
                </button>
              </div>

              <button
                class="flex-1 min-w-[130px] border-2 border-orange-500 text-orange-500 py-3 rounded-lg font-semibold hover:bg-orange-50 transition disabled:opacity-50 disabled:cursor-not-allowed"
                :disabled="!canPurchase"
                @click="addToCart"
              >
                {{ added ? "Added! ✓" : "Add to Cart" }}
              </button>

              <button
                class="flex-1 min-w-[130px] gradient-primary text-white py-3 rounded-lg font-semibold hover:opacity-90 transition disabled:opacity-50 disabled:cursor-not-allowed"
                :disabled="!canPurchase"
                @click="buyNow"
              >
                Buy Now
              </button>
            </div>

            <p v-if="hasOptions && !selectedVariant" class="text-xs text-gray-500 mt-2">
              Select {{ options.map((o) => o.name.toLowerCase()).join(" and ") }} to continue.
            </p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted } from "vue";
import { useRoute, useRouter } from "vue-router";
import { getProduct, type Product, type ProductVariant } from "../services/products";
import { useAddToCart } from "../composables/useAddToCart";
import { useCartStore } from "../stores/cart";
import StarRating from "../components/common/StarRating.vue";

const route = useRoute();
const router = useRouter();
const cartStore = useCartStore();
const { addToCart: addItem, ensureSignedIn } = useAddToCart();

const product = ref<Product | null>(null);
const loading = ref(false);
const quantity = ref(1);
const added = ref(false);

// One value per option name, e.g. { Color: "Red", Size: "M" }
const selected = ref<Record<string, string>>({});

const options = computed(() => product.value?.options ?? []);
const hasOptions = computed(() => options.value.length > 0);

const selectedVariant = computed<ProductVariant | null>(() => {
  if (!product.value || !hasOptions.value) return null;
  if (!options.value.every((o) => !!selected.value[o.name])) return null;

  return (
    product.value.variants?.find((v) =>
      options.value.every((o) => v.option_values[o.name] === selected.value[o.name])
    ) ?? null
  );
});

// Variant price/image are nullable overrides — fall back to the product's.
const effectivePrice = computed(
  () => selectedVariant.value?.price ?? product.value?.price ?? "0"
);
const effectiveImage = computed(
  () => selectedVariant.value?.image ?? product.value?.image ?? ""
);
const effectiveStock = computed(() => {
  if (!product.value) return 0;
  return selectedVariant.value?.stock_quantity ?? product.value.stock_quantity;
});

const canPurchase = computed(
  () =>
    !hasOptions.value ||
    (!!selectedVariant.value && selectedVariant.value.stock_quantity > 0)
);

const variantLabel = computed(() =>
  options.value
    .map((o) => selected.value[o.name])
    .filter(Boolean)
    .join(" / ")
);

// A value is pickable when some in-stock variant has it alongside the
// user's current picks for the other options.
function isValueAvailable(name: string, value: string): boolean {
  return (product.value?.variants ?? []).some(
    (v) =>
      v.option_values[name] === value &&
      v.stock_quantity > 0 &&
      options.value.every((o) => {
        if (o.name === name) return true;
        const pick = selected.value[o.name];
        return !pick || v.option_values[o.name] === pick;
      })
  );
}

function selectValue(name: string, value: string) {
  if (selected.value[name] === value) {
    delete selected.value[name];
  } else {
    selected.value[name] = value;
  }
  quantity.value = 1;
}

const discount = computed(() => {
  if (!product.value) return 0;
  const price = Number(effectivePrice.value);
  const original = Number(product.value.original_price ?? effectivePrice.value);
  return original > 0 ? Math.round(((original - price) / original) * 100) : 0;
});

async function loadProduct() {
  loading.value = true;
  product.value = null;
  quantity.value = 1;
  selected.value = {};
  try {
    product.value = await getProduct(String(route.params.slug));
  } catch {
    product.value = null;
  } finally {
    loading.value = false;
  }
}

function purchasePayload() {
  return {
    id: product.value!.id,
    name: product.value!.name,
    price: Number(effectivePrice.value),
    image: effectiveImage.value,
    variant_id: selectedVariant.value?.id ?? null,
    variant_label: selectedVariant.value ? variantLabel.value : null,
  };
}

async function addToCart() {
  if (!product.value || !canPurchase.value) return;

  const ok = await addItem(purchasePayload(), quantity.value);
  if (!ok) return;

  added.value = true;
  setTimeout(() => (added.value = false), 1500);
}

async function buyNow() {
  if (!product.value || !canPurchase.value) return;
  if (!(await ensureSignedIn("Sign in to buy this item."))) return;

  // Buy-now checks out only this item, without touching the cart.
  cartStore.setBuyNow(purchasePayload(), quantity.value);

  // ?checkout=1 is picked up by DefaultLayout, which opens the checkout modal.
  router.push({ path: route.path, query: { checkout: "1" } });
}

watch(() => route.params.slug, loadProduct);
onMounted(loadProduct);
</script>
