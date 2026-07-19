<template>
  <div class="max-w-2xl mx-auto my-12 px-4">
    <h1 class="font-display text-2xl font-bold mb-6">My Account</h1>

    <AccountNav />

    <div v-if="loading" class="text-center text-gray-500 py-12">Loading your orders...</div>

    <div v-else-if="error" class="text-center text-red-500 py-12">{{ error }}</div>

    <div v-else-if="orders.length === 0" class="text-center py-12">
      <p class="text-gray-500 mb-4">You haven't placed any orders yet.</p>
      <router-link
        to="/products"
        class="inline-block bg-orange-500 text-white px-6 py-2 rounded hover:bg-orange-600 transition"
      >
        Start Shopping
      </router-link>
    </div>

    <template v-else>
      <div class="space-y-4">
        <div
          v-for="order in orders"
          :key="order.id"
          class="bg-white border rounded-lg shadow-sm p-4"
        >
          <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
            <div>
              <p class="font-semibold text-sm">{{ order.order_number }}</p>
              <p class="text-xs text-gray-500">
                Placed {{ new Date(order.created_at).toLocaleString() }}
              </p>
            </div>
            <span
              class="px-3 py-1 rounded-full text-xs font-semibold capitalize"
              :class="statusClass(order.status)"
            >
              {{ order.status }}
            </span>
          </div>

          <ul class="text-sm divide-y">
            <li
              v-for="item in order.items"
              :key="item.id"
              class="py-1.5 flex justify-between"
            >
              <span>
                {{ item.product_name }}
                <span v-if="item.variant_label" class="text-gray-400">({{ item.variant_label }})</span>
                × {{ item.quantity }}
              </span>
              <span>₱{{ item.subtotal }}</span>
            </li>
          </ul>

          <p class="text-sm font-semibold text-right mt-2">Total: ₱{{ order.total }}</p>
        </div>
      </div>

      <div class="mt-6 bg-white border rounded-lg">
        <Pagination
          :current-page="meta.current_page"
          :last-page="meta.last_page"
          :total="meta.total"
          :from="meta.from"
          :to="meta.to"
          @change="loadOrders"
        />
      </div>
    </template>
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from "vue";
import { getMyOrders } from "../../services/account";
import type { Order } from "../../services/orders";
import AccountNav from "../../components/account/AccountNav.vue";
import Pagination from "../../components/common/Pagination.vue";

const orders = ref<Order[]>([]);
const meta = ref({
  current_page: 1,
  last_page: 1,
  total: 0,
  from: null as number | null,
  to: null as number | null,
});
const loading = ref(true);
const error = ref("");

function statusClass(status: Order["status"]): string {
  switch (status) {
    case "pending":
      return "bg-yellow-100 text-yellow-700";
    case "processing":
      return "bg-blue-100 text-blue-700";
    case "shipped":
      return "bg-indigo-100 text-indigo-700";
    case "delivered":
      return "bg-green-100 text-green-700";
    case "cancelled":
      return "bg-red-100 text-red-700";
  }
}

async function loadOrders(page = 1) {
  loading.value = true;
  error.value = "";

  try {
    const response = await getMyOrders(page);
    orders.value = response.data;
    meta.value = {
      current_page: response.current_page,
      last_page: response.last_page,
      total: response.total,
      from: response.from,
      to: response.to,
    };
  } catch {
    error.value = "Could not load your orders. Please try again.";
  } finally {
    loading.value = false;
  }
}

onMounted(() => loadOrders());
</script>
