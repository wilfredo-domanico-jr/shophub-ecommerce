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
      <div class="relative w-full md:w-72">
        <svg class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
        </svg>
        <input
          v-model="search"
          type="text"
          placeholder="Search order number or customer..."
          class="w-full pl-9 pr-4 py-2 border rounded-lg focus:outline-none focus:border-orange-500"
        />
      </div>
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

            <td class="text-right p-4">
              <div class="flex items-center justify-end gap-2">
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

                <button
                  title="View details"
                  class="w-8 h-8 flex items-center justify-center rounded-lg text-blue-500 bg-blue-50 hover:bg-blue-500 hover:text-white transition shrink-0"
                  @click="openView(order)"
                >
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                  </svg>
                </button>
              </div>
            </td>
          </tr>

          <tr v-if="orders.length === 0">
            <td colspan="6" class="text-center py-10 text-gray-400">
              No orders found
            </td>
          </tr>
        </tbody>
      </table>

      <Pagination
        :current-page="meta.current_page"
        :last-page="meta.last_page"
        :total="meta.total"
        :from="meta.from"
        :to="meta.to"
        @change="goToPage"
      />
    </div>

    <!-- VIEW ORDER MODAL -->
    <div
      v-if="showViewModal"
      class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4"
    >
      <div class="bg-white w-full max-w-2xl rounded-xl p-6 space-y-5 max-h-[90vh] overflow-y-auto">
        <div v-if="viewLoading" class="py-12 text-center text-gray-400">
          Loading order...
        </div>

        <template v-else-if="viewingOrder">
          <!-- Header -->
          <div class="flex items-start justify-between">
            <div>
              <h2 class="text-xl font-bold">{{ viewingOrder.order_number }}</h2>
              <p class="text-xs text-gray-400 mt-1">
                Placed {{ new Date(viewingOrder.created_at).toLocaleString() }}
              </p>
            </div>
            <span
              class="px-2 py-1 text-xs rounded-full font-medium capitalize"
              :class="statusClass(viewingOrder.status)"
            >
              {{ viewingOrder.status }}
            </span>
          </div>

          <!-- Customer + Shipping -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="bg-gray-50 rounded-lg p-4">
              <h3 class="text-xs font-semibold text-gray-500 uppercase mb-2">Customer</h3>
              <p class="text-sm font-medium">{{ viewingOrder.customer_name }}</p>
              <p class="text-sm text-gray-600">{{ viewingOrder.customer_email }}</p>
              <p class="text-sm text-gray-600">{{ viewingOrder.customer_phone }}</p>
            </div>

            <div class="bg-gray-50 rounded-lg p-4">
              <h3 class="text-xs font-semibold text-gray-500 uppercase mb-2">Shipping Address</h3>
              <p class="text-sm text-gray-600 whitespace-pre-line">{{ viewingOrder.shipping_address }}</p>
            </div>
          </div>

          <!-- Items -->
          <div>
            <h3 class="text-xs font-semibold text-gray-500 uppercase mb-2">Items</h3>
            <div class="border rounded-lg overflow-hidden">
              <table class="w-full text-sm">
                <thead class="bg-gray-50 text-left text-gray-500">
                  <tr>
                    <th class="p-3">Product</th>
                    <th class="p-3">Price</th>
                    <th class="p-3">Qty</th>
                    <th class="p-3 text-right">Subtotal</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="item in viewingOrder.items" :key="item.id" class="border-t">
                    <td class="p-3">{{ item.product_name }}</td>
                    <td class="p-3">₱{{ Number(item.product_price).toLocaleString() }}</td>
                    <td class="p-3">{{ item.quantity }}</td>
                    <td class="p-3 text-right">₱{{ Number(item.subtotal).toLocaleString() }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Summary -->
          <div class="bg-gray-50 rounded-lg p-4 space-y-1 text-sm">
            <div class="flex justify-between text-gray-600">
              <span>Subtotal</span>
              <span>₱{{ Number(viewingOrder.subtotal).toLocaleString() }}</span>
            </div>
            <div class="flex justify-between text-gray-600">
              <span>Shipping Fee</span>
              <span>₱{{ Number(viewingOrder.shipping_fee).toLocaleString() }}</span>
            </div>
            <div class="flex justify-between font-semibold text-base pt-1 border-t">
              <span>Total</span>
              <span>₱{{ Number(viewingOrder.total).toLocaleString() }}</span>
            </div>
          </div>

          <!-- Payment -->
          <div class="grid grid-cols-2 gap-4 text-sm">
            <div>
              <h3 class="text-xs font-semibold text-gray-500 uppercase mb-1">Payment Method</h3>
              <p>{{ viewingOrder.payment_method }}</p>
            </div>
            <div>
              <h3 class="text-xs font-semibold text-gray-500 uppercase mb-1">Payment Status</h3>
              <span
                class="px-2 py-1 text-xs rounded-full font-medium capitalize"
                :class="viewingOrder.payment_status === 'paid' ? 'bg-green-100 text-green-600' : 'bg-yellow-100 text-yellow-600'"
              >
                {{ viewingOrder.payment_status }}
              </span>
            </div>
          </div>

          <!-- Notes -->
          <div v-if="viewingOrder.notes">
            <h3 class="text-xs font-semibold text-gray-500 uppercase mb-1">Notes</h3>
            <p class="text-sm text-gray-600 whitespace-pre-line">{{ viewingOrder.notes }}</p>
          </div>

          <!-- Buttons -->
          <div class="flex justify-end pt-2">
            <button class="px-4 py-2 text-gray-500" @click="closeView">Close</button>
          </div>
        </template>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, watch } from "vue";
import type { Order } from "../../services/orders";
import { getAdminOrders, getAdminOrder, updateOrderStatus } from "../../services/admin/orders";
import Pagination from "../../components/common/Pagination.vue";

type AdminOrder = Order & { items_count?: number };

const search = ref("");
const page = ref(1);
const orders = ref<AdminOrder[]>([]);
const error = ref("");

const showViewModal = ref(false);
const viewLoading = ref(false);
const viewingOrder = ref<Order | null>(null);

async function openView(order: AdminOrder) {
  showViewModal.value = true;
  viewLoading.value = true;
  viewingOrder.value = null;
  try {
    viewingOrder.value = await getAdminOrder(order.id);
  } finally {
    viewLoading.value = false;
  }
}

function closeView() {
  showViewModal.value = false;
  viewingOrder.value = null;
}

const meta = ref({ current_page: 1, last_page: 1, total: 0, from: 0 as number | null, to: 0 as number | null });

async function loadOrders() {
  error.value = "";
  try {
    const res = await getAdminOrders({ search: search.value || undefined, page: page.value });
    orders.value = res.data;
    meta.value = {
      current_page: res.current_page,
      last_page: res.last_page,
      total: res.total,
      from: res.from,
      to: res.to,
    };
  } catch {
    error.value = "Failed to load orders.";
  }
}

function goToPage(p: number) {
  page.value = p;
  loadOrders();
}

let searchTimeout: ReturnType<typeof setTimeout>;
watch(search, () => {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    page.value = 1;
    loadOrders();
  }, 300);
});

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
