<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold">Reviews</h1>
        <p class="text-gray-500 text-sm">
          Moderate customer product reviews — hidden reviews drop out of the product rating
        </p>
      </div>
    </div>

    <!-- Filters -->
    <div class="flex flex-col sm:flex-row gap-3">
      <div class="relative flex-1">
        <svg class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
        </svg>
        <input
          v-model="search"
          type="text"
          placeholder="Search comment, product, or customer..."
          class="w-full pl-9 pr-4 py-2 border rounded-lg focus:outline-none focus:border-orange-500"
        />
      </div>

      <select
        v-model="ratingFilter"
        class="border rounded-lg px-3 py-2 focus:outline-none focus:border-orange-500"
        @change="page = 1; loadReviews()"
      >
        <option value="">All ratings</option>
        <option v-for="star in [5, 4, 3, 2, 1]" :key="star" :value="star">{{ star }} star{{ star > 1 ? "s" : "" }}</option>
      </select>
    </div>

    <p v-if="error" class="text-red-500 text-sm">{{ error }}</p>

    <!-- Table -->
    <div class="bg-white rounded-xl shadow overflow-x-auto">
      <table class="w-full text-sm min-w-[900px]">
        <thead class="text-left text-gray-500 border-b">
          <tr>
            <th class="p-4">Product</th>
            <th>Customer</th>
            <th>Rating</th>
            <th>Comment</th>
            <th>Photos</th>
            <th>Date</th>
            <th>Status</th>
            <th class="text-right p-4">Actions</th>
          </tr>
        </thead>

        <tbody>
          <tr v-for="review in reviews" :key="review.id" class="border-b hover:bg-gray-50">
            <td class="p-4">
              <router-link
                v-if="review.product"
                :to="`/products/${review.product.slug}`"
                target="_blank"
                class="font-medium text-gray-800 hover:text-orange-500"
              >
                {{ review.product.name }}
              </router-link>
              <span v-else class="text-gray-400">Deleted product</span>
            </td>
            <td>{{ review.user?.name ?? "Deleted user" }}</td>
            <td><StarRating :rating="review.rating" /></td>
            <td class="max-w-[240px]">
              <p class="line-clamp-2 text-gray-600">{{ review.comment || "—" }}</p>
            </td>
            <td>
              <span v-if="review.photo_urls.length" class="text-gray-600">
                {{ review.photo_urls.length }} 📷
              </span>
              <span v-else class="text-gray-300">—</span>
            </td>
            <td class="text-xs text-gray-500">{{ formatDate(review.created_at) }}</td>
            <td>
              <span
                class="px-2 py-1 text-xs rounded-full"
                :class="review.is_hidden ? 'bg-gray-100 text-gray-500' : 'bg-green-100 text-green-600'"
              >
                {{ review.is_hidden ? "Hidden" : "Visible" }}
              </span>
            </td>

            <td class="text-right p-4">
              <div class="flex justify-end gap-2">
                <button
                  :title="review.is_hidden ? 'Unhide' : 'Hide'"
                  class="w-8 h-8 flex items-center justify-center rounded-lg text-blue-500 bg-blue-50 hover:bg-blue-500 hover:text-white transition disabled:opacity-50"
                  :disabled="togglingId === review.id || deletingId === review.id"
                  @click="toggleVisibility(review)"
                >
                  <svg v-if="review.is_hidden" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                  </svg>
                  <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                  </svg>
                </button>

                <button
                  title="Delete"
                  class="w-8 h-8 flex items-center justify-center rounded-lg text-red-500 bg-red-50 hover:bg-red-500 hover:text-white transition disabled:opacity-50"
                  :disabled="togglingId === review.id || deletingId === review.id"
                  @click="remove(review.id)"
                >
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                  </svg>
                </button>
              </div>
            </td>
          </tr>

          <tr v-if="loading">
            <td colspan="8" class="text-center py-10 text-gray-400">Loading reviews...</td>
          </tr>
          <tr v-else-if="reviews.length === 0">
            <td colspan="8" class="text-center py-10 text-gray-400">No reviews found</td>
          </tr>
        </tbody>
      </table>
    </div>

    <div class="bg-white rounded-xl shadow">
      <Pagination
        :current-page="meta.current_page"
        :last-page="meta.last_page"
        :total="meta.total"
        :from="meta.from"
        :to="meta.to"
        @change="goToPage"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, watch } from "vue";
import {
  getAdminReviews,
  setReviewVisibility,
  deleteAdminReview,
  type AdminReview,
} from "../../services/admin/reviews";
import Pagination from "../../components/common/Pagination.vue";
import StarRating from "../../components/common/StarRating.vue";
import { useToastStore } from "../../stores/toast";

const toast = useToastStore();

const reviews = ref<AdminReview[]>([]);
const search = ref("");
const ratingFilter = ref<number | "">("");
const page = ref(1);
const error = ref("");
const togglingId = ref<number | null>(null);
const deletingId = ref<number | null>(null);
const loading = ref(false);

const meta = ref({
  current_page: 1,
  last_page: 1,
  total: 0,
  from: null as number | null,
  to: null as number | null,
});

async function loadReviews() {
  error.value = "";
  loading.value = true;
  try {
    const res = await getAdminReviews({
      page: page.value,
      search: search.value || undefined,
      rating: ratingFilter.value === "" ? undefined : ratingFilter.value,
    });
    reviews.value = res.data;
    meta.value = {
      current_page: res.current_page,
      last_page: res.last_page,
      total: res.total,
      from: res.from,
      to: res.to,
    };
  } catch {
    error.value = "Failed to load reviews.";
  } finally {
    loading.value = false;
  }
}

onMounted(loadReviews);

function goToPage(newPage: number) {
  page.value = newPage;
  loadReviews();
}

let searchTimeout: ReturnType<typeof setTimeout>;
watch(search, () => {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    page.value = 1;
    loadReviews();
  }, 300);
});

function formatDate(iso: string) {
  return new Date(iso).toLocaleDateString(undefined, {
    month: "short",
    day: "numeric",
    year: "numeric",
  });
}

async function toggleVisibility(review: AdminReview) {
  if (togglingId.value === review.id) return;
  togglingId.value = review.id;
  try {
    const updated = await setReviewVisibility(review.id, !review.is_hidden);
    review.is_hidden = updated.is_hidden;
    toast.success(updated.is_hidden ? "Review hidden." : "Review visible again.");
  } catch {
    toast.error("Failed to update the review.");
  } finally {
    togglingId.value = null;
  }
}

async function remove(id: number) {
  if (deletingId.value === id) return;
  if (!confirm("Delete this review? The product rating will be recalculated.")) return;

  deletingId.value = id;
  try {
    await deleteAdminReview(id);
    await loadReviews();
    toast.success("Review deleted.");
  } catch {
    toast.error("Failed to delete the review.");
  } finally {
    deletingId.value = null;
  }
}
</script>
