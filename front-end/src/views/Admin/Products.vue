<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold">Products</h1>
        <p class="text-gray-500 text-sm">Manage your store products</p>
      </div>

      <button
        @click="openAdd"
        class="bg-orange-500 text-white px-4 py-2 rounded-lg hover:opacity-90"
      >
        + Add Product
      </button>
    </div>

    <!-- Search -->
    <div class="bg-white p-4 rounded-xl shadow">
      <div class="relative max-w-sm">
        <svg class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
        </svg>
        <input
          v-model="search"
          type="text"
          placeholder="Search products..."
          class="w-full pl-9 pr-4 py-2 border rounded-lg focus:outline-none focus:border-orange-500"
        />
      </div>
    </div>

    <p v-if="error" class="text-red-500 text-sm">{{ error }}</p>

    <!-- Table -->
    <div class="bg-white rounded-xl shadow overflow-x-auto">
      <table class="w-full text-sm min-w-[600px]">
        <thead class="border-b text-left text-gray-500">
          <tr>
            <th class="p-4">Product</th>
            <th>Category</th>
            <th>Price</th>
            <th>Stock</th>
            <th>Status</th>
            <th class="text-right p-4">Actions</th>
          </tr>
        </thead>

        <tbody>
          <tr
            v-for="p in products"
            :key="p.id"
            class="border-b hover:bg-gray-50"
          >
            <td class="p-4 flex items-center gap-3">
              <img :src="p.image ?? ''" class="w-10 h-10 rounded object-cover" />
              <div>
                <p class="font-medium">
                  {{ p.name }}
                  <span
                    v-if="p.variants_count"
                    class="ml-1 px-1.5 py-0.5 text-[10px] rounded-full bg-purple-100 text-purple-600 align-middle"
                  >
                    {{ p.variants_count }} variants
                  </span>
                </p>
                <p class="text-xs text-gray-400">ID: {{ p.id }}</p>
              </div>
            </td>

            <td>{{ p.category?.name ?? "—" }}</td>
            <td>₱{{ p.price }}</td>
            <td>{{ p.stock_quantity }}</td>

            <td>
              <span
                class="px-2 py-1 text-xs rounded-full"
                :class="
                  !p.is_active
                    ? 'bg-gray-100 text-gray-500'
                    : p.stock_quantity > 0
                      ? 'bg-green-100 text-green-600'
                      : 'bg-red-100 text-red-500'
                "
              >
                {{ !p.is_active ? "Inactive" : p.stock_quantity > 0 ? "Active" : "Out of stock" }}
              </span>
            </td>

            <td class="text-right p-4">
              <div class="flex justify-end gap-2">
                <button
                  title="Edit"
                  class="w-8 h-8 flex items-center justify-center rounded-lg text-blue-500 bg-blue-50 hover:bg-blue-500 hover:text-white transition"
                  @click="openEdit(p)"
                >
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                  </svg>
                </button>

                <button
                  title="Delete"
                  class="w-8 h-8 flex items-center justify-center rounded-lg text-red-500 bg-red-50 hover:bg-red-500 hover:text-white transition"
                  @click="remove(p.id)"
                >
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                  </svg>
                </button>
              </div>
            </td>
          </tr>

          <tr v-if="!loading && products.length === 0">
            <td colspan="6" class="text-center py-10 text-gray-400">
              No products found
            </td>
          </tr>
        </tbody>
      </table>

      <Pagination
        :current-page="meta.current_page"
        :last-page="meta.last_page"
        :total="meta.total"
        :from="meta.from"
        :to="meta.to"
        @change="goToPage"
      />
    </div>

    <!-- Add / edit modal -->
    <div
      v-if="showModal"
      class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4"
    >
      <div class="bg-white w-full max-w-xl rounded-xl p-6 space-y-4 max-h-[90vh] overflow-y-auto">
        <h2 class="text-xl font-bold">
          {{ isEdit ? "Edit Product" : "Add Product" }}
        </h2>

        <p v-if="formError" class="text-red-500 text-sm">{{ formError }}</p>

        <div>
          <label class="block mb-1 text-sm font-medium text-gray-700">Product Name</label>
          <input
            v-model="form.name"
            type="text"
            placeholder="e.g. Wireless Bluetooth Earbuds"
            class="w-full border p-2 rounded focus:outline-none focus:border-orange-500"
          />
        </div>

        <div>
          <label class="block mb-1 text-sm font-medium text-gray-700">Description</label>
          <textarea
            v-model="form.description"
            rows="3"
            placeholder="What makes this product great?"
            class="w-full border p-2 rounded focus:outline-none focus:border-orange-500"
          ></textarea>
        </div>

        <div>
          <label class="block mb-1 text-sm font-medium text-gray-700">Category</label>
          <select v-model.number="form.category_id" class="w-full border p-2 rounded focus:outline-none focus:border-orange-500">
            <option :value="0" disabled>Select category</option>
            <option v-for="c in categories" :key="c.id" :value="c.id">
              {{ c.name }}
            </option>
          </select>
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block mb-1 text-sm font-medium text-gray-700">Price (₱)</label>
            <input
              v-model.number="form.price"
              type="number"
              min="0"
              placeholder="0.00"
              class="w-full border p-2 rounded focus:outline-none focus:border-orange-500"
            />
          </div>

          <div>
            <label class="block mb-1 text-sm font-medium text-gray-700">Original Price (₱, optional)</label>
            <input
              v-model.number="form.original_price"
              type="number"
              min="0"
              placeholder="No compare-at price"
              class="w-full border p-2 rounded focus:outline-none focus:border-orange-500"
            />
            <p class="text-xs text-gray-400 mt-1">Shown struck-through; drives the discount %.</p>
          </div>
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block mb-1 text-sm font-medium text-gray-700">Stock Quantity</label>
            <input
              :value="hasVariants ? variantStockTotal : form.stock_quantity"
              :disabled="hasVariants"
              type="number"
              min="0"
              placeholder="0"
              class="w-full border p-2 rounded focus:outline-none focus:border-orange-500 disabled:bg-gray-50 disabled:text-gray-400"
              @input="form.stock_quantity = Number(($event.target as HTMLInputElement).value) || 0"
            />
            <p v-if="hasVariants" class="text-xs text-gray-400 mt-1">Derived from variant stocks.</p>
          </div>
        </div>

        <div>
          <label class="block mb-1 text-sm font-medium text-gray-700">Product Image</label>
          <ImageDropzone v-model="form.image" />
        </div>

        <!-- Flags -->
        <div class="space-y-2 border-t pt-4">
          <label class="flex items-center gap-2 text-sm cursor-pointer">
            <input v-model="form.is_featured" type="checkbox" class="accent-orange-500" />
            Featured (shown in the homepage trending section)
          </label>

          <label class="flex items-center gap-2 text-sm cursor-pointer">
            <input
              v-model="form.is_flash_sale"
              type="checkbox"
              class="accent-orange-500"
              @change="!form.is_flash_sale ? (form.flash_sale_goal = null) : null"
            />
            Flash sale item (shown during scheduled flash sales)
          </label>

          <div v-if="form.is_flash_sale" class="pl-6">
            <label class="block mb-1 text-xs text-gray-500">Sale goal (units, optional — drives the "sold" progress bar)</label>
            <input
              v-model.number="form.flash_sale_goal"
              type="number"
              min="1"
              placeholder="Auto"
              class="w-40 border p-2 rounded text-sm focus:outline-none focus:border-orange-500"
            />
          </div>

          <label class="flex items-center gap-2 text-sm cursor-pointer">
            <input v-model="form.is_active" type="checkbox" class="accent-orange-500" />
            Active (visible in the store)
          </label>
        </div>

        <!-- Variations -->
        <div class="border-t pt-4">
          <label class="flex items-center gap-2 text-sm font-medium text-gray-700 cursor-pointer">
            <input v-model="hasVariants" type="checkbox" class="accent-orange-500" />
            This product has variations (e.g. Color, Size)
          </label>
        </div>

        <template v-if="hasVariants">
          <div class="space-y-2">
            <div class="flex items-center justify-between">
              <p class="text-sm font-medium text-gray-700">Options</p>
              <button
                v-if="optionRows.length < 3"
                type="button"
                class="text-xs text-orange-500 hover:underline"
                @click="optionRows.push({ name: '', valuesText: '' })"
              >
                + Add option
              </button>
            </div>

            <div v-for="(row, i) in optionRows" :key="i" class="flex flex-col sm:flex-row gap-2 sm:items-center">
              <input
                v-model="row.name"
                type="text"
                placeholder="Name (e.g. Color)"
                class="w-full sm:w-32 border p-2 rounded text-sm focus:outline-none focus:border-orange-500"
              />
              <input
                v-model="row.valuesText"
                type="text"
                placeholder="Values, comma separated (e.g. Red, Blue, Black)"
                class="flex-1 border p-2 rounded text-sm focus:outline-none focus:border-orange-500"
              />
              <button
                type="button"
                class="text-red-400 hover:text-red-600 px-1"
                title="Remove option"
                @click="optionRows.splice(i, 1)"
              >
                ✕
              </button>
            </div>

            <button
              type="button"
              class="w-full border border-dashed border-orange-300 text-orange-500 text-sm py-2 rounded-lg hover:bg-orange-50"
              @click="generateCombinations"
            >
              Generate combinations
            </button>
            <p class="text-xs text-gray-400">
              Regenerating keeps price, stock, and image for combinations that still match;
              combinations that no longer exist are removed.
            </p>
          </div>

          <div v-if="variantRows.length" class="space-y-2">
            <p class="text-sm font-medium text-gray-700">
              Variants ({{ variantRows.length }}) — total stock {{ variantStockTotal }}
            </p>

            <div
              v-for="(row, i) in variantRows"
              :key="comboKey(row.option_values)"
              class="border rounded-lg p-3 space-y-2"
            >
              <div class="flex items-center justify-between">
                <p class="text-sm font-semibold">{{ variantRowLabel(row) }}</p>
                <button
                  type="button"
                  class="text-xs text-red-500 hover:underline"
                  @click="variantRows.splice(i, 1)"
                >
                  Remove
                </button>
              </div>

              <div class="grid grid-cols-2 gap-2">
                <div>
                  <label class="text-xs text-gray-500">Price override (blank = ₱{{ form.price }})</label>
                  <input
                    v-model.number="row.price"
                    type="number"
                    min="0"
                    :placeholder="String(form.price)"
                    class="w-full border p-2 rounded text-sm focus:outline-none focus:border-orange-500"
                  />
                </div>
                <div>
                  <label class="text-xs text-gray-500">Stock</label>
                  <input
                    v-model.number="row.stock_quantity"
                    type="number"
                    min="0"
                    class="w-full border p-2 rounded text-sm focus:outline-none focus:border-orange-500"
                  />
                </div>
              </div>

              <div>
                <label class="text-xs text-gray-500">Variant image (optional — falls back to the product image)</label>
                <ImageDropzone v-model="row.image" compact />
              </div>
            </div>
          </div>
        </template>

        <div class="flex justify-end gap-2 pt-2">
          <button class="px-4 py-2 text-gray-500" @click="closeModal">
            Cancel
          </button>

          <button
            class="bg-orange-500 text-white px-4 py-2 rounded disabled:opacity-50"
            :disabled="saving"
            @click="saveProduct"
          >
            {{ saving ? "Saving..." : isEdit ? "Update" : "Add" }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from "vue";
import type { Product } from "../../services/products";
import type { Category } from "../../services/categories";
import {
  getAdminProducts,
  createProduct,
  updateProduct,
  deleteProduct,
  type ProductPayload,
} from "../../services/admin/products";
import { getAdminCategories } from "../../services/admin/categories";
import ImageDropzone from "../../components/admin/ImageDropzone.vue";
import Pagination from "../../components/common/Pagination.vue";
import { useToastStore } from "../../stores/toast";

const toast = useToastStore();

const products = ref<Product[]>([]);
const categories = ref<Category[]>([]);
const loading = ref(false);
const error = ref("");
const formError = ref("");
const search = ref("");
const page = ref(1);

const meta = ref({ current_page: 1, last_page: 1, total: 0, from: 0 as number | null, to: 0 as number | null });

async function loadProducts() {
  loading.value = true;
  error.value = "";
  try {
    const res = await getAdminProducts({ search: search.value || undefined, page: page.value });
    products.value = res.data;
    meta.value = {
      current_page: res.current_page,
      last_page: res.last_page,
      total: res.total,
      from: res.from,
      to: res.to,
    };
  } catch {
    error.value = "Failed to load products.";
  } finally {
    loading.value = false;
  }
}

function goToPage(p: number) {
  page.value = p;
  loadProducts();
}

let searchTimeout: ReturnType<typeof setTimeout>;
watch(search, () => {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    page.value = 1;
    loadProducts();
  }, 300);
});

