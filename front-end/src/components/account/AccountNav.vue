<template>
  <div class="flex gap-2 mb-6 border-b">
    <router-link
      v-for="tab in tabs"
      :key="tab.to"
      :to="tab.to"
      class="px-4 py-2 -mb-px text-sm font-medium border-b-2 transition"
      :class="
        route.path === tab.to
          ? 'border-orange-500 text-orange-600'
          : 'border-transparent text-gray-500 hover:text-gray-700'
      "
    >
      {{ tab.label }}
    </router-link>
  </div>
</template>

<script setup lang="ts">
import { useRoute } from "vue-router";

const route = useRoute();

const tabs = [
  { to: "/account", label: "Profile" },
  { to: "/account/orders", label: "My Orders" },
];
</script>
