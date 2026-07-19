<template>
  <div class="space-y-6">
    <!-- Header -->
    <div
      class="flex flex-col md:flex-row md:items-center md:justify-between gap-4"
    >
      <div>
        <h1 class="text-2xl font-bold">Careers</h1>
        <p class="text-gray-500 text-sm">
          Manage the job openings shown on the public Careers page
        </p>
      </div>

      <button
        @click="openAdd"
        class="bg-orange-500 text-white px-4 py-2 rounded-lg hover:opacity-90"
      >
        + Add Opening
      </button>
    </div>

    <p v-if="error" class="text-red-500 text-sm">{{ error }}</p>

    <!-- Table -->
    <div class="bg-white rounded-xl shadow overflow-x-auto">
      <table class="w-full text-sm min-w-[700px]">
        <thead class="text-left text-gray-500 border-b">
          <tr>
            <th class="p-4">Role</th>
            <th>Location</th>
            <th>Type</th>
            <th>Status</th>
            <th class="text-right p-4">Actions</th>
          </tr>
        </thead>

        <tbody>
          <tr
            v-for="opening in openings"
            :key="opening.id"
            class="border-b hover:bg-gray-50"
          >
            <td class="p-4">
              <p class="font-medium">{{ opening.title }}</p>
              <p class="text-xs text-gray-500">{{ opening.department }}</p>
            </td>
            <td>{{ opening.location }}</td>
            <td>{{ opening.employment_type }}</td>
            <td>
              <span
                class="px-2 py-1 text-xs rounded-full"
                :class="
                  opening.is_active
                    ? 'bg-green-100 text-green-600'
                    : 'bg-gray-100 text-gray-500'
                "
              >
                {{ opening.is_active ? "Published" : "Hidden" }}
              </span>
            </td>

            <td class="text-right p-4">
              <div class="flex justify-end gap-2">
                <button
                  title="Edit"
                  class="w-8 h-8 flex items-center justify-center rounded-lg text-blue-500 bg-blue-50 hover:bg-blue-500 hover:text-white transition"
                  @click="openEdit(opening)"
                >
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                  </svg>
                </button>

                <button
                  title="Delete"
                  class="w-8 h-8 flex items-center justify-center rounded-lg text-red-500 bg-red-50 hover:bg-red-500 hover:text-white transition"
                  @click="remove(opening.id)"
                >
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                  </svg>
                </button>
              </div>
            </td>
          </tr>

          <tr v-if="openings.length === 0">
            <td colspan="5" class="text-center py-10 text-gray-400">
              No job openings yet
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- MODAL -->
    <div
      v-if="showModal"
      class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4"
    >
      <div class="bg-white w-full max-w-md rounded-xl p-6 space-y-4 max-h-[90vh] overflow-y-auto">
        <h2 class="text-xl font-bold">
          {{ isEdit ? "Edit Opening" : "Add Opening" }}
        </h2>

        <p v-if="formError" class="text-red-500 text-sm">{{ formError }}</p>

        <div>
          <label class="block mb-1 text-sm font-medium text-gray-700">Job Title</label>
          <input
            v-model="form.title"
            type="text"
            placeholder="e.g. Frontend Developer (Vue)"
            class="w-full border p-2 rounded focus:outline-none focus:border-orange-500"
          />
        </div>

        <div>
          <label class="block mb-1 text-sm font-medium text-gray-700">Department</label>
          <input
            v-model="form.department"
            type="text"
            placeholder="e.g. Engineering"
            class="w-full border p-2 rounded focus:outline-none focus:border-orange-500"
          />
        </div>

        <div>
          <label class="block mb-1 text-sm font-medium text-gray-700">Location</label>
          <input
            v-model="form.location"
            type="text"
            placeholder="e.g. Manila / Remote"
            class="w-full border p-2 rounded focus:outline-none focus:border-orange-500"
          />
        </div>

        <div>
          <label class="block mb-1 text-sm font-medium text-gray-700">Employment Type</label>
          <select
            v-model="form.employment_type"
            class="w-full border p-2 rounded focus:outline-none focus:border-orange-500"
          >
            <option>Full-time</option>
            <option>Part-time</option>
            <option>Contract</option>
            <option>Internship</option>
          </select>
        </div>

        <div>
          <label class="block mb-1 text-sm font-medium text-gray-700">Description</label>
          <textarea
            v-model="form.description"
            rows="4"
            placeholder="What the role does and why it matters"
            class="w-full border p-2 rounded focus:outline-none focus:border-orange-500"
          ></textarea>
        </div>

        <label class="flex items-center gap-2 text-sm">
          <input type="checkbox" v-model="form.is_active" />
          Published on the Careers page
        </label>

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
import { ref, onMounted } from "vue";
import type { JobOpening } from "../../services/careers";
import {
  getAdminJobOpenings,
  createJobOpening,
  updateJobOpening,
  deleteJobOpening,
} from "../../services/admin/careers";
import { useToastStore } from "../../stores/toast";
import { firstValidationError } from "../../services/account";

const toast = useToastStore();

const openings = ref<JobOpening[]>([]);
const error = ref("");
const formError = ref("");

async function loadOpenings() {
  error.value = "";
  try {
    openings.value = await getAdminJobOpenings();
  } catch {
    error.value = "Failed to load job openings.";
  }
}

onMounted(loadOpenings);

const showModal = ref(false);
const isEdit = ref(false);

type OpeningForm = {
  id: number;
  title: string;
  department: string;
  location: string;
  employment_type: string;
  description: string;
  is_active: boolean;
};

const emptyForm = (): OpeningForm => ({
  id: 0,
  title: "",
  department: "",
  location: "",
  employment_type: "Full-time",
  description: "",
  is_active: true,
});

const form = ref<OpeningForm>(emptyForm());

function openAdd() {
  isEdit.value = false;
  formError.value = "";
  form.value = emptyForm();
  showModal.value = true;
}

function openEdit(opening: JobOpening) {
  isEdit.value = true;
  formError.value = "";
  form.value = {
    id: opening.id,
    title: opening.title,
    department: opening.department,
    location: opening.location,
    employment_type: opening.employment_type,
    description: opening.description,
    is_active: opening.is_active,
  };
  showModal.value = true;
}

function closeModal() {
  showModal.value = false;
}

async function save() {
  if (!form.value.title || !form.value.department || !form.value.location || !form.value.description) {
    formError.value = "All fields are required.";
    return;
  }

  const payload = {
    title: form.value.title,
    department: form.value.department,
    location: form.value.location,
    employment_type: form.value.employment_type,
    description: form.value.description,
    is_active: form.value.is_active,
  };

  try {
    if (isEdit.value) {
      await updateJobOpening(form.value.id, payload);
      toast.success("Opening updated.");
    } else {
      await createJobOpening(payload);
      toast.success("Opening added.");
    }
    closeModal();
    await loadOpenings();
  } catch (e) {
    formError.value = firstValidationError(e, "Failed to save the opening.");
  }
}

async function remove(id: number) {
  if (!confirm("Delete this job opening?")) return;
  try {
    await deleteJobOpening(id);
    await loadOpenings();
    toast.success("Opening removed.");
  } catch (e: any) {
    toast.error(e?.response?.data?.message ?? "Failed to remove the opening.");
  }
}
</script>
