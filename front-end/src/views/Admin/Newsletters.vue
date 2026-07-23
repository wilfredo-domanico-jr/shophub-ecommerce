<template>
  <div class="space-y-6">
    <!-- Header -->
    <div
      class="flex flex-col md:flex-row md:items-center md:justify-between gap-4"
    >
      <div>
        <h1 class="text-2xl font-bold">Newsletter</h1>
        <p class="text-gray-500 text-sm">
          {{ subscribersCount }} subscriber{{ subscribersCount === 1 ? "" : "s" }} —
          write, save drafts, and send campaigns
        </p>
      </div>

      <button
        @click="openAdd"
        class="bg-orange-500 text-white px-4 py-2 rounded-lg hover:opacity-90"
      >
        + New Newsletter
      </button>
    </div>

    <p v-if="error" class="text-red-500 text-sm">{{ error }}</p>

    <!-- Tabs -->
    <div class="flex gap-2">
      <button
        v-for="t in (['campaigns', 'subscribers'] as const)"
        :key="t"
        class="px-4 py-2 rounded-lg text-sm font-medium transition"
        :class="tab === t ? 'gradient-primary text-white shadow' : 'bg-white text-gray-600 hover:bg-orange-50'"
        @click="tab = t"
      >
        {{ t === "campaigns" ? "Campaigns" : "Subscribers" }}
      </button>
    </div>

    <!-- Campaigns table -->
    <div v-if="tab === 'campaigns'" class="bg-white rounded-xl shadow overflow-x-auto">
      <table class="w-full text-sm min-w-[700px]">
        <thead class="text-left text-gray-500 border-b">
          <tr>
            <th class="p-4">Subject</th>
            <th>Status</th>
            <th>Sent</th>
            <th class="text-right p-4">Actions</th>
          </tr>
        </thead>

        <tbody>
          <tr
            v-for="newsletter in newsletters"
            :key="newsletter.id"
            class="border-b hover:bg-gray-50"
          >
            <td class="p-4">
              <div class="flex items-center gap-3">
                <img
                  v-if="newsletter.image_url"
                  :src="newsletter.image_url"
                  class="w-9 h-9 rounded-lg object-cover shrink-0"
                />
                <div>
                  <p class="font-medium">{{ newsletter.subject }}</p>
                  <p class="text-xs text-gray-500 line-clamp-1 max-w-md">{{ newsletter.body }}</p>
                </div>
              </div>
            </td>
            <td>
              <span
                class="px-2 py-1 text-xs rounded-full"
                :class="
                  newsletter.status === 'sent'
                    ? 'bg-green-100 text-green-600'
                    : 'bg-yellow-100 text-yellow-700'
                "
              >
                {{ newsletter.status === "sent" ? "Sent" : "Draft" }}
              </span>
            </td>
            <td class="text-gray-500">
              {{ newsletter.sent_at ? new Date(newsletter.sent_at).toLocaleString() : "—" }}
            </td>

            <td class="text-right p-4">
              <div class="flex justify-end gap-2">
                <button
                  v-if="newsletter.status === 'draft'"
                  title="Send to all subscribers"
                  class="w-8 h-8 flex items-center justify-center rounded-lg text-green-600 bg-green-50 hover:bg-green-600 hover:text-white transition disabled:opacity-50"
                  :disabled="sendingId !== null"
                  @click="send(newsletter)"
                >
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                  </svg>
                </button>

                <button
                  v-if="newsletter.status === 'draft'"
                  title="Edit"
                  class="w-8 h-8 flex items-center justify-center rounded-lg text-blue-500 bg-blue-50 hover:bg-blue-500 hover:text-white transition"
                  @click="openEdit(newsletter)"
                >
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                  </svg>
                </button>

                <button
                  title="Delete"
                  class="w-8 h-8 flex items-center justify-center rounded-lg text-red-500 bg-red-50 hover:bg-red-500 hover:text-white transition"
                  @click="remove(newsletter.id)"
                >
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                  </svg>
                </button>
              </div>
            </td>
          </tr>

          <tr v-if="loading">
            <td colspan="4" class="text-center py-10 text-gray-400">
              Loading newsletters...
            </td>
          </tr>
          <tr v-else-if="newsletters.length === 0">
            <td colspan="4" class="text-center py-10 text-gray-400">
              No newsletters yet — write your first one
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Subscribers -->
    <template v-if="tab === 'subscribers'">
      <div class="bg-white p-4 rounded-xl shadow">
        <div class="relative max-w-sm">
          <svg class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
          </svg>
          <input
            v-model="subscriberSearch"
            type="text"
            placeholder="Search email..."
            class="w-full pl-9 pr-4 py-2 border rounded-lg focus:outline-none focus:border-orange-500"
          />
        </div>
      </div>

      <div class="bg-white rounded-xl shadow overflow-x-auto">
        <table class="w-full text-sm min-w-[600px]">
          <thead class="text-left text-gray-500 border-b">
            <tr>
              <th class="p-4">Email</th>
              <th>Subscribed On</th>
              <th>Status</th>
              <th class="text-right p-4">Actions</th>
            </tr>
          </thead>

          <tbody>
            <tr
              v-for="subscriber in subscribers"
              :key="subscriber.id"
              class="border-b hover:bg-gray-50"
            >
              <td class="p-4 font-medium">{{ subscriber.email }}</td>
              <td class="text-gray-500">
                {{ new Date(subscriber.created_at).toLocaleDateString() }}
              </td>
              <td>
                <span
                  class="px-2 py-1 text-xs rounded-full"
                  :class="
                    subscriber.unsubscribed_at
                      ? 'bg-gray-100 text-gray-500'
                      : 'bg-green-100 text-green-600'
                  "
                >
                  {{ subscriber.unsubscribed_at ? "Unsubscribed" : "Subscribed" }}
                </span>
              </td>
              <td class="text-right p-4">
                <button
                  title="Remove subscriber"
                  class="w-8 h-8 inline-flex items-center justify-center rounded-lg text-red-500 bg-red-50 hover:bg-red-500 hover:text-white transition"
                  @click="removeSubscriber(subscriber.id)"
                >
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                  </svg>
                </button>
              </td>
            </tr>

            <tr v-if="subscribersLoading">
              <td colspan="4" class="text-center py-10 text-gray-400">
                Loading subscribers...
              </td>
            </tr>
            <tr v-else-if="subscribers.length === 0">
              <td colspan="4" class="text-center py-10 text-gray-400">
                No subscribers found
              </td>
            </tr>
          </tbody>
        </table>

        <Pagination
          :current-page="subscriberMeta.current_page"
          :last-page="subscriberMeta.last_page"
          :total="subscriberMeta.total"
          :from="subscriberMeta.from"
          :to="subscriberMeta.to"
          @change="goToSubscriberPage"
        />
      </div>
    </template>

    <!-- MODAL -->
    <div
      v-if="showModal"
      class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4"
    >
      <div class="bg-white w-full max-w-lg rounded-xl p-6 space-y-4 max-h-[90vh] overflow-y-auto">
        <h2 class="text-xl font-bold">
          {{ isEdit ? "Edit Newsletter" : "New Newsletter" }}
        </h2>

        <p v-if="formError" class="text-red-500 text-sm">{{ formError }}</p>

        <div>
          <label class="block mb-1 text-sm font-medium text-gray-700">Subject</label>
          <input
            v-model="form.subject"
            type="text"
            placeholder="e.g. Weekend Flash Sale — up to 50% off"
            class="w-full border p-2 rounded focus:outline-none focus:border-orange-500"
          />
        </div>

        <div>
          <label class="block mb-1 text-sm font-medium text-gray-700">Message</label>
          <textarea
            v-model="form.body"
            rows="7"
            placeholder="Write the newsletter content. Blank lines start a new paragraph."
            class="w-full border p-2 rounded focus:outline-none focus:border-orange-500"
          ></textarea>
        </div>

        <div>
          <label class="block mb-1 text-sm font-medium text-gray-700">
            Banner Image <span class="text-gray-400 font-normal">(optional)</span>
          </label>
          <ImageDropzone v-model="form.image_url" />
        </div>

        <div class="flex justify-end gap-2 pt-2">
          <button class="px-4 py-2 text-gray-500" @click="closeModal">
            Cancel
          </button>

          <button
            class="bg-orange-500 text-white px-4 py-2 rounded disabled:opacity-50"
            :disabled="saving"
            @click="save"
          >
            {{ saving ? "Saving..." : isEdit ? "Save Draft" : "Save as Draft" }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, watch } from "vue";
