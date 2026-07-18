<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
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
                <p class="font-medium">{{ p.name }}</p>
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
                  p.stock_quantity > 0
                    ? 'bg-green-100 text-green-600'
                    : 'bg-red-100 text-red-500'
                "
              >
                {{ p.stock_quantity > 0 ? "Active" : "Out of stock" }}
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
      <div class="bg-white w-full max-w-md rounded-xl p-6 space-y-4 max-h-[90vh] overflow-y-auto">
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
            <label class="block mb-1 text-sm font-medium text-gray-700">Stock Quantity</label>
            <input
              v-model.number="form.stock_quantity"
              type="number"
              min="0"
              placeholder="0"
              class="w-full border p-2 rounded focus:outline-none focus:border-orange-500"
            />
          </div>
        </div>

        <div>
          <label class="block mb-1 text-sm font-medium text-gray-700">Product Image</label>
          <ImageDropzone v-model="form.image" />
        </div>

        <div class="flex justify-end gap-2 pt-2">
          <button class="px-4 py-2 text-gray-500" @click="closeModal">
            Cancel
          </button>

          <button
            class="bg-orange-500 text-white px-4 py-2 rounded"
            @click="saveProduct"
          >
            {{ isEdit ? "Update" : "Add" }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, watch } from "vue";
import type { Product } from "../../services/products";
import type { Category } from "../../services/categories";
import { getAdminProducts, createProduct, updateProduct, deleteProduct } from "../../services/admin/products";
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
  price: number;
  stock_quantity: number;
  image: string;
};

const emptyForm = (): ProductForm => ({
  id: 0,
  category_id: 0,
  name: "",
  price: 0,
  stock_quantity: 0,
  image: "",
});

const form = ref<ProductForm>(emptyForm());

function openAdd() {
  isEdit.value = false;
  formError.value = "";
  form.value = emptyForm();
  showModal.value = true;
}

function openEdit(product: Product) {
  isEdit.value = true;
  formError.value = "";
  form.value = {
    id: product.id,
    category_id: product.category_id,
    name: product.name,
    price: Number(product.price),
    stock_quantity: product.stock_quantity,
    image: product.image ?? "",
  };
  showModal.value = true;
}

function closeModal() {
  showModal.value = false;
}

async function saveProduct() {
  if (!form.value.name || !form.value.category_id) {
    formError.value = "Name and category are required.";
    return;
  }

  const payload = {
    name: form.value.name,
    category_id: form.value.category_id,
    price: form.value.price,
    stock_quantity: form.value.stock_quantity,
    image: form.value.image || null,
  };

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
  } catch {
    formError.value = "Failed to save product.";
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
