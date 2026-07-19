<template>
  <div v-if="providers.length" class="space-y-3 mb-6">
    <button
      v-if="providers.includes('google')"
      type="button"
      class="w-full py-2 rounded border border-gray-300 bg-white hover:bg-gray-50 transition flex items-center justify-center gap-2 font-medium"
      @click="loginWith('google')"
    >
      <svg class="w-5 h-5" viewBox="0 0 24 24" aria-hidden="true">
        <path
          fill="#4285F4"
          d="M23.49 12.27c0-.85-.08-1.66-.22-2.45H12v4.64h6.44a5.5 5.5 0 0 1-2.39 3.61v3h3.87c2.26-2.09 3.57-5.17 3.57-8.8z"
        />
        <path
          fill="#34A853"
          d="M12 24c3.24 0 5.95-1.08 7.93-2.91l-3.87-3c-1.07.72-2.45 1.15-4.06 1.15-3.12 0-5.77-2.11-6.71-4.95H1.29v3.1A11.99 11.99 0 0 0 12 24z"
        />
        <path
          fill="#FBBC05"
          d="M5.29 14.29A7.2 7.2 0 0 1 4.91 12c0-.79.14-1.57.38-2.29v-3.1H1.29A11.99 11.99 0 0 0 0 12c0 1.94.46 3.77 1.29 5.39l4-3.1z"
        />
        <path
          fill="#EA4335"
          d="M12 4.77c1.76 0 3.34.6 4.58 1.79l3.44-3.44C17.94 1.19 15.24 0 12 0 7.31 0 3.26 2.69 1.29 6.61l4 3.1C6.23 6.87 8.88 4.77 12 4.77z"
        />
      </svg>
      Continue with Google
    </button>

    <button
      v-if="providers.includes('facebook')"
      type="button"
      class="w-full py-2 rounded border border-gray-300 bg-white hover:bg-gray-50 transition flex items-center justify-center gap-2 font-medium"
      @click="loginWith('facebook')"
    >
      <svg class="w-5 h-5" viewBox="0 0 24 24" aria-hidden="true">
        <path
          fill="#1877F2"
          d="M24 12c0-6.63-5.37-12-12-12S0 5.37 0 12c0 5.99 4.39 10.95 10.13 11.85v-8.38H7.08V12h3.05V9.36c0-3.01 1.79-4.67 4.53-4.67 1.31 0 2.69.23 2.69.23v2.95H15.83c-1.49 0-1.96.93-1.96 1.88V12h3.33l-.53 3.47h-2.8v8.38C19.61 22.95 24 17.99 24 12z"
        />
      </svg>
      Continue with Facebook
    </button>
  </div>
</template>

<script setup lang="ts">
import { useRoute } from "vue-router";

defineProps<{ providers: string[] }>();

const route = useRoute();

function loginWith(provider: string) {
  // The OAuth round-trip leaves the SPA entirely, so stash the post-login
  // destination where AuthCallback can find it afterwards.
  const redirect = (route.query.redirect as string) || "";
  if (redirect) {
    localStorage.setItem("postLoginRedirect", redirect);
  } else {
    localStorage.removeItem("postLoginRedirect");
  }
  window.location.href = `${import.meta.env.VITE_API_BASE_URL}/auth/${provider}/redirect`;
}
</script>
