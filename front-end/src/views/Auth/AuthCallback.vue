<template>
  <div class="max-w-md mx-auto my-16 px-4">
    <div class="bg-white p-8 rounded-lg shadow-md text-center">
      <h1 class="text-2xl font-bold mb-4 text-gradient-primary">Signing you in...</h1>
      <p class="text-sm text-gray-600">Hang tight, this only takes a moment.</p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { onMounted } from "vue";
import { useRoute, useRouter } from "vue-router";
import { useAuthStore } from "../../stores/auth";
import { useToastStore } from "../../stores/toast";

const auth = useAuthStore();
const toast = useToastStore();
const route = useRoute();
const router = useRouter();

const FALLBACK_ERROR = "Social sign-in failed. Please try again.";
const ERROR_MESSAGES: Record<string, string> = {
  no_email:
    "Your social account has no email address, so we can't create an account with it. Please register with email instead.",
  social_failed: FALLBACK_ERROR,
};

onMounted(async () => {
  const token = route.query.token as string | undefined;
  const error = route.query.error as string | undefined;

  // Scrub the token from the address bar before anything async happens,
  // so it never survives in browser history.
  window.history.replaceState(history.state, "", "/auth/callback");

  if (error || !token) {
    toast.error(ERROR_MESSAGES[error ?? ""] ?? FALLBACK_ERROR);
    router.replace({ name: "CustomerLogin" });
    return;
  }

  try {
    await auth.loginWithToken(token);
    toast.success(`Welcome, ${auth.user?.name ?? "shopper"}!`);

    const target = localStorage.getItem("postLoginRedirect") || "/";
    localStorage.removeItem("postLoginRedirect");
    router.replace(auth.isAdmin ? "/admin" : target);
  } catch {
    toast.error("Sign-in failed. Please try again.");
    router.replace({ name: "CustomerLogin" });
  }
});
</script>
