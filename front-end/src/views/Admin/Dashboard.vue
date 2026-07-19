<template>
  <div class="space-y-6">
    <!-- Header -->
    <div>
      <h1 class="text-2xl font-bold">Dashboard</h1>
      <p class="text-gray-500 text-sm">
        Welcome back! Here’s your store performance.
      </p>
    </div>

    <p v-if="loadError" class="text-red-500 text-sm bg-red-50 border border-red-200 rounded-lg p-3">
      Failed to load dashboard stats — refresh to try again.
    </p>

    <!-- Stats -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
      <div
        v-for="card in statCards"
        :key="card.label"
        class="bg-white p-5 rounded-xl shadow flex items-center gap-4"
      >
        <div
          class="w-12 h-12 rounded-xl flex items-center justify-center text-white shrink-0"
          :class="card.iconBg"
        >
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="card.icon" />
          </svg>
        </div>
        <div>
          <p class="text-gray-500 text-sm">{{ card.label }}</p>
          <h2 class="text-2xl font-bold" :class="card.textColor">{{ card.value }}</h2>
        </div>
      </div>
    </div>

    <!-- SALES CHART -->
    <div class="bg-white p-5 rounded-xl shadow">
      <h2 class="font-display text-lg font-semibold mb-4 text-gray-800">
        Sales Overview (last 7 days)
      </h2>

      <Line :data="chartData" :options="chartOptions" />
    </div>

    <!-- Recent Orders -->
    <div class="bg-white rounded-xl shadow p-5">
      <h2 class="font-display text-lg font-semibold mb-4 text-gray-800">Recent Orders</h2>

      <div class="overflow-x-auto">
      <table class="w-full text-sm min-w-[500px]">
        <thead class="text-left text-gray-500 border-b">
          <tr>
            <th class="py-2">Order #</th>
            <th>Customer</th>
            <th>Status</th>
            <th>Total</th>
          </tr>
        </thead>

        <tbody>
          <tr v-for="order in stats.recent_orders" :key="order.id" class="border-b">
            <td class="py-2">{{ order.order_number }}</td>
            <td>{{ order.customer_name }}</td>
            <td>
              <span
                class="px-2 py-1 text-xs rounded-full font-medium capitalize"
                :class="statusClass(order.status)"
              >
                {{ order.status }}
              </span>
            </td>
            <td class="font-medium">₱{{ Number(order.total).toLocaleString() }}</td>
          </tr>

          <tr v-if="stats.recent_orders.length === 0">
            <td colspan="4" class="text-center py-6 text-gray-400">
              No orders yet
            </td>
          </tr>
        </tbody>
      </table>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from "vue";
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  Title,
  Tooltip,
  Legend,
} from "chart.js";

import { Line } from "vue-chartjs";
import { getDashboardStats, type DashboardStats } from "../../services/admin/dashboard";

ChartJS.register(
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  Title,
  Tooltip,
  Legend,
);

const stats = ref<DashboardStats>({
  total_sales: 0,
  orders_count: 0,
  products_count: 0,
  customers_count: 0,
  sales_series: [],
  recent_orders: [],
});

const loadError = ref(false);

onMounted(async () => {
  try {
    stats.value = await getDashboardStats();
  } catch {
    loadError.value = true; // otherwise the dashboard shows all zeros silently
  }
});

const statCards = computed(() => [
  {
    label: "Total Sales",
    value: `₱${stats.value.total_sales.toLocaleString()}`,
    icon: "M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z",
    iconBg: "gradient-primary",
    textColor: "text-orange-500",
  },
  {
    label: "Orders",
    value: stats.value.orders_count,
    icon: "M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2",
    iconBg: "gradient-secondary",
    textColor: "text-gray-800",
  },
  {
    label: "Products",
    value: stats.value.products_count,
    icon: "M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4",
    iconBg: "gradient-accent",
    textColor: "text-gray-800",
  },
  {
    label: "Customers",
    value: stats.value.customers_count,
    icon: "M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-1.13a4 4 0 10-4-4 4 4 0 004 4zm6 0a4 4 0 10-4-4",
    iconBg: "gradient-success",
    textColor: "text-gray-800",
  },
]);

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

const chartData = computed(() => ({
  labels: stats.value.sales_series.map((s) => s.label),
  datasets: [
    {
      label: "Sales (₱)",
      data: stats.value.sales_series.map((s) => s.total),
      borderColor: "#f97316",
      backgroundColor: "rgba(249,115,22,0.2)",
      tension: 0.4,
      fill: true,
    },
  ],
}));

const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
};
</script>

<style scoped>
/* control chart height */
div :deep(canvas) {
  height: 300px !important;
}
</style>
