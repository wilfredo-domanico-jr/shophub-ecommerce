<template>
  <div class="max-w-md mx-auto my-16 px-4">
    <div class="bg-white p-8 rounded-lg shadow-md">
      <h1 class="text-2xl font-bold text-center mb-6 text-gradient-primary">
        Reset Password
      </h1>

      <div v-if="!token || !email" class="text-sm text-red-500 text-center">
        This reset link is invalid or incomplete. Please request a new one from the
        <router-link to="/forgot-password" class="underline">forgot password</router-link> page.
      </div>

      <template v-else>
        <div v-if="errorMessage" class="mb-4 text-red-500 text-sm text-center">
          {{ errorMessage }}
        </div>

        <form @submit.prevent="handleSubmit" class="space-y-4">
          <div>
            <label class="block mb-1 font-medium" for="password">New Password</label>
            <input
              v-model="password"
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
            <label class="block mb-1 font-medium" for="password_confirmation">Confirm New Password</label>
            <input
              v-model="passwordConfirmation"
              id="password_confirmation"
              type="password"
              required
              minlength="8"
              autocomplete="new-password"
              class="w-full px-4 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-orange-400"
              placeholder="Repeat your new password"
            />
          </div>

          <button
            type="submit"
            :disabled="loading"
            class="w-full bg-orange-500 text-white py-2 rounded hover:bg-orange-600 transition disabled:opacity-50"
          >
            {{ loading ? "Resetting..." : "Reset Password" }}
          </button>
        </form>
      </template>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, ref } from "vue";
import { useRoute, useRouter } from "vue-router";
import { resetPassword, firstValidationError } from "../../services/account";

const route = useRoute();
const router = useRouter();

const token = computed(() => (route.query.token as string) || "");
const email = computed(() => (route.query.email as string) || "");

const password = ref("");
const passwordConfirmation = ref("");
const errorMessage = ref("");
const loading = ref(false);

async function handleSubmit() {
  errorMessage.value = "";
  loading.value = true;

  try {
    await resetPassword({
      token: token.value,
      email: email.value,
      password: password.value,
      password_confirmation: passwordConfirmation.value,
    });

    router.push({ path: "/login", query: { reset: "1" } });
  } catch (error) {
    errorMessage.value = firstValidationError(
      error,
      "Reset failed. The link may have expired — request a new one."
    );
  } finally {
    loading.value = false;
  }
}
</script>