onMounted(async () => {
  categories.value = (await getAdminCategories({ per_page: 100 })).data;
  await loadProducts();
});

// modal state
const showModal = ref(false);
const isEdit = ref(false);

type ProductForm = {
  id: number;
  category_id: number;
  name: string;
  description: string;
  price: number;
  original_price: number | "" | null;
  stock_quantity: number;
  image: string;
  is_featured: boolean;
  is_flash_sale: boolean;
  flash_sale_goal: number | "" | null;
  is_active: boolean;
};

const emptyForm = (): ProductForm => ({
  id: 0,
  category_id: 0,
  name: "",
  description: "",
  price: 0,
  original_price: null,
  stock_quantity: 0,
  image: "",
  is_featured: false,
  is_flash_sale: false,
  flash_sale_goal: null,
  is_active: true,
});

const numberOrNull = (value: number | "" | null) =>
  value === "" || value === null ? null : Number(value);

const form = ref<ProductForm>(emptyForm());

// --- Variations ---
type OptionRow = { name: string; valuesText: string };
type VariantRow = {
  id?: number;
  option_values: Record<string, string>;
  price: number | "" | null; // "" = cleared input -> inherit product price
  stock_quantity: number;
  image: string;
};

const hasVariants = ref(false);
const optionRows = ref<OptionRow[]>([]);
const variantRows = ref<VariantRow[]>([]);

