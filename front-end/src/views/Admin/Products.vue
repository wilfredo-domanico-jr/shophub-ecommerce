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

            <td class="text-right p-4 space-x-2">
              <button class="text-blue-500" @click="openEdit(p)">Edit</button>

              <button class="text-red-500" @click="remove(p.id)">Delete</button>
            </td>
          </tr>

          <tr v-if="!loading && products.length === 0">
            <td colspan="6" class="text-center py-10 text-gray-400">
              No products found
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- ================= ADD / EDIT MODAL ================= -->
    <div
      v-if="showModal"
      class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4"
      @click.self="closeModal"
    >
      <div class="bg-white w-full max-w-md rounded-xl p-6 space-y-4">
        <h2 class="text-xl font-bold">
          {{ isEdit ? "Edit Product" : "Add Product" }}
        </h2>

        <p v-if="formError" class="text-red-500 text-sm">{{ formError }}</p>

        <!-- Name -->
        <input
          v-model="form.name"
          type="text"
          placeholder="Product name"
          class="w-full border p-2 rounded"
        />

        <!-- Category -->
        <select v-model.number="form.category_id" class="w-full border p-2 rounded">
          <option :value="0" disabled>Select category</option>
          <option v-for="c in categories" :key="c.id" :value="c.id">
            {{ c.name }}
          </option>
        </select>

        <!-- Price -->
        <input
          v-model.number="form.price"
          type="number"
          placeholder="Price"
          class="w-full border p-2 rounded"
        />

        <!-- Stock -->
        <input
          v-model.number="form.stock_quantity"
          type="number"
          placeholder="Stock"
          class="w-full border p-2 rounded"
        />

        <!-- Image -->
        <input
          v-model="form.image"
          type="text"
          placeholder="Image URL"
          class="w-full border p-2 rounded"
        />

        <!-- Buttons -->
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
import { ref, onMounted } from "vue";
import type { Product } from "../../services/products";
import type { Category } from "../../services/categories";
import { getAdminProducts, createProduct, updateProduct, deleteProduct } from "../../services/admin/products";
import { getAdminCategories } from "../../services/admin/categories";

const products = ref<Product[]>([]);
const categories = ref<Category[]>([]);
const loading = ref(false);
const error = ref("");
const formError = ref("");

async function loadProducts() {
  loading.value = true;
  error.value = "";
  try {
    products.value = await getAdminProducts();
  } catch {
    error.value = "Failed to load products.";
  } finally {
    loading.value = false;
  }
}

onMounted(async () => {
  categories.value = await getAdminCategories();
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
    } else {
      await createProduct(payload);
    }
    closeModal();
    await loadProducts();
  } catch {
    formError.value = "Failed to save product.";
  }
}

async function remove(id: number) {
  if (!confirm("Delete this product?")) return;
  await deleteProduct(id);
  await loadProducts();
}
</script>
