<template>
  <form class="bg-gray-50 border rounded-xl p-4 space-y-4" @submit.prevent="submit">
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">Your rating</label>
      <RatingInput v-model="rating" />
    </div>

    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">
        Comment <span class="text-gray-400 font-normal">(optional)</span>
      </label>
      <textarea
        v-model="comment"
        rows="3"
        maxlength="2000"
        placeholder="Share what you liked (or didn't) about this product"
        class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-orange-300"
      ></textarea>
    </div>

    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">
        Photos <span class="text-gray-400 font-normal">(optional, up to 4)</span>
      </label>
      <div class="flex flex-wrap items-center gap-2">
        <div v-for="(photo, i) in existingPhotos" :key="photo.path" class="relative">
          <img :src="photo.url" alt="" class="h-16 w-16 object-cover rounded-lg border" />
          <button
            type="button"
            class="absolute -top-1.5 -right-1.5 w-5 h-5 rounded-full bg-gray-800 text-white text-xs leading-none"
            aria-label="Remove photo"
            @click="removeExistingPhoto(i)"
          >
            ×
          </button>
        </div>
        <div v-for="(photo, i) in photos" :key="photo.key" class="relative">
          <img :src="photo.preview" alt="" class="h-16 w-16 object-cover rounded-lg border" />
          <button
            type="button"
            class="absolute -top-1.5 -right-1.5 w-5 h-5 rounded-full bg-gray-800 text-white text-xs leading-none"
            aria-label="Remove photo"
            @click="removePhoto(i)"
          >
            ×
          </button>
        </div>
        <label
          v-if="totalPhotos < 4"
          class="h-16 w-16 flex items-center justify-center border-2 border-dashed rounded-lg text-gray-400 cursor-pointer hover:border-orange-300 hover:text-orange-400 transition"
        >
          <span class="text-2xl leading-none">+</span>
          <input type="file" accept="image/*" multiple class="hidden" @change="addPhotos" />
        </label>
      </div>
    </div>

    <p v-if="error" class="text-sm text-red-500">{{ error }}</p>

    <div class="flex gap-2">
      <button
        type="submit"
        class="gradient-primary text-white px-5 py-2 rounded-lg text-sm font-semibold hover:opacity-90 transition disabled:opacity-50"
        :disabled="saving || rating === 0"
      >
        {{ saving ? "Saving..." : review ? "Update Review" : "Submit Review" }}
      </button>
      <button
        type="button"
        class="px-5 py-2 rounded-lg text-sm font-medium border text-gray-600 hover:bg-gray-100 transition"
        @click="emit('cancel')"
      >
        Cancel
      </button>
    </div>
  </form>
</template>

<script setup lang="ts">
import { computed, onBeforeUnmount, ref } from "vue";
import { createReview, updateReview, type Review } from "../../services/reviews";
import { firstValidationError } from "../../services/account";
import { useToastStore } from "../../stores/toast";
import RatingInput from "../common/RatingInput.vue";

const props = defineProps<{
  slug: string;
  /** Editing an existing review when set. */
  review?: Review | null;
}>();

const emit = defineEmits<{ (e: "saved"): void; (e: "cancel"): void }>();

const toast = useToastStore();

const rating = ref(props.review?.rating ?? 0);
const comment = ref(props.review?.comment ?? "");
const photos = ref<{ key: number; file: File; preview: string }[]>([]);
// Already-uploaded photos when editing; removal here only takes effect
// server-side on submit. photos/photo_urls are index-aligned on the API.
const existingPhotos = ref(
  (props.review?.photos ?? []).map((path, i) => ({
    path,
    url: props.review?.photo_urls[i] ?? "",
  }))
);
const error = ref("");
const saving = ref(false);

const totalPhotos = computed(() => existingPhotos.value.length + photos.value.length);

let photoKey = 0;

function addPhotos(event: Event) {
  const input = event.target as HTMLInputElement;
  for (const file of Array.from(input.files ?? [])) {
    if (totalPhotos.value >= 4) break;
    photos.value.push({ key: photoKey++, file, preview: URL.createObjectURL(file) });
  }
  input.value = "";
}

function removePhoto(index: number) {
  const [removed] = photos.value.splice(index, 1);
  if (removed) URL.revokeObjectURL(removed.preview);
}

function removeExistingPhoto(index: number) {
  existingPhotos.value.splice(index, 1);
}

onBeforeUnmount(() => photos.value.forEach((p) => URL.revokeObjectURL(p.preview)));

async function submit() {
  if (saving.value || rating.value === 0) return;
  saving.value = true;
  error.value = "";

  try {
    if (props.review) {
      const kept = new Set(existingPhotos.value.map((p) => p.path));
      await updateReview(props.review.id, {
        rating: rating.value,
        comment: comment.value.trim() || undefined,
        photos: photos.value.map((p) => p.file),
        removePhotos: (props.review.photos ?? []).filter((path) => !kept.has(path)),
      });
      toast.success("Review updated.");
    } else {
      await createReview(props.slug, {
        rating: rating.value,
        comment: comment.value.trim() || undefined,
        photos: photos.value.map((p) => p.file),
      });
      toast.success("Thanks for your review!");
    }
    emit("saved");
  } catch (e) {
    error.value = firstValidationError(e, "Could not save your review. Please try again.");
  } finally {
    saving.value = false;
  }
}
</script>
