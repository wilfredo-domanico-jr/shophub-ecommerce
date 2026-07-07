<template>
  <div
    v-if="lastPage > 1 || total > 0"
    class="flex flex-col sm:flex-row items-center justify-between gap-3 px-4 py-3 border-t text-sm text-gray-500"
  >
    <p>
      Showing <span class="font-medium text-gray-700">{{ from ?? 0 }}</span>–<span class="font-medium text-gray-700">{{ to ?? 0 }}</span>
      of <span class="font-medium text-gray-700">{{ total }}</span> results
    </p>

    <div class="flex items-center gap-1">
      <button
        class="px-3 py-1.5 rounded-lg border text-gray-600 hover:bg-orange-50 hover:text-orange-600 hover:border-orange-300 transition disabled:opacity-40 disabled:hover:bg-transparent disabled:hover:text-gray-600 disabled:cursor-not-allowed"
        :disabled="currentPage <= 1"
        @click="$emit('change', currentPage - 1)"
      >
        Prev
      </button>

      <button
        v-for="page in pages"
        :key="page"
        class="w-9 h-9 rounded-lg border transition"
        :class="
          page === currentPage
            ? 'gradient-primary text-white border-transparent shadow'
            : 'text-gray-600 hover:bg-orange-50 hover:text-orange-600 hover:border-orange-300'
        "
        @click="$emit('change', page)"
      >
        {{ page }}
      </button>

      <button
        class="px-3 py-1.5 rounded-lg border text-gray-600 hover:bg-orange-50 hover:text-orange-600 hover:border-orange-300 transition disabled:opacity-40 disabled:hover:bg-transparent disabled:hover:text-gray-600 disabled:cursor-not-allowed"
        :disabled="currentPage >= lastPage"
        @click="$emit('change', currentPage + 1)"
      >
        Next
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from "vue";

const props = defineProps<{
  currentPage: number;
  lastPage: number;
  total: number;
  from: number | null;
  to: number | null;
}>();

defineEmits<{ (e: "change", page: number): void }>();

const pages = computed(() => {
  const windowSize = 2;
  const start = Math.max(1, props.currentPage - windowSize);
  const end = Math.min(props.lastPage, props.currentPage + windowSize);
  const result = [];
  for (let p = start; p <= end; p++) result.push(p);
  return result;
});
</script>
