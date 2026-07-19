<template>
  <div class="flex min-h-screen bg-gray-50">
    <!-- BACKDROP (mobile only) -->
    <div
      v-if="isMobileOpen"
      class="fixed inset-0 bg-black/40 z-40 md:hidden"
      @click="closeSidebar"
    ></div>

    <!-- SIDEBAR -->
    <aside
      class="fixed md:static z-50 md:z-auto w-64 bg-white border-r border-gray-100 flex flex-col h-full md:h-auto transform transition-transform duration-300"
      :class="
        isMobileOpen ? 'translate-x-0' : '-translate-x-full md:translate-x-0'
      "
    >
      <!-- Logo + Close (mobile) -->
      <div class="p-5 flex items-center justify-between border-b">
        <div class="flex items-center gap-2">
          <div
            class="gradient-primary w-9 h-9 rounded-lg flex items-center justify-center text-white font-bold"
          >
            S
          </div>
          <span class="font-display text-lg font-bold text-gray-800">ShopHub</span>
        </div>

        <button class="md:hidden text-gray-500" @click="closeSidebar">✕</button>
      </div>

      <!-- Navigation -->
      <nav class="flex-1 p-4 space-y-1">
        <router-link
          v-for="item in navItems"
          :key="item.to"
          :to="item.to"
          class="flex items-center gap-3 px-4 py-2.5 rounded-lg text-gray-600 hover:bg-orange-50 hover:text-orange-600 transition"
          :class="{
            'gradient-primary text-white shadow-md hover:text-white': isActive(item.to),
          }"
          @click="closeSidebar"
        >
          <svg
            class="w-5 h-5 shrink-0"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              :d="item.icon"
            />
          </svg>
          <span class="font-medium text-sm">{{ item.label }}</span>
        </router-link>

        <div class="pt-3 mt-3 border-t">
          <router-link
            to="/"
            class="flex items-center gap-3 px-4 py-2.5 rounded-lg text-gray-400 hover:bg-gray-100 hover:text-gray-600 transition"
            @click="closeSidebar"
          >
            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M10 19l-7-7m0 0l7-7m-7 7h18"
              />
            </svg>
            <span class="font-medium text-sm">Back to Store</span>
          </router-link>
        </div>
      </nav>

      <!-- User + Logout -->
      <div class="p-4 border-t space-y-3">
        <div class="flex items-center gap-3 px-2">
          <div
            class="w-9 h-9 rounded-full gradient-secondary flex items-center justify-center text-white text-sm font-bold shrink-0"
          >
            {{ initials }}
          </div>
          <div class="min-w-0">
            <p class="text-sm font-medium text-gray-800 truncate">
              {{ auth.user?.name ?? "Admin" }}
            </p>
            <p class="text-xs text-gray-400 truncate">{{ auth.user?.email }}</p>
          </div>
        </div>

        <button
          class="w-full flex items-center justify-center gap-2 px-4 py-2 text-red-500 hover:bg-red-50 rounded-lg transition font-medium text-sm"
          @click="logout"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"
            />
          </svg>
          Logout
        </button>
      </div>
    </aside>

    <!-- MAIN CONTENT -->
    <div class="flex-1 flex flex-col w-full">
      <!-- TOP BAR -->
      <header
        class="bg-white border-b border-gray-100 px-6 py-4 flex items-center justify-between sticky top-0 z-30"
      >
        <div class="flex items-center gap-3">
          <button class="md:hidden text-gray-700" @click="openSidebar">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M4 6h16M4 12h16M4 18h16"
              />
            </svg>
          </button>

          <h1 class="font-display font-semibold text-lg text-gray-800">
            {{ currentTitle }}
          </h1>
        </div>

        <div class="hidden md:flex items-center gap-2 text-sm text-gray-500">
          <span>Welcome back,</span>
          <span class="font-medium text-gray-700">{{ auth.user?.name ?? "Admin" }}</span>
          <span>👋</span>
        </div>
      </header>

      <!-- PAGE CONTENT -->
      <main class="p-6 flex-1">
        <router-view />
      </main>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, ref } from "vue";
import { useRoute, useRouter } from "vue-router";
import { useAuthStore } from "../stores/auth";
import { useToastStore } from "../stores/toast";

const route = useRoute();
const router = useRouter();
const auth = useAuthStore();

const isMobileOpen = ref(false);

const navItems = [
  {
    to: "/admin",
    label: "Dashboard",
    icon: "M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6",
  },
  {
    to: "/admin/products",
    label: "Products",
    icon: "M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4",
  },
  {
    to: "/admin/orders",
    label: "Orders",
    icon: "M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4",
  },
  {
    to: "/admin/categories",
    label: "Categories",
    icon: "M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10",
  },
  {
    to: "/admin/users",
    label: "Users",
    icon: "M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-1.13a4 4 0 10-4-4 4 4 0 004 4zm6 0a4 4 0 10-4-4",
  },
  {
    to: "/admin/careers",
    label: "Careers",
    icon: "M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z",
  },
];

const currentTitle = computed(() => {
  return navItems.find((item) => item.to === route.path)?.label ?? "Admin Panel";
});

const initials = computed(() => {
  const name = auth.user?.name ?? "Admin";
  return name
    .split(" ")
    .map((part) => part[0])
    .join("")
    .slice(0, 2)
    .toUpperCase();
});

function openSidebar() {
  isMobileOpen.value = true;
}

function closeSidebar() {
  isMobileOpen.value = false;
}

function isActive(path: string) {
  return route.path === path;
}

async function logout() {
  await auth.logout();
  useToastStore().info("You have been signed out.");
  router.push("/admin/login");
}
</script>
