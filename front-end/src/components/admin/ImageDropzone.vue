<template>
  <div>
    <div
      class="relative border-2 border-dashed rounded-lg p-4 text-center transition cursor-pointer"
      :class="isDragging ? 'border-orange-500 bg-orange-50' : 'border-gray-300 hover:border-orange-400'"
      @dragover.prevent="isDragging = true"
      @dragleave.prevent="isDragging = false"
      @drop.prevent="onDrop"
      @click="fileInput?.click()"
    >
      <input
        ref="fileInput"
        type="file"
        accept="image/*"
        class="hidden"
        @change="onFileSelected"
      />

      <div v-if="modelValue" class="space-y-2">
        <img :src="modelValue" class="mx-auto h-28 w-28 object-cover rounded-lg" />
        <button
          type="button"
          class="text-xs text-red-500 hover:underline"
          @click.stop="$emit('update:modelValue', '')"
        >
          Remove image
        </button>
      </div>

      <div v-else-if="uploading" class="py-6 text-sm text-gray-500">
        Uploading...
      </div>

      <div v-else class="py-6 text-sm text-gray-500">
        <svg class="w-8 h-8 mx-auto mb-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
        </svg>
        <p>Drag & drop an image, or click to browse</p>
      </div>
    </div>

    <p v-if="error" class="text-red-500 text-xs mt-1">{{ error }}</p>

    <div class="mt-2">
      <label class="text-xs text-gray-500">Or paste an image URL</label>
      <input
        :value="modelValue"
        type="text"
        placeholder="https://..."
        class="w-full border p-2 rounded text-sm mt-1"
        @input="$emit('update:modelValue', ($event.target as HTMLInputElement).value)"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from "vue";
import { uploadImage } from "../../services/admin/upload";

defineProps<{ modelValue: string }>();
const emit = defineEmits<{ (e: "update:modelValue", value: string): void }>();

const fileInput = ref<HTMLInputElement | null>(null);
const isDragging = ref(false);
const uploading = ref(false);
const error = ref("");

async function upload(file: File) {
  if (!file.type.startsWith("image/")) {
    error.value = "Please choose an image file.";
    return;
  }

  error.value = "";
  uploading.value = true;

  try {
    const url = await uploadImage(file);
    emit("update:modelValue", url);
  } catch {
    error.value = "Upload failed. Please try again.";
  } finally {
    uploading.value = false;
  }
}

function onDrop(event: DragEvent) {
  isDragging.value = false;
  const file = event.dataTransfer?.files?.[0];
  if (file) upload(file);
}

function onFileSelected(event: Event) {
  const file = (event.target as HTMLInputElement).files?.[0];
  if (file) upload(file);
}
</script>
