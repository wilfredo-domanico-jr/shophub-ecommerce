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

interface CategoryCard {
  id: number;
  icon: string;
  title: string;
  itemCount: number;
  gradientClass: string;
}

const categories = ref<CategoryCard[]>([]);

async function fetchCategories() {
  const data = await getCategories();
  categories.value = data.map((c) => ({
    id: c.id,
    icon: c.icon ?? "",
    title: c.name,
    itemCount: c.products_count,
    gradientClass: c.color_class ?? "gradient-primary",
  }));
}

onMounted(() => {
  fetchCategories();
});
</script>
