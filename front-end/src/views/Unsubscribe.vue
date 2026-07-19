<template>
  <div class="max-w-md mx-auto my-16 px-4">
    <div class="bg-white p-8 rounded-lg shadow-md text-center">
      <template v-if="state === 'working'">
        <h1 class="text-2xl font-bold mb-4 text-gradient-primary">One moment...</h1>
        <p class="text-sm text-gray-600">Processing your unsubscribe request.</p>
      </template>

      <template v-else-if="state === 'done'">
        <div class="text-5xl mb-4">👋</div>
        <h1 class="text-2xl font-bold mb-4 text-gradient-primary">You're unsubscribed</h1>
        <p class="text-sm text-gray-600 mb-6">{{ message }}</p>
        <p class="text-xs text-gray-400 mb-6">
          Changed your mind? You can subscribe again anytime from the footer.
        </p>
        <router-link
          to="/"
          class="inline-block bg-orange-500 text-white px-6 py-2 rounded hover:bg-orange-600 transition"
        >
          Back to ShopHub
        </router-link>
      </template>

      <template v-else>
        <div class="text-5xl mb-4">🤔</div>
        <h1 class="text-2xl font-bold mb-4 text-gradient-primary">Link not valid</h1>
        <p class="text-sm text-gray-600 mb-6">{{ message }}</p>
        <router-link
          to="/"
          class="inline-block bg-orange-500 text-white px-6 py-2 rounded hover:bg-orange-600 transition"
        >
          Back to ShopHub
        </router-link>
      </template>
    </div>
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from "vue";
import { useRoute } from "vue-router";
import { unsubscribeFromNewsletter } from "../services/newsletter";

const route = useRoute();

const state = ref<"working" | "done" | "invalid">("working");
const message = ref("");

onMounted(async () => {
  const token = route.query.token as string | undefined;

  if (!token) {
    state.value = "invalid";
    message.value = "This unsubscribe link is missing its token. Please use the link from your email.";
    return;
  }

  try {
    const { message: apiMessage } = await unsubscribeFromNewsletter(token);
    message.value = apiMessage;
    state.value = "done";
  } catch (e: any) {
    message.value =
      e?.response?.data?.message ??
      "This unsubscribe link is invalid or no longer active.";
    state.value = "invalid";
  }
});
</script>