const variantStockTotal = computed(() =>
  variantRows.value.reduce((sum, row) => sum + (Number(row.stock_quantity) || 0), 0)
);

function parsedOptions() {
  return optionRows.value
    .map((row) => ({
      name: row.name.trim(),
      values: row.valuesText.split(",").map((v) => v.trim()).filter(Boolean),
    }))
    .filter((o) => o.name && o.values.length);
}

// Mirrors the backend's variant key: sorted, lowercased "name=value" pairs.
function comboKey(values: Record<string, string>) {
  return Object.entries(values)
    .map(([name, value]) => `${name.trim().toLowerCase()}=${value.trim().toLowerCase()}`)
    .sort()
    .join("|");
}

function variantRowLabel(row: VariantRow) {
  const names = parsedOptions().map((o) => o.name);
  const ordered = names.map((n) => row.option_values[n]).filter(Boolean);
  return (ordered.length ? ordered : Object.values(row.option_values)).join(" / ");
}

function generateCombinations() {
  const options = parsedOptions();
  if (!options.length) {
    formError.value = "Add at least one option with values first.";
    return;
  }
  formError.value = "";

  let combos: Record<string, string>[] = [{}];
  for (const option of options) {
    combos = combos.flatMap((combo) =>
      option.values.map((value) => ({ ...combo, [option.name]: value }))
    );
  }

  const existing = new Map(variantRows.value.map((row) => [comboKey(row.option_values), row]));

  variantRows.value = combos.map((combo) => {
    const match = existing.get(comboKey(combo));
    return match
      ? { ...match, option_values: combo }
      : { option_values: combo, price: null, stock_quantity: 0, image: "" };
  });
}

