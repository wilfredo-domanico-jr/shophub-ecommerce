<template>
  <div class="space-y-6">
    <!-- Header -->
    <div
      class="flex flex-col md:flex-row md:items-center md:justify-between gap-4"
    >
      <div>
        <h1 class="text-2xl font-bold">Users</h1>
        <p class="text-gray-500 text-sm">
          Manage admin and customer accounts
        </p>
      </div>

      <button
        @click="openAdd"
        class="bg-orange-500 text-white px-4 py-2 rounded-lg hover:opacity-90"
      >
        + Add Admin
      </button>
    </div>

    <!-- Search + Role filter -->
    <div class="bg-white p-4 rounded-xl shadow flex flex-col sm:flex-row gap-3">
      <div class="relative max-w-sm flex-1">
        <svg class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
        </svg>
        <input
          v-model="search"
          type="text"
          placeholder="Search name or email..."
          class="w-full pl-9 pr-4 py-2 border rounded-lg focus:outline-none focus:border-orange-500"
        />
      </div>

      <select
        v-model="roleFilter"
        class="border rounded-lg px-3 py-2 focus:outline-none focus:border-orange-500"
      >
        <option value="">All roles</option>
        <option value="admin">Admins</option>
        <option value="customer">Customers</option>
      </select>
    </div>

    <p v-if="error" class="text-red-500 text-sm">{{ error }}</p>

    <!-- Table -->
    <div class="bg-white rounded-xl shadow overflow-x-auto">
      <table class="w-full text-sm min-w-[600px]">
        <thead class="text-left text-gray-500 border-b">
          <tr>
            <th class="p-4">Name</th>
            <th>Email</th>
            <th>Role</th>
            <th class="text-right p-4">Actions</th>
          </tr>
        </thead>

        <tbody>
          <tr
            v-for="user in users"
            :key="user.id"
            class="border-b hover:bg-gray-50"
          >
            <td class="p-4 font-medium">{{ user.name }}</td>
            <td>{{ user.email }}</td>
            <td>
              <span
                class="px-2.5 py-1 rounded-full text-xs font-semibold"
                :class="user.is_admin ? 'bg-orange-100 text-orange-700' : 'bg-gray-100 text-gray-600'"
              >
                {{ user.is_admin ? "Admin" : "Customer" }}
              </span>
            </td>

            <td class="text-right p-4">
              <div class="flex justify-end gap-2">
                <button
                  title="Edit"
                  class="w-8 h-8 flex items-center justify-center rounded-lg text-blue-500 bg-blue-50 hover:bg-blue-500 hover:text-white transition"
                  @click="openEdit(user)"
                >
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                  </svg>
                </button>

                <button
                  title="Remove"
                  class="w-8 h-8 flex items-center justify-center rounded-lg text-red-500 bg-red-50 hover:bg-red-500 hover:text-white transition"
                  @click="remove(user.id)"
                >
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                  </svg>
                </button>
              </div>
            </td>
          </tr>

          <tr v-if="users.length === 0">
            <td colspan="4" class="text-center py-10 text-gray-400">
              No users found
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

    <!-- MODAL -->
    <div
      v-if="showModal"
      class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4"
    >
      <div class="bg-white w-full max-w-md rounded-xl p-6 space-y-4">
        <h2 class="text-xl font-bold">
          {{ isEdit ? "Edit Admin" : "Add Admin" }}
        </h2>

        <p v-if="formError" class="text-red-500 text-sm">{{ formError }}</p>

        <div>
          <label class="block mb-1 text-sm font-medium text-gray-700">Full Name</label>
          <input
            v-model="form.name"
            type="text"
            placeholder="e.g. Juan Dela Cruz"
            class="w-full border p-2 rounded focus:outline-none focus:border-orange-500"
          />
        </div>

        <div>
          <label class="block mb-1 text-sm font-medium text-gray-700">Email</label>
          <input
            v-model="form.email"
            type="email"
            placeholder="you@example.com"
            class="w-full border p-2 rounded focus:outline-none focus:border-orange-500"
          />
        </div>

        <div>
          <label class="block mb-1 text-sm font-medium text-gray-700">Password</label>
          <input
            v-model="form.password"
            type="password"
            :placeholder="isEdit ? 'New password (leave blank to keep)' : 'Password'"
            class="w-full border p-2 rounded focus:outline-none focus:border-orange-500"
          />
        </div>

        <div class="flex justify-end gap-2 pt-2">
          <button class="px-4 py-2 text-gray-500" @click="closeModal">
            Cancel
          </button>

          <button
            class="bg-orange-500 text-white px-4 py-2 rounded"
            @click="save"
          >
            {{ isEdit ? "Update" : "Add" }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, watch } from "vue";
