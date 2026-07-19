<template>
  <router-link
    :to="`/products?category=${category.slug}`"
    :class="category.gradientClass"
    :style="category.gradientStyle"
    class="rounded-xl p-6 text-white hover:shadow-xl transition cursor-pointer transform hover:scale-105 block"
  >
    <svg
      class="w-12 h-12 mb-3"
      fill="none"
      stroke="currentColor"
      viewBox="0 0 24 24"
    >
      <path
        stroke-linecap="round"
        stroke-linejoin="round"
        stroke-width="2"
        :d="category.icon"
      ></path>
    </svg>
    <h4 class="font-semibold text-lg mb-1">{{ category.title }}</h4>
    <p class="text-sm opacity-90">{{ category.itemCount }} items</p>
  </router-link>
</template>

<script lang="ts" setup>
interface CategoryCard {
  id: number;
  slug: string;
  icon: string;
  title: string;
  itemCount: number;
  gradientClass: string;
  gradientStyle?: Record<string, string>;
}

const props = defineProps<{
  category: CategoryCard;
}>();
</script>
