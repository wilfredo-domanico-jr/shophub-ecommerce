<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-50 px-4">
    <div class="max-w-md w-full bg-white p-8 rounded-lg shadow-md">
      <h1 class="text-2xl font-bold text-center mb-6 text-gradient-primary">
        Login to ShopHub
      </h1>

      <!-- Demo mode -->
      <div v-if="demoConfig?.demo_mode" class="mb-6 p-4 rounded-lg bg-orange-50 border border-orange-200 text-center">
        <p class="text-sm text-gray-600 mb-3">
          This is a portfolio demo — skip the form and explore the admin panel instantly.
        </p>
        <button
          type="button"
          :disabled="loading"
          class="w-full gradient-primary text-white py-2 rounded font-semibold hover:opacity-90 transition disabled:opacity-50"
          @click="handleDemoLogin"
        >
          {{ loading ? "Logging in..." : "Try Demo Admin Login" }}
        </button>
      </div>

      <div v-if="demoConfig?.demo_mode" class="flex items-center gap-3 mb-6">
        <div class="flex-1 h-px bg-gray-200"></div>
        <span class="text-xs text-gray-400 uppercase">or log in manually</span>
        <div class="flex-1 h-px bg-gray-200"></div>
      </div>

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
import { useToastStore } from "../../stores/toast";
import { getAppConfig, type AppConfig } from "../../services/config";
import type { AxiosError } from "axios";

const auth = useAuthStore();
const router = useRouter();

const email = ref("");
const password = ref("");
const errorMessage = ref("");
const loading = ref(false);
const demoConfig = ref<AppConfig | null>(null);

onMounted(async () => {
  if (auth.user) {
    router.replace("/admin");
    return;
  }

  try {
    demoConfig.value = await getAppConfig();
  } catch {
    demoConfig.value = null;
  }
});

async function handleDemoLogin() {
  if (!demoConfig.value?.demo_admin_email || !demoConfig.value.demo_admin_password) return;

  email.value = demoConfig.value.demo_admin_email;
  password.value = demoConfig.value.demo_admin_password;
  await handleLogin();
}

async function handleLogin() {
  errorMessage.value = "";
  loading.value = true;

  try {
    await auth.login({
      email: email.value,
      password: password.value,
    });

    // Non-admins would just bounce off the /admin guard back to this page —
    // tell them what's happening instead of looping.
    if (!auth.isAdmin) {
      useToastStore().info("This account has no admin access — taking you to the shop.");
      router.push("/");
      return;
    }

    useToastStore().success(`Welcome back, ${auth.user?.name ?? "admin"}!`);
    router.push("/admin");
  } catch (error) {
    const err = error as AxiosError<{ message?: string }>;

    errorMessage.value =
      err.response?.data?.message || "Login failed. Try again.";
  } finally {
    loading.value = false;
  }
}
</script>