import type { AdminUser } from "../../services/admin/users";
import {
  getAdminUsers,
  createAdminUser,
  updateAdminUser,
  deleteAdminUser,
} from "../../services/admin/users";
import Pagination from "../../components/common/Pagination.vue";
import { useToastStore } from "../../stores/toast";

const toast = useToastStore();

const search = ref("");
const roleFilter = ref<"" | "admin" | "customer">("");
const page = ref(1);
const users = ref<AdminUser[]>([]);
const error = ref("");
const formError = ref("");

const meta = ref({ current_page: 1, last_page: 1, total: 0, from: 0 as number | null, to: 0 as number | null });

async function loadUsers() {
  error.value = "";
  try {
    const res = await getAdminUsers({
      search: search.value || undefined,
      role: roleFilter.value || undefined,
      page: page.value,
    });
    users.value = res.data;
    meta.value = {
      current_page: res.current_page,
      last_page: res.last_page,
      total: res.total,
      from: res.from,
      to: res.to,
    };
  } catch {
    error.value = "Failed to load users.";
  }
}

function goToPage(p: number) {
  page.value = p;
  loadUsers();
}

let searchTimeout: ReturnType<typeof setTimeout>;
watch(search, () => {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    page.value = 1;
    loadUsers();
  }, 300);
});

watch(roleFilter, () => {
  page.value = 1;
  loadUsers();
});

onMounted(loadUsers);

const showModal = ref(false);
const isEdit = ref(false);

type UserForm = { id: number; name: string; email: string; password: string };

const form = ref<UserForm>({ id: 0, name: "", email: "", password: "" });

function openAdd() {
  isEdit.value = false;
  formError.value = "";
  form.value = { id: 0, name: "", email: "", password: "" };
  showModal.value = true;
}

function openEdit(user: AdminUser) {
  isEdit.value = true;
  formError.value = "";
  form.value = { id: user.id, name: user.name, email: user.email, password: "" };
  showModal.value = true;
}

function closeModal() {
  showModal.value = false;
}

async function save() {
  if (!form.value.name || !form.value.email) {
    formError.value = "Name and email are required.";
    return;
  }

  if (!isEdit.value && !form.value.password) {
    formError.value = "Password is required for new admins.";
    return;
  }

  try {
    if (isEdit.value) {
      await updateAdminUser(form.value.id, {
        name: form.value.name,
        email: form.value.email,
        password: form.value.password || undefined,
      });
      toast.success("Admin updated.");
    } else {
      await createAdminUser({
        name: form.value.name,
        email: form.value.email,
        password: form.value.password,
      });
      toast.success("Admin created.");
    }
    closeModal();
    await loadUsers();
  } catch (e: any) {
    formError.value = e?.response?.data?.message ?? "Failed to save admin.";
  }
}

async function remove(id: number) {
  if (!confirm("Remove this user?")) return;
  try {
    await deleteAdminUser(id);
    await loadUsers();
    toast.success("User removed.");
  } catch (e: any) {
    toast.error(e?.response?.data?.message ?? "Failed to remove user.");
  }
}
</script>
