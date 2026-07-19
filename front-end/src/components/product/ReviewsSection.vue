<template>
  <section ref="sectionEl" class="mt-10">
    <h2 class="font-display text-xl font-bold text-gray-800 mb-4">Customer Reviews</h2>

    <div class="bg-white rounded-xl shadow p-6">
      <!-- Summary -->
      <div v-if="product.reviews_count === 0 && total === 0" class="text-gray-400 text-sm">
        No reviews yet — be the first to review this product.
      </div>
      <div v-else class="flex flex-col sm:flex-row gap-6 sm:items-center">
        <div class="text-center sm:w-40">
          <p class="text-4xl font-bold text-gray-800">{{ Number(product.rating).toFixed(1) }}</p>
          <div class="flex justify-center mt-1">
            <StarRating :rating="Number(product.rating)" />
          </div>
          <p class="text-sm text-gray-500 mt-1">
            {{ total }} review{{ total === 1 ? "" : "s" }}
          </p>
        </div>
        <div class="flex-1 space-y-1">
          <div v-for="star in [5, 4, 3, 2, 1]" :key="star" class="flex items-center gap-2 text-sm">
            <span class="w-10 text-gray-500">{{ star }} ★</span>
            <div class="flex-1 h-2 bg-gray-100 rounded-full overflow-hidden">
              <div
                class="h-full bg-yellow-400 rounded-full"
                :style="{ width: `${total ? ((breakdown[star] ?? 0) / total) * 100 : 0}%` }"
              ></div>
            </div>
            <span class="w-8 text-right text-gray-400">{{ breakdown[star] ?? 0 }}</span>
          </div>
        </div>
      </div>

      <!-- Write / edit entry -->
      <div class="mt-6 pt-6 border-t">
        <template v-if="!auth.isLoggedIn">
          <router-link
            :to="{ path: '/login', query: { redirect: route.fullPath } }"
            class="text-sm text-orange-500 font-medium hover:underline"
          >
            Sign in to review this product
          </router-link>
        </template>
        <template v-else-if="showForm">
          <ReviewForm
            :slug="product.slug"
            :review="editingOwnReview ? ownReview : null"
            @saved="onSaved"
            @cancel="showForm = false"
          />
        </template>
        <template v-else-if="!ownReview">
          <button
            class="border-2 border-orange-500 text-orange-500 px-5 py-2 rounded-lg text-sm font-semibold hover:bg-orange-50 transition"
            @click="openForm(false)"
          >
            Write a review
          </button>
          <p class="text-xs text-gray-400 mt-2">
            Reviews are open to customers with a delivered order for this product.
          </p>
        </template>
      </div>

      <!-- List -->
      <ul v-if="reviews.length" class="mt-6 divide-y">
        <li v-for="review in reviews" :key="review.id" class="py-4">
          <div class="flex flex-wrap items-center gap-2">
            <span class="font-semibold text-sm text-gray-800">{{ review.user?.name ?? "Customer" }}</span>
            <span class="bg-green-100 text-green-700 text-xs font-medium px-2 py-0.5 rounded-full">
              Verified Purchase
            </span>
            <span class="text-xs text-gray-400">{{ formatDate(review.created_at) }}</span>
          </div>
          <div class="flex items-center gap-2 mt-1">
            <StarRating :rating="review.rating" />
            <template v-if="review.user_id === auth.user?.id">
              <button class="text-xs text-orange-500 hover:underline" @click="openForm(true)">Edit</button>
              <button class="text-xs text-red-500 hover:underline" @click="removeOwnReview">Delete</button>
            </template>
          </div>
          <p v-if="review.comment" class="text-sm text-gray-600 mt-2 whitespace-pre-line">
            {{ review.comment }}
          </p>
          <div v-if="review.photo_urls.length" class="flex flex-wrap gap-2 mt-2">
            <button
              v-for="url in review.photo_urls"
              :key="url"
              type="button"
              class="focus:outline-none"
              @click="lightboxUrl = url"
            >
              <img :src="url" alt="Review photo" class="h-16 w-16 object-cover rounded-lg border" />
            </button>
          </div>
        </li>
      </ul>

      <div v-if="lastPage > 1" class="mt-4 border-t pt-2">
        <Pagination
          :current-page="currentPage"
          :last-page="lastPage"
          :total="total"
          :from="from"
          :to="to"
          @change="loadReviews"
        />
      </div>
    </div>

    <!-- Photo lightbox -->
    <div
      v-if="lightboxUrl"
      class="fixed inset-0 bg-black/80 z-50 flex items-center justify-center p-4"
      @click="lightboxUrl = null"
    >
      <img :src="lightboxUrl" alt="Review photo" class="max-h-[85vh] max-w-full rounded-lg" />
    </div>
  </section>
