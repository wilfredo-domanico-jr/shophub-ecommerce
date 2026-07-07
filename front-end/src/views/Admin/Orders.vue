<template>
  <div class="space-y-6">
    <!-- Header -->
    <div
      class="flex flex-col md:flex-row md:items-center md:justify-between gap-4"
    >
      <div>
        <h1 class="text-2xl font-bold">Orders</h1>
        <p class="text-gray-500 text-sm">Manage customer orders</p>
      </div>

      <!-- Search -->
      <input
        v-model="search"
        type="text"
        placeholder="Search order number or customer..."
        class="border px-4 py-2 rounded-lg w-full md:w-72 focus:outline-none focus:border-orange-500"
        @keyup.enter="loadOrders"
      />
    </div>

    <p v-if="error" class="text-red-500 text-sm">{{ error }}</p>

    <!-- Table -->
    <div class="bg-white rounded-xl shadow overflow-x-auto">
      <table class="w-full text-sm min-w-[700px]">
        <thead class="text-left text-gray-500 border-b">
          <tr>
            <th class="p-4">Order #</th>
            <th>Customer</th>
            <th>Items</th>
            <th>Total</th>
            <th>Status</th>
            <th class="text-right p-4">Actions</th>
          </tr>
        </thead>

        <tbody>
          <tr
            v-for="order in orders"
            :key="order.id"
            class="border-b hover:bg-gray-50"
          >
            <td class="p-4 font-medium">{{ order.order_number }}</td>
            <td>{{ order.customer_name }}</td>
            <td>{{ order.items_count ?? order.items?.length ?? 0 }} item(s)</td>
            <td class="font-semibold">₱{{ Number(order.total).toLocaleString() }}</td>

            <td>
              <span
                class="px-2 py-1 text-xs rounded-full font-medium capitalize"
                :class="statusClass(order.status)"
              >
                {{ order.status }}
              </span>
            </td>

            <td class="text-right p-4 space-x-2">
              <select
                :value="order.status"
                @change="onStatusChange(order, $event)"
                class="border rounded px-2 py-1 text-xs"
              >
                <option value="pending">Pending</option>
                <option value="processing">Processing</option>
                <option value="shipped">Shipped</option>
                <option value="delivered">Delivered</option>
                <option value="cancelled">Cancelled</option>
              </select>
            </td>
          </tr>

          <tr v-if="orders.length === 0">
            <td colspan="6" class="text-center py-10 text-gray-400">
              No orders found
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from "vue";
import type { Order } from "../../services/orders";
import { getAdminOrders, updateOrderStatus } from "../../services/admin/orders";

type AdminOrder = Order & { items_count?: number };

const search = ref("");
const orders = ref<AdminOrder[]>([]);
const error = ref("");

async function loadOrders() {
  error.value = "";
  try {
    const res = await getAdminOrders(search.value ? { search: search.value } : {});
    orders.value = res.data;
  } catch {
    error.value = "Failed to load orders.";
  }
}

onMounted(loadOrders);

function statusClass(status: string) {
  switch (status) {
    case "pending":
      return "bg-yellow-100 text-yellow-600";
    case "processing":
      return "bg-purple-100 text-purple-600";
    case "shipped":
      return "bg-blue-100 text-blue-600";
    case "delivered":
      return "bg-green-100 text-green-600";
    case "cancelled":
      return "bg-red-100 text-red-600";
    default:
      return "bg-gray-100 text-gray-600";
  }
}

async function onStatusChange(order: AdminOrder, event: Event) {
  const status = (event.target as HTMLSelectElement).value as Order["status"];
  const previous = order.status;
  order.status = status;
  try {
    await updateOrderStatus(order.id, status);
  } catch {
    order.status = previous;
    error.value = "Failed to update order status.";
  }
}
</script>
