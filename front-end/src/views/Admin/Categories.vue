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
      <div class="relative max-w-sm">
        <svg class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
        </svg>
        <input
          v-model="search"
          type="text"
          placeholder="Search categories..."
          class="w-full pl-9 pr-4 py-2 border rounded-lg focus:outline-none focus:border-orange-500"
        />
      </div>
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
            v-for="c in categories"
            :key="c.id"
            class="border-b hover:bg-gray-50"
          >
            <td class="p-4 font-medium flex items-center gap-3">
              <div
                class="w-9 h-9 rounded-lg flex items-center justify-center text-white shrink-0"
                :class="c.color_class || 'bg-gray-300'"
              >
                <svg v-if="c.icon" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="c.icon" />
                </svg>
              </div>
              {{ c.name }}
            </td>

            <td>{{ c.products_count }} items</td>

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

            <td class="text-right p-4">
              <div class="flex justify-end gap-2">
                <button
                  title="Edit"
                  class="w-8 h-8 flex items-center justify-center rounded-lg text-blue-500 bg-blue-50 hover:bg-blue-500 hover:text-white transition"
                  @click="openEdit(c)"
                >
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                  </svg>
                </button>

                <button
                  title="Delete"
                  class="w-8 h-8 flex items-center justify-center rounded-lg text-red-500 bg-red-50 hover:bg-red-500 hover:text-white transition"
                  @click="remove(c.id)"
                >
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                  </svg>
                </button>
              </div>
            </td>
          </tr>

          <tr v-if="categories.length === 0">
            <td colspan="4" class="text-center py-10 text-gray-400">
              No categories found
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

    <!-- MODAL -->
    <div
      v-if="showModal"
      class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4"
    >
      <div class="bg-white w-full max-w-md rounded-xl p-6 space-y-4">
        <h2 class="text-xl font-bold">
          {{ isEdit ? "Edit Category" : "Add Category" }}
        </h2>

        <p v-if="formError" class="text-red-500 text-sm">{{ formError }}</p>

        <div>
          <label class="block mb-1 text-sm font-medium text-gray-700">Category Name</label>
          <input
            v-model="form.name"
            type="text"
            placeholder="e.g. Electronics"
            class="w-full border p-2 rounded focus:outline-none focus:border-orange-500"
          />
        </div>

        <div>
          <label class="block mb-1 text-sm font-medium text-gray-700">Icon</label>
          <div class="grid grid-cols-6 gap-2">
            <button
              v-for="opt in ICON_OPTIONS"
              :key="opt.icon"
              type="button"
              :title="opt.label"
              class="aspect-square rounded-lg border-2 flex items-center justify-center transition"
              :class="form.icon === opt.icon ? 'border-orange-500 bg-orange-50' : 'border-gray-200 hover:border-orange-300'"
              @click="form.icon = opt.icon"
            >
              <svg class="w-5 h-5 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="opt.icon" />
              </svg>
            </button>
          </div>
        </div>

        <div>
          <label class="block mb-1 text-sm font-medium text-gray-700">Color</label>
          <div class="grid grid-cols-4 gap-2">
            <button
              v-for="opt in COLOR_OPTIONS"
              :key="opt.value"
              type="button"
              :title="opt.label"
              class="h-9 rounded-lg border-2 transition"
              :class="[opt.value, form.color_class === opt.value ? 'border-gray-800' : 'border-transparent']"
              @click="form.color_class = opt.value"
            ></button>
          </div>
        </div>

        <label class="flex items-center gap-2 text-sm">
          <input type="checkbox" v-model="form.is_active" />
          Active
        </label>

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
import { ref, onMounted, watch } from "vue";
import type { Category } from "../../services/categories";
import {
  getAdminCategories,
  createCategory,
  updateCategory,
  deleteCategory,
} from "../../services/admin/categories";
import Pagination from "../../components/common/Pagination.vue";
import { useToastStore } from "../../stores/toast";

const toast = useToastStore();

const ICON_OPTIONS = [
  { label: "Electronics", icon: "M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" },
  { label: "Fashion", icon: "M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" },
  { label: "Home", icon: "M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" },
  { label: "Beauty", icon: "M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" },
  { label: "Sports", icon: "M13 10V3L4 14h7v7l9-11h-7z" },
  { label: "Generic", icon: "M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-5 5a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 10V5a2 2 0 012-2z" },
];

const COLOR_OPTIONS = [
  { label: "Orange", value: "gradient-primary" },
  { label: "Pink/Purple", value: "gradient-secondary" },
  { label: "Teal/Blue", value: "gradient-accent" },
  { label: "Green", value: "gradient-success" },
];

const search = ref("");
const page = ref(1);
const categories = ref<Category[]>([]);
const error = ref("");
const formError = ref("");

const meta = ref({ current_page: 1, last_page: 1, total: 0, from: 0 as number | null, to: 0 as number | null });

async function loadCategories() {
  error.value = "";
  try {
    const res = await getAdminCategories({ search: search.value || undefined, page: page.value });
    categories.value = res.data;
    meta.value = {
      current_page: res.current_page,
      last_page: res.last_page,
      total: res.total,
      from: res.from,
      to: res.to,
    };
  } catch {
    error.value = "Failed to load categories.";
  }
}

function goToPage(p: number) {
  page.value = p;
  loadCategories();
}

let searchTimeout: ReturnType<typeof setTimeout>;
watch(search, () => {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    page.value = 1;
    loadCategories();
  }, 300);
});

onMounted(loadCategories);

// modal
const showModal = ref(false);
const isEdit = ref(false);

type CategoryForm = {
  id: number;
  name: string;
  is_active: boolean;
  icon: string;
  color_class: string;
};

const emptyForm = (): CategoryForm => ({
  id: 0,
  name: "",
  is_active: true,
  icon: ICON_OPTIONS[0]!.icon,
  color_class: COLOR_OPTIONS[0]!.value,
});

const form = ref<CategoryForm>(emptyForm());

function openAdd() {
  isEdit.value = false;
  formError.value = "";
  form.value = emptyForm();
  showModal.value = true;
}

function openEdit(cat: Category) {
  isEdit.value = true;
  formError.value = "";
  form.value = {
    id: cat.id,
    name: cat.name,
    is_active: cat.is_active,
    icon: cat.icon || ICON_OPTIONS[0]!.icon,
    color_class: cat.color_class || COLOR_OPTIONS[0]!.value,
  };
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

  const payload = {
    name: form.value.name,
    is_active: form.value.is_active,
    icon: form.value.icon,
    color_class: form.value.color_class,
  };

  try {
    if (isEdit.value) {
      await updateCategory(form.value.id, payload);
      toast.success("Category updated.");
    } else {
      await createCategory(payload);
      toast.success("Category created.");
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
    toast.success("Category deleted.");
  } catch (e: any) {
    toast.error(e?.response?.data?.message ?? "Failed to delete category.");
  }
}
</script>
