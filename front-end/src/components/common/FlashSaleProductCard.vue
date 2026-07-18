<template>
  <div
    class="bg-white rounded-xl p-4 hover:shadow-lg transition cursor-pointer"
    @click="handleClick"
  >
    <div class="relative mb-3">
      <img
        :src="product.image"
        :alt="product.name"
        class="w-full h-32 object-cover rounded-lg"
      />
      <div
        class="absolute top-2 left-2 text-white text-xs font-bold px-2 py-1 rounded badge-wiggle bg-red-500"
      >
        -{{ product.discount }}%
      </div>
    </div>

    <h4 class="text-sm font-medium mb-2 line-clamp-2">{{ product.name }}</h4>

    <div class="flex items-center gap-2 mb-2">
      <span class="text-orange-500 font-bold text-lg"
        >₱{{ product.price }}</span
      >
      <span class="text-gray-400 text-sm line-through"
        >₱{{ product.originalPrice }}</span
      >
    </div>

    <div class="w-full bg-gray-200 rounded-full h-2 mb-2">
      <div
        class="h-2 rounded-full bg-gradient-to-r from-orange-400 to-red-500"
        :style="{ width: product.progress + '%' }"
      ></div>
    </div>

    <p class="text-xs text-gray-500">{{ product.sold }} sold</p>
  </div>
</template>

<script lang="ts" setup>
interface Product {
  id: number;
  name: string;
  price: number;
  originalPrice: number;
  sold: number;
  discount: number;
  image: string;
  progress: number;
}

const props = defineProps<{
  product: Product;
}>();

const emit = defineEmits<{
  (e: "select", product: Product): void;
}>();

function handleClick() {
  emit("select", props.product);
}
</script>
