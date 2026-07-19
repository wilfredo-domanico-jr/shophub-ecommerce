<template>
  <div class="max-w-md mx-auto my-16 px-4">
    <div class="bg-white p-8 rounded-lg shadow-md">
      <h1 class="text-2xl font-bold text-center mb-6 text-gradient-primary">
        Create Your Account
      </h1>

      <SocialLoginButtons :providers="socialProviders" />

      <div v-if="socialProviders.length" class="flex items-center gap-3 mb-6">
        <div class="flex-1 h-px bg-gray-200"></div>
        <span class="text-xs text-gray-400 uppercase">or continue with email</span>
        <div class="flex-1 h-px bg-gray-200"></div>
      </div>

      <div v-if="errorMessage" class="mb-4 text-red-500 text-sm text-center">
        {{ errorMessage }}
      </div>

      <form @submit.prevent="handleRegister" class="space-y-4">
        <div>
          <label class="block mb-1 font-medium" for="name">Full Name</label>
          <input
            v-model="form.name"
            id="name"
            type="text"
            required
            autocomplete="name"
            class="w-full px-4 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-orange-400"
            placeholder="Juan Dela Cruz"
          />
        </div>

        <div>
          <label class="block mb-1 font-medium" for="email">Email</label>
          <input
            v-model="form.email"
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
            v-model="form.password"
            id="password"
            type="password"
            required
            minlength="8"
            autocomplete="new-password"
            class="w-full px-4 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-orange-400"
            placeholder="At least 8 characters"
          />
        </div>

        <div>
          <label class="block mb-1 font-medium" for="password_confirmation">Confirm Password</label>
          <input
            v-model="form.password_confirmation"
            id="password_confirmation"
            type="password"
            required
            minlength="8"
            autocomplete="new-password"
            class="w-full px-4 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-orange-400"
            placeholder="Repeat your password"
          />
        </div>

        <button
          type="submit"
          :disabled="loading"
          class="w-full bg-orange-500 text-white py-2 rounded hover:bg-orange-600 transition disabled:opacity-50"
        >
          {{ loading ? "Creating account..." : "Create Account" }}
        </button>
      </form>

      <p class="mt-6 text-sm text-center text-gray-600">
        Already have an account?
        <router-link to="/login" class="text-orange-500 font-medium hover:underline">
          Sign in
        </router-link>
      </p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from "vue";
import { useRouter } from "vue-router";
import { useAuthStore } from "../../stores/auth";
import { useToastStore } from "../../stores/toast";
import { firstValidationError } from "../../services/account";
import { getAppConfig } from "../../services/config";
import SocialLoginButtons from "../../components/auth/SocialLoginButtons.vue";

const auth = useAuthStore();
const router = useRouter();

const socialProviders = ref<string[]>([]);

onMounted(async () => {
  try {
    socialProviders.value = (await getAppConfig()).social_providers ?? [];
  } catch {
    socialProviders.value = [];
  }
});

const form = ref({
  name: "",
  email: "",
  password: "",
  password_confirmation: "",
});
const errorMessage = ref("");
const loading = ref(false);

async function handleRegister() {
  errorMessage.value = "";
  loading.value = true;

  try {
    await auth.register(form.value);
    useToastStore().success(`Welcome to ShopHub, ${auth.user?.name ?? "shopper"}!`);
    router.push("/");
  } catch (error) {
    errorMessage.value = firstValidationError(error, "Registration failed. Try again.");
  } finally {
    loading.value = false;
  }
}
</script>
