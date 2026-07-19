<template>
  <div class="bg-white border-b border-gray-200 top-[72px] md:top-[88px] z-40">
    <div class="container mx-auto px-4">
      <div class="flex justify-between gap-2 overflow-x-auto py-4 scrollbar-hide">
        <router-link
          v-for="category in categories"
          :key="category.id"
          :to="`/products?category=${category.slug}`"
          class="flex flex-col items-center gap-2 min-w-[80px] flex-1 group"
        >
          <div
            class="category-icon w-12 h-12 rounded-full flex items-center justify-center text-white"
            :class="category.gradientClass"
            :style="category.gradientStyle"
          >
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="category.icon" />
            </svg>
          </div>
          <span class="text-xs font-medium text-gray-700 group-hover:text-orange-500">
            {{ category.name }}
          </span>
        </router-link>
      </div>
    </div>
  </div>
</template>

<script lang="ts" setup>
import { ref, onMounted } from "vue";
import { getCategories } from "../../services/categories";
import { categoryColorClass, categoryColorStyle } from "../../utils/categoryColor";

interface CategoryBarItem {
  id: number;
  slug: string;
  name: string;
  icon: string;
  gradientClass: string;
  gradientStyle?: Record<string, string>;
}

const MAX_CATEGORIES = 10;

const FALLBACK_GRADIENTS = [
  "gradient-primary",
  "gradient-secondary",
  "gradient-accent",
  "gradient-success",
];

const FALLBACK_ICON =
  "M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-5 5a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 10V5a2 2 0 012-2z";

const categories = ref<CategoryBarItem[]>([]);

async function fetchCategories() {
  const data = await getCategories();
  categories.value = data
    .slice()
    .sort((a, b) => a.name.localeCompare(b.name))
    .slice(0, MAX_CATEGORIES)
    .map((c, index) => ({
      id: c.id,
      slug: c.slug,
      name: c.name,
      icon: c.icon || FALLBACK_ICON,
      gradientClass: categoryColorClass(
        c.color_class,
        FALLBACK_GRADIENTS[index % FALLBACK_GRADIENTS.length]!
      ),
      gradientStyle: categoryColorStyle(c.color_class),
    }));
}

onMounted(() => {
  fetchCategories().catch(() => {});
});
</script>