import type { Newsletter, NewsletterSubscriber } from "../../services/admin/newsletters";
import {
  getAdminNewsletters,
  createNewsletter,
  updateNewsletter,
  deleteNewsletter,
  sendNewsletter,
  getAdminSubscribers,
  deleteSubscriber,
} from "../../services/admin/newsletters";
import ImageDropzone from "../../components/admin/ImageDropzone.vue";
import Pagination from "../../components/common/Pagination.vue";
import { useToastStore } from "../../stores/toast";
import { firstValidationError } from "../../services/account";

const toast = useToastStore();

const tab = ref<"campaigns" | "subscribers">("campaigns");

const newsletters = ref<Newsletter[]>([]);
const subscribersCount = ref(0);
const error = ref("");
const formError = ref("");
const loading = ref(false);

async function loadNewsletters() {
  error.value = "";
  loading.value = true;
  try {
    const data = await getAdminNewsletters();
    newsletters.value = data.newsletters;
    subscribersCount.value = data.subscribers_count;
  } catch {
    error.value = "Failed to load newsletters.";
  } finally {
    loading.value = false;
  }
}

onMounted(loadNewsletters);

// Subscribers tab
const subscribers = ref<NewsletterSubscriber[]>([]);
const subscriberSearch = ref("");
const subscriberPage = ref(1);
const subscriberMeta = ref({
  current_page: 1,
  last_page: 1,
  total: 0,
  from: 0 as number | null,
  to: 0 as number | null,
});

