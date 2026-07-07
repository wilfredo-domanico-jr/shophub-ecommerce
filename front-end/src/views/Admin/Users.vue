<template>
  <div class="space-y-6">
    <!-- Header -->
    <div
      class="flex flex-col md:flex-row md:items-center md:justify-between gap-4"
    >
      <div>
        <h1 class="text-2xl font-bold">Admins</h1>
        <p class="text-gray-500 text-sm">
          Manage who can access this admin panel
        </p>
      </div>

      <button
        @click="openAdd"
        class="bg-orange-500 text-white px-4 py-2 rounded-lg hover:opacity-90"
      >
        + Add Admin
      </button>
    </div>

    <!-- Search -->
    <input
      v-model="search"
      type="text"
      placeholder="Search name or email..."
      class="border px-4 py-2 rounded-lg w-full md:w-72 focus:outline-none focus:border-orange-500"
    />

    <p v-if="error" class="text-red-500 text-sm">{{ error }}</p>

    <!-- Table -->
    <div class="bg-white rounded-xl shadow overflow-x-auto">
      <table class="w-full text-sm min-w-[600px]">
        <thead class="text-left text-gray-500 border-b">
          <tr>
            <th class="p-4">Name</th>
            <th>Email</th>
            <th class="text-right p-4">Actions</th>
          </tr>
        </thead>

        <tbody>
          <tr
            v-for="user in filteredUsers"
            :key="user.id"
            class="border-b hover:bg-gray-50"
          >
            <td class="p-4 font-medium">{{ user.name }}</td>
            <td>{{ user.email }}</td>

            <td class="text-right p-4 space-x-2">
              <button
                class="text-blue-500 hover:underline"
                @click="openEdit(user)"
              >
                Edit
              </button>

              <button
                class="text-red-500 hover:underline"
                @click="remove(user.id)"
              >
                Remove
              </button>
            </td>
          </tr>

          <tr v-if="filteredUsers.length === 0">
            <td colspan="3" class="text-center py-10 text-gray-400">
              No admins found
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- MODAL -->
    <div
      v-if="showModal"
      class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4"
      @click.self="closeModal"
    >
      <div class="bg-white w-full max-w-md rounded-xl p-6 space-y-4">
        <h2 class="text-xl font-bold">
          {{ isEdit ? "Edit Admin" : "Add Admin" }}
        </h2>

        <p v-if="formError" class="text-red-500 text-sm">{{ formError }}</p>

        <input
          v-model="form.name"
          type="text"
          placeholder="Full name"
          class="w-full border p-2 rounded"
        />

        <input
          v-model="form.email"
          type="email"
          placeholder="Email"
          class="w-full border p-2 rounded"
        />

        <input
          v-model="form.password"
          type="password"
          :placeholder="isEdit ? 'New password (leave blank to keep)' : 'Password'"
          class="w-full border p-2 rounded"
        />

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
import { ref, computed, onMounted } from "vue";
import type { AdminUser } from "../../services/admin/users";
import {
  getAdminUsers,
  createAdminUser,
  updateAdminUser,
  deleteAdminUser,
} from "../../services/admin/users";

const search = ref("");
const users = ref<AdminUser[]>([]);
const error = ref("");
const formError = ref("");

async function loadUsers() {
  error.value = "";
  try {
    users.value = await getAdminUsers();
  } catch {
    error.value = "Failed to load admins.";
  }
}

onMounted(loadUsers);

const filteredUsers = computed(() => {
  return users.value.filter(
    (u) =>
      u.name.toLowerCase().includes(search.value.toLowerCase()) ||
      u.email.toLowerCase().includes(search.value.toLowerCase()),
  );
});

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
    } else {
      await createAdminUser({
        name: form.value.name,
        email: form.value.email,
        password: form.value.password,
      });
    }
    closeModal();
    await loadUsers();
  } catch (e: any) {
    formError.value = e?.response?.data?.message ?? "Failed to save admin.";
  }
}

async function remove(id: number) {
  if (!confirm("Remove this admin?")) return;
  try {
    await deleteAdminUser(id);
    await loadUsers();
  } catch (e: any) {
    error.value = e?.response?.data?.message ?? "Failed to remove admin.";
  }
}
</script>
