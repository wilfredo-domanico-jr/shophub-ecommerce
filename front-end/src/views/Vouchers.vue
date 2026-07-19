<template>
  <div class="container mx-auto px-4 py-8">
    <!-- Breadcrumb -->
    <nav class="text-sm text-gray-500 mb-6 flex items-center gap-2">
      <router-link to="/" class="hover:text-orange-500">Home</router-link>
      <span>/</span>
      <span class="text-gray-700">Vouchers</span>
    </nav>

    <div class="max-w-3xl mx-auto">
      <h1 class="font-display text-2xl md:text-3xl font-bold text-gray-800 mb-2">
        Vouchers
      </h1>
      <p class="text-gray-500 mb-8">
        Copy a code and apply it at checkout to save on your order.
      </p>

      <div v-if="loading" class="space-y-4">
        <div v-for="n in 3" :key="n" class="skeleton rounded-xl h-24"></div>
      </div>

      <div
        v-else-if="vouchers.length === 0"
        class="bg-white rounded-xl shadow p-12 text-center text-gray-400"
      >
        No vouchers are available right now — check back soon!
      </div>

      <div v-else class="space-y-4">
        <div
          v-for="voucher in vouchers"
          :key="voucher.code"
          class="bg-white rounded-xl shadow flex flex-col sm:flex-row sm:items-center gap-4 p-5 border-l-4 border-orange-500"
        >
          <div class="flex-1">
            <div class="flex items-center gap-2 flex-wrap">
              <span class="font-mono font-bold text-lg text-orange-600">{{ voucher.code }}</span>
              <span class="bg-orange-100 text-orange-600 text-xs font-semibold px-2 py-0.5 rounded-full">
                {{ voucherSummary(voucher) }}
              </span>
            </div>
            <p v-if="voucher.description" class="text-sm text-gray-600 mt-1">
              {{ voucher.description }}
            </p>
            <p class="text-xs text-gray-400 mt-1">
              <span v-if="voucher.min_spend">Min spend ₱{{ Number(voucher.min_spend).toLocaleString() }}. </span>
              <span v-if="voucher.per_customer_limit">Once per customer. </span>
              <span v-if="voucher.expires_at">Valid until {{ formatDate(voucher.expires_at) }}.</span>
              <span v-else>No expiry.</span>
            </p>
          </div>

          <button
            class="shrink-0 border-2 border-orange-500 text-orange-500 px-4 py-2 rounded-lg font-semibold hover:bg-orange-50 transition"
            @click="copyCode(voucher.code)"
          >
            {{ copiedCode === voucher.code ? "Copied! ✓" : "Copy code" }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from "vue";
import { getPublicVouchers, voucherSummary, type PublicVoucher } from "../services/vouchers";
import { useToastStore } from "../stores/toast";

const toast = useToastStore();

const vouchers = ref<PublicVoucher[]>([]);
const loading = ref(true);
const copiedCode = ref("");

onMounted(async () => {
  try {
    vouchers.value = await getPublicVouchers();
  } catch {
    toast.error("Failed to load vouchers.");
  } finally {
    loading.value = false;
  }
});

function formatDate(iso: string) {
  return new Date(iso).toLocaleDateString(undefined, {
    month: "long",
    day: "numeric",
    year: "numeric",
  });
}

async function copyCode(code: string) {
  try {
    await navigator.clipboard.writeText(code);
    copiedCode.value = code;
    setTimeout(() => (copiedCode.value = ""), 2000);
  } catch {
    toast.info(`Voucher code: ${code}`);
  }
}
</script>