function resetVariantState(product?: Product) {
  hasVariants.value = !!product?.options?.length;
  optionRows.value = (product?.options ?? []).map((o) => ({
    name: o.name,
    valuesText: o.values.join(", "),
  }));
  variantRows.value = (product?.variants ?? []).map((v) => ({
    id: v.id,
    option_values: { ...v.option_values },
    price: v.price === null ? null : Number(v.price),
    stock_quantity: v.stock_quantity,
    image: v.image ?? "",
  }));
}

function openAdd() {
  isEdit.value = false;
  formError.value = "";
  form.value = emptyForm();
  resetVariantState();
  showModal.value = true;
}

function openEdit(product: Product) {
  isEdit.value = true;
  formError.value = "";
  form.value = {
    id: product.id,
    category_id: product.category_id,
    name: product.name,
    description: product.description ?? "",
    price: Number(product.price),
    original_price: product.original_price !== null ? Number(product.original_price) : null,
    stock_quantity: product.stock_quantity,
    image: product.image ?? "",
    is_featured: product.is_featured,
    is_flash_sale: product.is_flash_sale,
    flash_sale_goal: product.flash_sale_goal,
    is_active: product.is_active,
  };
  resetVariantState(product);
  showModal.value = true;
}

function closeModal() {
  showModal.value = false;
}