const subscribersLoading = ref(false);

async function loadSubscribers() {
  subscribersLoading.value = true;
  try {
    const res = await getAdminSubscribers({
      search: subscriberSearch.value || undefined,
      page: subscriberPage.value,
    });
    subscribers.value = res.data;
    subscriberMeta.value = {
      current_page: res.current_page,
      last_page: res.last_page,
      total: res.total,
      from: res.from,
      to: res.to,
    };
  } catch {
    error.value = "Failed to load subscribers.";
  } finally {
    subscribersLoading.value = false;
  }
}

function goToSubscriberPage(p: number) {
  subscriberPage.value = p;
  loadSubscribers();
}

watch(tab, (value) => {
  if (value === "subscribers") loadSubscribers();
});

let subscriberSearchTimeout: ReturnType<typeof setTimeout>;
watch(subscriberSearch, () => {
  clearTimeout(subscriberSearchTimeout);
  subscriberSearchTimeout = setTimeout(() => {
    subscriberPage.value = 1;
    loadSubscribers();
  }, 300);
});

async function removeSubscriber(id: number) {
  if (!confirm("Remove this subscriber? They'll stop receiving newsletters entirely.")) return;
  try {
    await deleteSubscriber(id);
    await Promise.all([loadSubscribers(), loadNewsletters()]);
    toast.success("Subscriber removed.");
  } catch (e: any) {
    toast.error(e?.response?.data?.message ?? "Failed to remove the subscriber.");
  }
}

const showModal = ref(false);
const isEdit = ref(false);

type NewsletterForm = {
  id: number;
  subject: string;
  body: string;
  image_url: string;
};

const emptyForm = (): NewsletterForm => ({
  id: 0,
  subject: "",
  body: "",
  image_url: "",
});

const form = ref<NewsletterForm>(emptyForm());

function openAdd() {
  isEdit.value = false;
  formError.value = "";
  form.value = emptyForm();
  showModal.value = true;
}

function openEdit(newsletter: Newsletter) {
  isEdit.value = true;
  formError.value = "";
  form.value = {
    id: newsletter.id,
    subject: newsletter.subject,
    body: newsletter.body,
    image_url: newsletter.image_url ?? "",
  };
  showModal.value = true;
}

function closeModal() {
  showModal.value = false;
}

const saving = ref(false);

async function save() {
  if (saving.value) return;
  if (!form.value.subject || !form.value.body) {
    formError.value = "Subject and message are required.";
    return;
  }
  saving.value = true;

  const payload = {
    subject: form.value.subject,
    body: form.value.body,
    image_url: form.value.image_url || null,
  };

  try {
    if (isEdit.value) {
      await updateNewsletter(form.value.id, payload);
      toast.success("Draft saved.");
    } else {
      await createNewsletter(payload);
      toast.success("Saved as draft.");
    }
    closeModal();
    await loadNewsletters();
  } catch (e) {
    formError.value = firstValidationError(e, "Failed to save the newsletter.");
  } finally {
    saving.value = false;
  }
}

// Guarded: a double-click here would email the entire subscriber list twice.
const sendingId = ref<number | null>(null);

async function send(newsletter: Newsletter) {
  if (sendingId.value !== null) return;
  if (!confirm(`Send "${newsletter.subject}" to all ${subscribersCount.value} subscribers?`)) return;
  sendingId.value = newsletter.id;
  try {
    const { message } = await sendNewsletter(newsletter.id);
    toast.success(message);
    await loadNewsletters();
  } catch (e: any) {
    toast.error(e?.response?.data?.message ?? "Failed to send the newsletter.");
  } finally {
    sendingId.value = null;
  }
}

async function remove(id: number) {
  if (!confirm("Delete this newsletter?")) return;
  try {
    await deleteNewsletter(id);
    await loadNewsletters();
    toast.success("Newsletter removed.");
  } catch (e: any) {
    toast.error(e?.response?.data?.message ?? "Failed to remove the newsletter.");
  }
}
</script>
