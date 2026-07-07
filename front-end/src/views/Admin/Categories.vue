<template>
  <div class="space-y-6">
    <!-- Header -->
    <div
      class="flex flex-col md:flex-row md:items-center md:justify-between gap-4"
    >
      <div>
        <h1 class="text-2xl font-bold">Categories</h1>
        <p class="text-gray-500 text-sm">Manage product categories</p>
      </div>

      <button
        @click="openAdd"
        class="bg-orange-500 text-white px-4 py-2 rounded-lg hover:opacity-90"
      >
        + Add Category
      </button>
    </div>

    <!-- Search -->
    <div class="bg-white p-4 rounded-xl shadow">
      <input
        v-model="search"
        type="text"
        placeholder="Search categories..."
        class="w-full border px-4 py-2 rounded-lg focus:outline-none focus:border-orange-500"
      />
    </div>

    <p v-if="error" class="text-red-500 text-sm">{{ error }}</p>

    <!-- Table -->
    <div class="bg-white rounded-xl shadow overflow-x-auto">
      <table class="w-full text-sm min-w-[600px]">
        <thead class="text-left text-gray-500 border-b">
          <tr>
            <th class="p-4">Category</th>
            <th>Products</th>
            <th>Status</th>
            <th class="text-right p-4">Actions</th>
          </tr>
        </thead>

        <tbody>
          <tr
            v-for="c in filteredCategories"
            :key="c.id"
            class="border-b hover:bg-gray-50"
          >
            <!-- Category -->
            <td class="p-4 font-medium">
              {{ c.name }}
            </td>

            <!-- Product count -->
            <td>{{ c.products_count }} items</td>

            <!-- Status -->
            <td>
              <span
                class="px-2 py-1 text-xs rounded-full"
                :class="
                  c.is_active
                    ? 'bg-green-100 text-green-600'
                    : 'bg-gray-100 text-gray-500'
                "
              >
                {{ c.is_active ? "Active" : "Inactive" }}
              </span>
            </td>

            <!-- Actions -->
            <td class="text-right p-4 space-x-2">
              <button
                class="text-blue-500 hover:underline"
                @click="openEdit(c)"
              >
                Edit
              </button>

              <button
                class="text-red-500 hover:underline"
                @click="remove(c.id)"
              >
                Delete
              </button>
            </td>
          </tr>

          <tr v-if="filteredCategories.length === 0">
            <td colspan="4" class="text-center py-10 text-gray-400">
              No categories found
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- MODAL -->
    <div
      v-if="showModal"
      class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4"
      @click.self="closeModal"
    >
      <div class="bg-white w-full max-w-md rounded-xl p-6 space-y-4">
        <h2 class="text-xl font-bold">
          {{ isEdit ? "Edit Category" : "Add Category" }}
        </h2>

        <p v-if="formError" class="text-red-500 text-sm">{{ formError }}</p>

        <!-- Name -->
        <input
          v-model="form.name"
          type="text"
          placeholder="Category name"
          class="w-full border p-2 rounded"
        />

        <!-- Active -->
        <label class="flex items-center gap-2 text-sm">
          <input type="checkbox" v-model="form.is_active" />
          Active
        </label>

        <!-- Buttons -->
        <div class="flex justify-end gap-2 pt-2">
          <button class="px-4 py-2 text-gray-500" @click="closeModal">
            Cancel
          </button>

          <button
            class="bg-orange-500 text-white px-4 py-2 rounded"
            @click="save"
          >
            {{ isEdit ? "Update" : "Add" }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from "vue";
import type { Category } from "../../services/categories";
import {
  getAdminCategories,
  createCategory,
  updateCategory,
  deleteCategory,
} from "../../services/admin/categories";

const search = ref("");
const categories = ref<Category[]>([]);
const error = ref("");
const formError = ref("");

async function loadCategories() {
  error.value = "";
  try {
    categories.value = await getAdminCategories();
  } catch {
    error.value = "Failed to load categories.";
  }
}

onMounted(loadCategories);

// modal
const showModal = ref(false);
const isEdit = ref(false);

type CategoryForm = { id: number; name: string; is_active: boolean };

const form = ref<CategoryForm>({ id: 0, name: "", is_active: true });

const filteredCategories = computed(() => {
  return categories.value.filter((c) =>
    c.name.toLowerCase().includes(search.value.toLowerCase()),
  );
});

function openAdd() {
  isEdit.value = false;
  formError.value = "";
  form.value = { id: 0, name: "", is_active: true };
  showModal.value = true;
}

function openEdit(cat: Category) {
  isEdit.value = true;
  formError.value = "";
  form.value = { id: cat.id, name: cat.name, is_active: cat.is_active };
  showModal.value = true;
}

function closeModal() {
  showModal.value = false;
}

async function save() {
  if (!form.value.name) {
    formError.value = "Name is required.";
    return;
  }

  try {
    if (isEdit.value) {
      await updateCategory(form.value.id, { name: form.value.name, is_active: form.value.is_active });
    } else {
      await createCategory({ name: form.value.name, is_active: form.value.is_active });
    }
    closeModal();
    await loadCategories();
  } catch {
    formError.value = "Failed to save category.";
  }
}

async function remove(id: number) {
  if (!confirm("Delete this category?")) return;
  try {
    await deleteCategory(id);
    await loadCategories();
  } catch (e: any) {
    error.value = e?.response?.data?.message ?? "Failed to delete category.";
  }
}
</script>
