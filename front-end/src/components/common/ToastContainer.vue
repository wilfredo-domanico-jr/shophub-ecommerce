<template>
  <div
    class="fixed bottom-4 right-4 z-[100] flex w-80 max-w-[calc(100vw-2rem)] flex-col gap-2"
    aria-live="polite"
  >
    <TransitionGroup name="toast">
      <div
        v-for="toast in toastStore.toasts"
        :key="toast.id"
        role="alert"
        class="flex items-start gap-3 rounded-lg px-4 py-3 text-sm text-white shadow-lg"
        :class="typeClasses[toast.type]"
      >
        <span class="flex-1">{{ toast.message }}</span>
        <button
          class="font-bold opacity-70 hover:opacity-100"
          aria-label="Dismiss notification"
          @click="toastStore.dismiss(toast.id)"
        >
          ✕
        </button>
      </div>
    </TransitionGroup>
  </div>
</template>

<script setup lang="ts">
import { useToastStore } from "../../stores/toast";
import type { ToastType } from "../../stores/toast";

const toastStore = useToastStore();

const typeClasses: Record<ToastType, string> = {
  success: "bg-green-500",
  error: "bg-red-500",
  info: "bg-orange-500",
};
</script>

<style scoped>
.toast-enter-active,
.toast-leave-active {
  transition: all 0.3s ease;
}
.toast-enter-from {
  opacity: 0;
  transform: translateY(0.5rem);
}
.toast-leave-to {
  opacity: 0;
  transform: translateX(1rem);
}
</style>
