<template>
  <div class="space-y-6">
    <!-- Header -->
    <div
      class="flex flex-col md:flex-row md:items-center md:justify-between gap-4"
    >
      <div>
        <h1 class="text-2xl font-bold">Flash Sales</h1>
        <p class="text-gray-500 text-sm">
          Schedule the homepage flash sale — the countdown and product grid follow this
        </p>
      </div>

      <button
        @click="openAdd"
        class="bg-orange-500 text-white px-4 py-2 rounded-lg hover:opacity-90"
      >
        + Schedule Sale
      </button>
    </div>

    <p v-if="error" class="text-red-500 text-sm">{{ error }}</p>

    <!-- Table -->
    <div class="bg-white rounded-xl shadow overflow-x-auto">
      <table class="w-full text-sm min-w-[700px]">
        <thead class="text-left text-gray-500 border-b">
          <tr>
            <th class="p-4">Title</th>
            <th>Starts</th>
            <th>Ends</th>
            <th>Status</th>
            <th class="text-right p-4">Actions</th>
          </tr>
        </thead>

        <tbody>
          <tr
            v-for="sale in sales"
            :key="sale.id"
            class="border-b hover:bg-gray-50"
          >
            <td class="p-4 font-medium">{{ sale.title }}</td>
            <td>{{ formatDate(sale.starts_at) }}</td>
            <td>{{ formatDate(sale.ends_at) }}</td>
            <td>
              <span
                class="px-2 py-1 text-xs rounded-full"
                :class="statusClass(sale)"
              >
                {{ statusLabel(sale) }}
              </span>
            </td>

            <td class="text-right p-4">
              <div class="flex justify-end gap-2">
                <button
                  title="Edit"
                  class="w-8 h-8 flex items-center justify-center rounded-lg text-blue-500 bg-blue-50 hover:bg-blue-500 hover:text-white transition"
                  @click="openEdit(sale)"
                >
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                  </svg>
                </button>

                <button
                  title="Delete"
                  class="w-8 h-8 flex items-center justify-center rounded-lg text-red-500 bg-red-50 hover:bg-red-500 hover:text-white transition"
                  @click="remove(sale.id)"
                >
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                  </svg>
                </button>
              </div>
            </td>
          </tr>

          <tr v-if="sales.length === 0">
            <td colspan="5" class="text-center py-10 text-gray-400">
              No flash sales scheduled — the homepage section stays hidden
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
          {{ isEdit ? "Edit Flash Sale" : "Schedule Flash Sale" }}
        </h2>

        <p v-if="formError" class="text-red-500 text-sm">{{ formError }}</p>

        <div>
          <label class="block mb-1 text-sm font-medium text-gray-700">Title</label>
          <input
            v-model="form.title"
            type="text"
            placeholder="e.g. Payday Mega Sale"
            class="w-full border p-2 rounded focus:outline-none focus:border-orange-500"
          />
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <div>
            <label class="block mb-1 text-sm font-medium text-gray-700">Starts</label>
            <input
              v-model="form.starts_at"
              type="datetime-local"
              class="w-full border p-2 rounded focus:outline-none focus:border-orange-500"
            />
          </div>

          <div>
            <label class="block mb-1 text-sm font-medium text-gray-700">Ends</label>
            <input
              v-model="form.ends_at"
              type="datetime-local"
              class="w-full border p-2 rounded focus:outline-none focus:border-orange-500"
            />
          </div>
        </div>

        <label class="flex items-center gap-2 text-sm">
          <input type="checkbox" v-model="form.is_active" />
          Active (shown on the homepage when live or upcoming)
        </label>

        <div class="flex justify-end gap-2 pt-2">
          <button class="px-4 py-2 text-gray-500" @click="closeModal">
            Cancel
          </button>

          <button
            class="bg-orange-500 text-white px-4 py-2 rounded disabled:opacity-50"
            :disabled="saving"
            @click="save"
          >
            {{ saving ? "Saving..." : isEdit ? "Update" : "Schedule" }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from "vue";
import {
  getAdminFlashSales,
  createFlashSale,
  updateFlashSale,
  deleteFlashSale,
  type FlashSale,
} from "../../services/admin/flashSales";
import { useToastStore } from "../../stores/toast";
import { firstValidationError } from "../../services/account";

const toast = useToastStore();

const sales = ref<FlashSale[]>([]);
const error = ref("");
const formError = ref("");

async function loadSales() {
  error.value = "";
  try {
    sales.value = await getAdminFlashSales();
  } catch {
    error.value = "Failed to load flash sales.";
  }
}

onMounted(loadSales);

function formatDate(iso: string) {
  return new Date(iso).toLocaleString(undefined, {
    month: "short",
    day: "numeric",
    year: "numeric",
    hour: "numeric",
    minute: "2-digit",
  });
}

function statusLabel(sale: FlashSale) {
  if (!sale.is_active) return "Disabled";
  const now = Date.now();
  if (now < Date.parse(sale.starts_at)) return "Upcoming";
  if (now < Date.parse(sale.ends_at)) return "Live";
  return "Ended";
}

function statusClass(sale: FlashSale) {
  const label = statusLabel(sale);
  return {
    Live: "bg-green-100 text-green-600",
    Upcoming: "bg-blue-100 text-blue-600",
    Ended: "bg-gray-100 text-gray-500",
    Disabled: "bg-gray-100 text-gray-500",
  }[label];
}

const showModal = ref(false);
const isEdit = ref(false);

type SaleForm = {
  id: number;
  title: string;
  starts_at: string; // datetime-local value
  ends_at: string;
  is_active: boolean;
};

const emptyForm = (): SaleForm => ({
  id: 0,
  title: "",
  starts_at: "",
  ends_at: "",
  is_active: true,
});

const form = ref<SaleForm>(emptyForm());

// API dates are UTC ISO; datetime-local inputs are local wall time.
// Convert properly in both directions or schedules shift by the UTC offset.
const toInputDate = (iso: string) => {
  if (!iso) return "";
  const d = new Date(iso);
  d.setMinutes(d.getMinutes() - d.getTimezoneOffset());
  return d.toISOString().slice(0, 16);
};

const toApiDate = (local: string) => new Date(local).toISOString();

function openAdd() {
  isEdit.value = false;
  formError.value = "";
  form.value = emptyForm();
  showModal.value = true;
}

function openEdit(sale: FlashSale) {
  isEdit.value = true;
  formError.value = "";
  form.value = {
    id: sale.id,
    title: sale.title,
    starts_at: toInputDate(sale.starts_at),
    ends_at: toInputDate(sale.ends_at),
    is_active: sale.is_active,
  };
  showModal.value = true;
}

function closeModal() {
  showModal.value = false;
}

const saving = ref(false);

async function save() {
  if (saving.value) return;
  if (!form.value.title.trim() || !form.value.starts_at || !form.value.ends_at) {
    formError.value = "Title, start, and end are all required.";
    return;
  }
  if (form.value.ends_at <= form.value.starts_at) {
    formError.value = "The sale must end after it starts.";
    return;
  }

  const payload = {
    title: form.value.title.trim(),
    starts_at: toApiDate(form.value.starts_at),
    ends_at: toApiDate(form.value.ends_at),
    is_active: form.value.is_active,
  };

  saving.value = true;
  try {
    if (isEdit.value) {
      await updateFlashSale(form.value.id, payload);
      toast.success("Flash sale updated.");
    } else {
      await createFlashSale(payload);
      toast.success("Flash sale scheduled.");
    }
    closeModal();
    await loadSales();
  } catch (e) {
    formError.value = firstValidationError(e, "Failed to save the flash sale.");
  } finally {
    saving.value = false;
  }
}

async function remove(id: number) {
  if (!confirm("Delete this flash sale? The homepage section will hide if nothing else is scheduled.")) return;
  try {
    await deleteFlashSale(id);
    await loadSales();
    toast.success("Flash sale removed.");
  } catch (e: any) {
    toast.error(e?.response?.data?.message ?? "Failed to remove the flash sale.");
  }
}
</script>