const saving = ref(false);

async function saveProduct() {
  if (saving.value) return;
  if (!form.value.name || !form.value.category_id) {
    formError.value = "Name and category are required.";
    return;
  }

  const payload: ProductPayload = {
    name: form.value.name,
    category_id: form.value.category_id,
    description: form.value.description.trim() || null,
    price: form.value.price,
    original_price: numberOrNull(form.value.original_price),
    stock_quantity: form.value.stock_quantity,
    image: form.value.image || null,
    is_featured: form.value.is_featured,
    is_flash_sale: form.value.is_flash_sale,
    flash_sale_goal: form.value.is_flash_sale ? numberOrNull(form.value.flash_sale_goal) : null,
    is_active: form.value.is_active,
  };

  if (hasVariants.value) {
    const options = parsedOptions();
    if (!options.length) {
      formError.value = "Add at least one option with values (e.g. Color: Red, Blue).";
      return;
    }
    if (!variantRows.value.length) {
      formError.value = 'Click "Generate combinations" to create the variants.';
      return;
    }

    payload.options = options;
    payload.variants = variantRows.value.map((row) => ({
      id: row.id,
      option_values: row.option_values,
      price: row.price === null || row.price === "" ? null : Number(row.price),
      stock_quantity: Number(row.stock_quantity) || 0,
      image: row.image || null,
    }));
  } else {
    // Explicitly clear so a formerly-variant product goes back to flat.
    payload.options = null;
    payload.variants = [];
  }

  saving.value = true;
  try {
    if (isEdit.value) {
      await updateProduct(form.value.id, payload);
      toast.success("Product updated.");
    } else {
      await createProduct(payload);
      toast.success("Product created.");
    }
    closeModal();
    await loadProducts();
  } catch (e: any) {
    const errors = e?.response?.data?.errors;
    const first = errors && (Object.values(errors)[0] as string[] | undefined)?.[0];
    formError.value = first ?? "Failed to save product.";
  } finally {
    saving.value = false;
  }
}

async function remove(id: number) {
  if (!confirm("Delete this product?")) return;
  try {
    await deleteProduct(id);
    await loadProducts();
    toast.success("Product deleted.");
  } catch (e: any) {
    toast.error(e?.response?.data?.message ?? "Failed to delete product.");
  }
}
</script>
