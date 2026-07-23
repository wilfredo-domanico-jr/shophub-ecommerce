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
                :class="categoryColorClass(c.color_class)"
                :style="categoryColorStyle(c.color_class)"
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

          <tr v-if="loading">
            <td colspan="4" class="text-center py-10 text-gray-400">
              Loading categories...
            </td>
          </tr>
          <tr v-else-if="categories.length === 0">
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
      <div class="bg-white w-full max-w-md rounded-xl p-6 space-y-4 max-h-[90vh] overflow-y-auto">
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
          <label class="block mb-1 text-sm font-medium text-gray-700">
            Icon
            <span class="text-gray-400 font-normal">({{ CATEGORY_ICONS.length }} available)</span>
          </label>
          <input
            v-model="iconSearch"
            type="text"
            placeholder="Search icons... e.g. shopping, music, phone"
            class="w-full border p-2 rounded mb-2 text-sm focus:outline-none focus:border-orange-500"
          />
          <div class="grid grid-cols-6 gap-2 max-h-44 overflow-y-auto pr-1">
            <button
              v-for="opt in filteredIcons"
              :key="opt.label"
              type="button"
              :title="opt.label"
              class="aspect-square rounded-lg border-2 flex items-center justify-center transition"
              :class="form.icon === opt.icon ? 'border-orange-500 bg-orange-50' : 'border-gray-200 hover:border-orange-300'"
              @click="form.icon = opt.icon"
            >
              <svg class="w-5 h-5 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" :d="opt.icon" />
              </svg>
            </button>
          </div>
          <p v-if="filteredIcons.length === 0" class="text-xs text-gray-400 text-center py-2">
            No icons match "{{ iconSearch }}"
          </p>
        </div>

        <div>
          <label class="block mb-1 text-sm font-medium text-gray-700">Color</label>
          <div class="grid grid-cols-4 gap-2 mb-2">
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
          <div class="flex items-center gap-3">
            <input
              type="color"
              :value="customColor"
              @input="pickCustomColor(($event.target as HTMLInputElement).value)"
              class="h-9 w-14 rounded-lg border-2 cursor-pointer p-0.5"
              :class="isCustomColorActive ? 'border-gray-800' : 'border-gray-200'"
              title="Pick any custom color"
            />
            <span class="text-xs text-gray-500">
              {{ isCustomColorActive ? `Custom color: ${form.color_class}` : "...or pick any custom color" }}
            </span>
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
            class="bg-orange-500 text-white px-4 py-2 rounded disabled:opacity-50"
            :disabled="saving"
            @click="save"
          >
            {{ saving ? "Saving..." : isEdit ? "Update" : "Add" }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, ref, onMounted, watch } from "vue";
import type { Category } from "../../services/categories";
import {
  getAdminCategories,
  createCategory,
  updateCategory,
  deleteCategory,
} from "../../services/admin/categories";
import Pagination from "../../components/common/Pagination.vue";
import { useToastStore } from "../../stores/toast";
import { CATEGORY_ICONS } from "../../data/categoryIcons";
import { categoryColorClass, categoryColorStyle } from "../../utils/categoryColor";

const toast = useToastStore();

const DEFAULT_ICON =
  CATEGORY_ICONS.find((i) => i.label === "Tag") ?? CATEGORY_ICONS[0]!;

const iconSearch = ref("");
const filteredIcons = computed(() => {
  const term = iconSearch.value.trim().toLowerCase();
  if (!term) return CATEGORY_ICONS;
  return CATEGORY_ICONS.filter((i) => i.label.toLowerCase().includes(term));
});

const COLOR_OPTIONS = [
  { label: "Orange", value: "gradient-primary" },
  { label: "Pink/Purple", value: "gradient-secondary" },
  { label: "Teal/Blue", value: "gradient-accent" },
  { label: "Green", value: "gradient-success" },
];

// The native color input needs a hex value; anything the admin picks there is
// stored in color_class as-is (renderers treat "#..." as a background style).
const customColor = ref("#f97316");
const isCustomColorActive = computed(() => form.value.color_class.startsWith("#"));

function pickCustomColor(hex: string) {
  customColor.value = hex;
  form.value.color_class = hex;
}

const search = ref("");
const page = ref(1);
const categories = ref<Category[]>([]);
const error = ref("");
const formError = ref("");
const loading = ref(false);

const meta = ref({ current_page: 1, last_page: 1, total: 0, from: 0 as number | null, to: 0 as number | null });

async function loadCategories() {
  error.value = "";
  loading.value = true;
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
  } finally {
    loading.value = false;
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
  icon: DEFAULT_ICON.icon,
  color_class: COLOR_OPTIONS[0]!.value,
});

const form = ref<CategoryForm>(emptyForm());

function openAdd() {
  isEdit.value = false;
  formError.value = "";
  iconSearch.value = "";
  form.value = emptyForm();
  showModal.value = true;
}

function openEdit(cat: Category) {
  isEdit.value = true;
  formError.value = "";
  iconSearch.value = "";
  form.value = {
    id: cat.id,
    name: cat.name,
    is_active: cat.is_active,
    icon: cat.icon || DEFAULT_ICON.icon,
    color_class: cat.color_class || COLOR_OPTIONS[0]!.value,
  };
  if (form.value.color_class.startsWith("#")) {
    customColor.value = form.value.color_class;
  }
  showModal.value = true;
}

function closeModal() {
  showModal.value = false;
}

const saving = ref(false);

async function save() {
  if (saving.value) return;
  if (!form.value.name) {
    formError.value = "Name is required.";
    return;
  }
  saving.value = true;

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
  } finally {
    saving.value = false;
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
