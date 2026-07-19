<template>
  <div class="space-y-6">
    <!-- Header -->
    <div
      class="flex flex-col md:flex-row md:items-center md:justify-between gap-4"
    >
      <div>
        <h1 class="text-2xl font-bold">Vouchers</h1>
        <p class="text-gray-500 text-sm">
          Create discount codes customers can apply at checkout
        </p>
      </div>

      <button
        @click="openAdd"
        class="bg-orange-500 text-white px-4 py-2 rounded-lg hover:opacity-90"
      >
        + Add Voucher
      </button>
    </div>

    <p v-if="error" class="text-red-500 text-sm">{{ error }}</p>

    <!-- Table -->
    <div class="bg-white rounded-xl shadow overflow-x-auto">
      <table class="w-full text-sm min-w-[800px]">
        <thead class="text-left text-gray-500 border-b">
          <tr>
            <th class="p-4">Code</th>
            <th>Discount</th>
            <th>Min Spend</th>
            <th>Validity</th>
            <th>Usage</th>
            <th>Status</th>
            <th class="text-right p-4">Actions</th>
          </tr>
        </thead>

        <tbody>
          <tr
            v-for="voucher in vouchers"
            :key="voucher.id"
            class="border-b hover:bg-gray-50"
          >
            <td class="p-4">
              <p class="font-mono font-bold">{{ voucher.code }}</p>
              <p v-if="voucher.description" class="text-xs text-gray-500">
                {{ voucher.description }}
              </p>
            </td>
            <td>{{ discountSummary(voucher) }}</td>
            <td>{{ voucher.min_spend ? `₱${Number(voucher.min_spend).toLocaleString()}` : "—" }}</td>
            <td class="text-xs">{{ validitySummary(voucher) }}</td>
            <td>
              {{ voucher.used_count }} / {{ voucher.usage_limit ?? "∞" }}
              <p v-if="voucher.per_customer_limit" class="text-xs text-gray-400">
                once per customer
              </p>
            </td>
            <td>
              <span
                class="px-2 py-1 text-xs rounded-full"
                :class="
                  voucher.is_active
                    ? 'bg-green-100 text-green-600'
                    : 'bg-gray-100 text-gray-500'
                "
              >
                {{ voucher.is_active ? "Active" : "Disabled" }}
              </span>
              <p v-if="voucher.is_public" class="text-xs text-gray-400 mt-1">Public</p>
            </td>

            <td class="text-right p-4">
              <div class="flex justify-end gap-2">
                <button
                  title="Edit"
                  class="w-8 h-8 flex items-center justify-center rounded-lg text-blue-500 bg-blue-50 hover:bg-blue-500 hover:text-white transition"
                  @click="openEdit(voucher)"
                >
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                  </svg>
                </button>

                <button
                  title="Delete"
                  class="w-8 h-8 flex items-center justify-center rounded-lg text-red-500 bg-red-50 hover:bg-red-500 hover:text-white transition"
                  @click="remove(voucher.id)"
                >
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                  </svg>
                </button>
              </div>
            </td>
          </tr>

          <tr v-if="vouchers.length === 0">
            <td colspan="7" class="text-center py-10 text-gray-400">
              No vouchers yet
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
          {{ isEdit ? "Edit Voucher" : "Add Voucher" }}
        </h2>

        <p v-if="formError" class="text-red-500 text-sm">{{ formError }}</p>

        <div>
          <label class="block mb-1 text-sm font-medium text-gray-700">Code</label>
          <input
            v-model="form.code"
            type="text"
            placeholder="e.g. SAVE10"
            class="w-full border p-2 rounded uppercase font-mono focus:outline-none focus:border-orange-500"
            @input="form.code = form.code.toUpperCase()"
          />
        </div>

        <div>
          <label class="block mb-1 text-sm font-medium text-gray-700">Description (optional)</label>
          <input
            v-model="form.description"
            type="text"
            placeholder="e.g. Payday sale — 10% off"
            class="w-full border p-2 rounded focus:outline-none focus:border-orange-500"
          />
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block mb-1 text-sm font-medium text-gray-700">Type</label>
            <select
              v-model="form.type"
              class="w-full border p-2 rounded focus:outline-none focus:border-orange-500"
              @change="form.type === 'fixed' ? (form.max_discount = null) : null"
            >
              <option value="percent">Percentage off</option>
              <option value="fixed">Fixed amount off</option>
            </select>
          </div>

          <div>
            <label class="block mb-1 text-sm font-medium text-gray-700">
              {{ form.type === "percent" ? "Percent off (%)" : "Amount off (₱)" }}
            </label>
            <input
              v-model.number="form.value"
              type="number"
              min="0"
              :max="form.type === 'percent' ? 100 : undefined"
              class="w-full border p-2 rounded focus:outline-none focus:border-orange-500"
            />
          </div>
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div v-if="form.type === 'percent'">
            <label class="block mb-1 text-sm font-medium text-gray-700">Max discount (₱, optional)</label>
            <input
              v-model.number="form.max_discount"
              type="number"
              min="0"
              placeholder="No cap"
              class="w-full border p-2 rounded focus:outline-none focus:border-orange-500"
            />
          </div>

          <div>
            <label class="block mb-1 text-sm font-medium text-gray-700">Min spend (₱, optional)</label>
            <input
              v-model.number="form.min_spend"
              type="number"
              min="0"
              placeholder="No minimum"
              class="w-full border p-2 rounded focus:outline-none focus:border-orange-500"
            />
          </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <div>
            <label class="block mb-1 text-sm font-medium text-gray-700">Starts (optional)</label>
            <input
              v-model="form.starts_at"
              type="datetime-local"
              class="w-full border p-2 rounded focus:outline-none focus:border-orange-500"
            />
          </div>

          <div>
            <label class="block mb-1 text-sm font-medium text-gray-700">Expires (optional)</label>
            <input
              v-model="form.expires_at"
              type="datetime-local"
              class="w-full border p-2 rounded focus:outline-none focus:border-orange-500"
            />
          </div>
        </div>

        <div>
          <label class="block mb-1 text-sm font-medium text-gray-700">Usage limit (optional)</label>
          <input
            v-model.number="form.usage_limit"
            type="number"
            min="1"
            placeholder="Unlimited"
            class="w-full border p-2 rounded focus:outline-none focus:border-orange-500"
          />
        </div>

        <label class="flex items-center gap-2 text-sm">
          <input type="checkbox" v-model="form.oncePerCustomer" />
          Once per customer
        </label>

        <label class="flex items-center gap-2 text-sm">
          <input type="checkbox" v-model="form.is_active" />
          Active
        </label>

        <label class="flex items-center gap-2 text-sm">
          <input type="checkbox" v-model="form.is_public" />
          Publicly listed (shown on the Vouchers page and in checkout)
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
            {{ saving ? "Saving..." : isEdit ? "Update" : "Add" }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from "vue";
import {
  getAdminVouchers,
  createVoucher,
  updateVoucher,
  deleteVoucher,
  type Voucher,
} from "../../services/admin/vouchers";
import { useToastStore } from "../../stores/toast";
import { firstValidationError } from "../../services/account";

const toast = useToastStore();

const vouchers = ref<Voucher[]>([]);
const error = ref("");
const formError = ref("");

async function loadVouchers() {
  error.value = "";
  try {
    vouchers.value = await getAdminVouchers();
  } catch {
    error.value = "Failed to load vouchers.";
  }
}

onMounted(loadVouchers);

function discountSummary(voucher: Voucher) {
  if (voucher.type === "percent") {
    const cap = voucher.max_discount
      ? ` (max ₱${Number(voucher.max_discount).toLocaleString()})`
      : "";
    return `${Number(voucher.value)}% off${cap}`;
  }
  return `₱${Number(voucher.value).toLocaleString()} off`;
}

function validitySummary(voucher: Voucher) {
  const fmt = (iso: string) =>
    new Date(iso).toLocaleDateString(undefined, { month: "short", day: "numeric", year: "numeric" });
  if (voucher.starts_at && voucher.expires_at) return `${fmt(voucher.starts_at)} – ${fmt(voucher.expires_at)}`;
  if (voucher.expires_at) return `Until ${fmt(voucher.expires_at)}`;
  if (voucher.starts_at) return `From ${fmt(voucher.starts_at)}`;
  return "No expiry";
}

const showModal = ref(false);
const isEdit = ref(false);

type VoucherForm = {
  id: number;
  code: string;
  description: string;
  type: "percent" | "fixed";
  value: number | "";
  max_discount: number | "" | null;
  min_spend: number | "" | null;
  starts_at: string; // datetime-local value or ""
  expires_at: string;
  usage_limit: number | "" | null;
  oncePerCustomer: boolean;
  is_active: boolean;
  is_public: boolean;
};

const emptyForm = (): VoucherForm => ({
  id: 0,
  code: "",
  description: "",
  type: "percent",
  value: "",
  max_discount: null,
  min_spend: null,
  starts_at: "",
  expires_at: "",
  usage_limit: null,
  oncePerCustomer: false,
  is_active: true,
  is_public: true,
});

const form = ref<VoucherForm>(emptyForm());

// API dates are UTC ISO; datetime-local inputs are local wall time.
// Convert properly in both directions or schedules shift by the UTC offset.
const toInputDate = (iso: string | null) => {
  if (!iso) return "";
  const d = new Date(iso);
  d.setMinutes(d.getMinutes() - d.getTimezoneOffset());
  return d.toISOString().slice(0, 16);
};

const toApiDate = (local: string) => (local ? new Date(local).toISOString() : null);

function openAdd() {
  isEdit.value = false;
  formError.value = "";
  form.value = emptyForm();
  showModal.value = true;
}

function openEdit(voucher: Voucher) {
  isEdit.value = true;
  formError.value = "";
  form.value = {
    id: voucher.id,
    code: voucher.code,
    description: voucher.description ?? "",
    type: voucher.type,
    value: Number(voucher.value),
    max_discount: voucher.max_discount !== null ? Number(voucher.max_discount) : null,
    min_spend: voucher.min_spend !== null ? Number(voucher.min_spend) : null,
    starts_at: toInputDate(voucher.starts_at),
    expires_at: toInputDate(voucher.expires_at),
    usage_limit: voucher.usage_limit,
    oncePerCustomer: !!voucher.per_customer_limit,
    is_active: voucher.is_active,
    is_public: voucher.is_public,
  };
  showModal.value = true;
}

function closeModal() {
  showModal.value = false;
}

const numberOrNull = (value: number | "" | null) =>
  value === "" || value === null ? null : Number(value);

const saving = ref(false);

async function save() {
  if (saving.value) return;
  if (!form.value.code.trim() || form.value.value === "" || Number(form.value.value) <= 0) {
    formError.value = "A code and a discount value are required.";
    return;
  }

  const payload = {
    code: form.value.code.trim(),
    description: form.value.description.trim() || null,
    type: form.value.type,
    value: Number(form.value.value),
    max_discount: form.value.type === "percent" ? numberOrNull(form.value.max_discount) : null,
    min_spend: numberOrNull(form.value.min_spend),
    starts_at: toApiDate(form.value.starts_at),
    expires_at: toApiDate(form.value.expires_at),
    usage_limit: numberOrNull(form.value.usage_limit),
    per_customer_limit: form.value.oncePerCustomer ? 1 : null,
    is_active: form.value.is_active,
    is_public: form.value.is_public,
  };

  saving.value = true;
  try {
    if (isEdit.value) {
      await updateVoucher(form.value.id, payload);
      toast.success("Voucher updated.");
    } else {
      await createVoucher(payload);
      toast.success("Voucher created.");
    }
    closeModal();
    await loadVouchers();
  } catch (e) {
    formError.value = firstValidationError(e, "Failed to save the voucher.");
  } finally {
    saving.value = false;
  }
}

async function remove(id: number) {
  if (!confirm("Delete this voucher? Past orders keep their discount.")) return;
  try {
    await deleteVoucher(id);
    await loadVouchers();
    toast.success("Voucher removed.");
  } catch (e: any) {
    toast.error(e?.response?.data?.message ?? "Failed to remove the voucher.");
  }
}
</script>
