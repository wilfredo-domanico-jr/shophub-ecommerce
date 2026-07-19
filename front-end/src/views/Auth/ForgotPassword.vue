<template>
  <div class="max-w-md mx-auto my-16 px-4">
    <div class="bg-white p-8 rounded-lg shadow-md">
      <h1 class="text-2xl font-bold text-center mb-2 text-gradient-primary">
        Forgot Password
      </h1>
      <p class="text-sm text-gray-600 text-center mb-6">
        Enter your email and we'll send you a link to reset your password.
      </p>

      <div v-if="successMessage" class="mb-4 p-4 rounded-lg bg-green-50 border border-green-200 text-green-700 text-sm text-center">
        {{ successMessage }}
      </div>

      <div v-if="errorMessage" class="mb-4 text-red-500 text-sm text-center">
        {{ errorMessage }}
      </div>

      <form v-if="!successMessage" @submit.prevent="handleSubmit" class="space-y-4">
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

        <button
          type="submit"
          :disabled="loading"
          class="w-full bg-orange-500 text-white py-2 rounded hover:bg-orange-600 transition disabled:opacity-50"
        >
          {{ loading ? "Sending..." : "Send Reset Link" }}
        </button>
      </form>

      <p class="mt-6 text-sm text-center text-gray-600">
        Remembered it?
        <router-link to="/login" class="text-orange-500 font-medium hover:underline">
          Back to sign in
        </router-link>
      </p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from "vue";
import { forgotPassword } from "../../services/account";
import { getAppConfig } from "../../services/config";

const email = ref("");
const successMessage = ref("");
const errorMessage = ref("");
const loading = ref(false);

// The backend silently ignores reset requests for demo accounts — tell the
// visitor up front instead of pretending an email was sent.
const demoEmails = ref<string[]>([]);

onMounted(async () => {
  try {
    const config = await getAppConfig();
    demoEmails.value = config.demo_mode
      ? [config.demo_admin_email, config.demo_customer_email]
          .filter((e): e is string => !!e)
          .map((e) => e.toLowerCase())
      : [];
  } catch {
    demoEmails.value = [];
  }
});

async function handleSubmit() {
  errorMessage.value = "";

  if (demoEmails.value.includes(email.value.trim().toLowerCase())) {
    errorMessage.value = "Password resets are disabled for the shared demo account.";
    return;
  }

  loading.value = true;

  try {
    const { message } = await forgotPassword(email.value);
    successMessage.value = message;
  } catch {
    errorMessage.value = "Something went wrong. Please try again.";
  } finally {
    loading.value = false;
  }
}
</script>
