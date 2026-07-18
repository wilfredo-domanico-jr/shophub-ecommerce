<template>
  <router-view />
  <ToastContainer />
</template>

<script setup lang="ts">
import { onMounted } from "vue";
import ToastContainer from "./components/common/ToastContainer.vue";
import { useAuthStore } from "./stores/auth";

const auth = useAuthStore();

onMounted(() => {
  if (!auth.initialized) {
    auth.fetchUser();
  }
});
</script>
