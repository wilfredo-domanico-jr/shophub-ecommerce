<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-50 px-4">
    <div class="max-w-md w-full bg-white p-8 rounded-lg shadow-md">
      <h1 class="text-2xl font-bold text-center mb-6 text-gradient-primary">
        Login to ShopHub
      </h1>

      <!-- Error Message -->
      <div v-if="errorMessage" class="mb-4 text-red-500 text-sm text-center">
        {{ errorMessage }}
      </div>

      <form @submit.prevent="handleLogin" class="space-y-4">
        <div>
          <label class="block mb-1 font-medium" for="email">Email</label>
          <input
            v-model="email"
            id="email"
            type="email"
            required
            autocomplete="username"
            class="w-full px-4 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-orange-400"
            placeholder="you@example.com"
          />
        </div>

        <div>
          <label class="block mb-1 font-medium" for="password">Password</label>
          <input
            v-model="password"
            id="password"
            type="password"
            required
            autocomplete="current-password"
            class="w-full px-4 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-orange-400"
            placeholder="********"
          />
        </div>

        <button
          type="submit"
          :disabled="loading"
          class="w-full bg-orange-500 text-white py-2 rounded hover:bg-orange-600 transition"
        >
          {{ loading ? "Logging in..." : "Login" }}
        </button>
      </form>
    </div>
  </div>
</template>
<script setup lang="ts">
import { ref, onMounted } from "vue";
import { useRouter } from "vue-router";
import { useAuthStore } from "../../stores/auth";
import type { AxiosError } from "axios";

const auth = useAuthStore();
const router = useRouter();

const email = ref("");
const password = ref("");
const errorMessage = ref("");
const loading = ref(false);

onMounted(() => {
  if (auth.user) {
    router.replace("/admin");
  }
});

async function handleLogin() {
  errorMessage.value = "";
  loading.value = true;

  try {
    await auth.login({
      email: email.value,
      password: password.value,
    });

    router.push("/admin");
  } catch (error: any) {
    const err = error as AxiosError<{ message?: string }>;

    errorMessage.value =
      err.response?.data?.message || "Login failed. Try again.";
  } finally {
    loading.value = false;
  }
}
</script>

<style scoped>
.text-gradient-primary {
  background: linear-gradient(135deg, #ff6b35 0%, #ff8c42 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
}
</style>
