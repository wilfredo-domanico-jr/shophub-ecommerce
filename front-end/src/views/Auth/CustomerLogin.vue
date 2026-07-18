<template>
  <div class="max-w-md mx-auto my-16 px-4">
    <div class="bg-white p-8 rounded-lg shadow-md">
      <h1 class="text-2xl font-bold text-center mb-6 text-gradient-primary">
        Sign In to ShopHub
      </h1>

      <div
        v-if="route.query.reset"
        class="mb-4 p-3 rounded-lg bg-green-50 border border-green-200 text-green-700 text-sm text-center"
      >
        Password reset successful — sign in with your new password.
      </div>

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

        <div class="text-right">
          <router-link to="/forgot-password" class="text-sm text-orange-500 hover:underline">
            Forgot password?
          </router-link>
        </div>

        <button
          type="submit"
          :disabled="loading"
          class="w-full bg-orange-500 text-white py-2 rounded hover:bg-orange-600 transition disabled:opacity-50"
        >
          {{ loading ? "Signing in..." : "Sign In" }}
        </button>
      </form>

      <p class="mt-6 text-sm text-center text-gray-600">
        Don't have an account?
        <router-link to="/register" class="text-orange-500 font-medium hover:underline">
          Create one
        </router-link>
      </p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from "vue";
import { useRoute, useRouter } from "vue-router";
import { useAuthStore } from "../../stores/auth";
import { useToastStore } from "../../stores/toast";
import type { AxiosError } from "axios";

const auth = useAuthStore();
const toast = useToastStore();
const route = useRoute();
const router = useRouter();

const email = ref("");
const password = ref("");
const errorMessage = ref("");
const loading = ref(false);

async function handleLogin() {
  errorMessage.value = "";
  loading.value = true;

  try {
    await auth.login({
      email: email.value,
      password: password.value,
    });

    toast.success(`Welcome back, ${auth.user?.name ?? "shopper"}!`);

    if (auth.isAdmin) {
      router.push("/admin");
    } else {
      router.push((route.query.redirect as string) || "/");
    }
  } catch (error) {
    const err = error as AxiosError<{ message?: string }>;
    errorMessage.value = err.response?.data?.message || "Login failed. Try again.";
  } finally {
    loading.value = false;
  }
}
</script>
