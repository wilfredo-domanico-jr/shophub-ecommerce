<template>
  <div class="container mx-auto px-4 py-8">
    <h3 class="font-display text-2xl md:text-3xl font-bold mb-6 text-gray-800">
      Shop By Category
    </h3>

    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
      <ShopByCategoryCard
        v-for="category in categories"
        :key="category.id"
        :category="category"
      />
    </div>
  </div>
</template>

<script lang="ts" setup>
import { ref, onMounted } from "vue";
import ShopByCategoryCard from "../common/ShopByCategoryCard.vue";
import { getCategories } from "../../services/categories";
import { categoryColorClass, categoryColorStyle } from "../../utils/categoryColor";

interface CategoryCard {
  id: number;
  slug: string;
  icon: string;
  title: string;
  itemCount: number;
  gradientClass: string;
  gradientStyle?: Record<string, string>;
}

const FALLBACK_GRADIENTS = [
  "gradient-primary",
  "gradient-secondary",
  "gradient-accent",
  "gradient-success",
];

const FALLBACK_ICON =
  "M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-5 5a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 10V5a2 2 0 012-2z";

const categories = ref<CategoryCard[]>([]);

async function fetchCategories() {
  const data = await getCategories();
  categories.value = data
    .slice()
    .sort((a, b) => b.products_count - a.products_count)
    .slice(0, 4)
    .map((c, index) => ({
      id: c.id,
      slug: c.slug,
      icon: c.icon || FALLBACK_ICON,
      title: c.name,
      itemCount: c.products_count,
      gradientClass: categoryColorClass(
        c.color_class,
        FALLBACK_GRADIENTS[index % FALLBACK_GRADIENTS.length]!
      ),
      gradientStyle: categoryColorStyle(c.color_class),
    }));
}

onMounted(() => {
  fetchCategories();
});
</script>