</template>

<script setup lang="ts">
import { computed, onMounted, ref, watch } from "vue";
import { useRoute, useRouter } from "vue-router";
import { getProductReviews, deleteReview, type Review } from "../../services/reviews";
import type { Product } from "../../services/products";
import { useAuthStore } from "../../stores/auth";
import { useToastStore } from "../../stores/toast";
import StarRating from "../common/StarRating.vue";
import Pagination from "../common/Pagination.vue";
import ReviewForm from "./ReviewForm.vue";

const props = defineProps<{ product: Product }>();
const emit = defineEmits<{ (e: "changed"): void }>();

const route = useRoute();
const router = useRouter();
const auth = useAuthStore();
const toast = useToastStore();

const reviews = ref<Review[]>([]);
const breakdown = ref<Record<string, number>>({});
const currentPage = ref(1);
const lastPage = ref(1);
const total = ref(0);
const from = ref<number | null>(null);
const to = ref<number | null>(null);

const showForm = ref(false);
const editingOwnReview = ref(false);
const lightboxUrl = ref<string | null>(null);
const sectionEl = ref<HTMLElement | null>(null);

// The signed-in user's review surfaces Edit/Delete instead of "Write a
// review". Only reliable when their review is on the current page, which
// covers the common case (page 1, newest first, right after posting).
const ownReview = computed(
  () => reviews.value.find((r) => r.user_id === auth.user?.id) ?? null
);

async function loadReviews(page = 1) {
  try {
    const data = await getProductReviews(props.product.slug, page);
    reviews.value = data.data;
    breakdown.value = data.breakdown ?? {};
    currentPage.value = data.current_page;
    lastPage.value = data.last_page;
    total.value = data.total;
    from.value = data.from;
    to.value = data.to;
  } catch {
    // Leave whatever was rendered; the section isn't worth an error state.
  }
}

function openForm(editing: boolean) {
  editingOwnReview.value = editing;
  showForm.value = true;
}

async function onSaved() {
  showForm.value = false;
  editingOwnReview.value = false;
  await loadReviews(1);
  emit("changed");
}

async function removeOwnReview() {
  if (!ownReview.value) return;
  if (!confirm("Delete your review?")) return;

  try {
    await deleteReview(ownReview.value.id);
    toast.success("Review deleted.");
    await loadReviews(1);
    emit("changed");
  } catch {
    toast.error("Could not delete your review. Please try again.");
  }
}

function formatDate(iso: string) {
  return new Date(iso).toLocaleDateString(undefined, {
    year: "numeric",
    month: "short",
    day: "numeric",
  });
}

// ?review=1 (from MyOrders) opens the form directly, then drops the query
// so refresh/back doesn't reopen it — same pattern as ?checkout=1.
function handleReviewQuery() {
  if (route.query.review !== "1") return;
  if (auth.isLoggedIn) {
    openForm(!!ownReview.value);
    sectionEl.value?.scrollIntoView({ behavior: "smooth" });
  }
  const { review: _review, ...rest } = route.query;
  router.replace({ path: route.path, query: rest });
}

watch(() => route.query.review, handleReviewQuery);

onMounted(async () => {
  await loadReviews();
  handleReviewQuery();
});
</script>
