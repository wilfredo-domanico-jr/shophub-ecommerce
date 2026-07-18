import { defineStore } from "pinia";
import { ref } from "vue";

export type ToastType = "success" | "error" | "info";

export interface Toast {
  id: number;
  type: ToastType;
  message: string;
}

let nextId = 1;

export const useToastStore = defineStore("toast", () => {
  const toasts = ref<Toast[]>([]);

  function show(type: ToastType, message: string, duration = 3500) {
    const id = nextId++;
    toasts.value.push({ id, type, message });
    setTimeout(() => dismiss(id), duration);
    return id;
  }

  function success(message: string) {
    return show("success", message);
  }

  function error(message: string) {
    return show("error", message, 5000);
  }

  function info(message: string) {
    return show("info", message);
  }

  function dismiss(id: number) {
    toasts.value = toasts.value.filter((t) => t.id !== id);
  }

  return { toasts, show, success, error, info, dismiss };
});
