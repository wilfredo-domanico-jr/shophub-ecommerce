<template>
  <div class="container mx-auto px-4 py-10 max-w-3xl">
    <nav class="text-sm text-gray-500 mb-6">
      <router-link to="/" class="hover:text-orange-500">Home</router-link>
      <span class="mx-2">/</span>
      <span class="text-gray-700">{{ content?.title ?? "Page" }}</span>
    </nav>

    <div v-if="!content" class="bg-white rounded-xl shadow p-12 text-center text-gray-400">
      Page not found.
    </div>

    <template v-else>
      <h1 class="font-display text-3xl font-bold text-gray-800 mb-2">{{ content.title }}</h1>
      <p class="text-gray-500 mb-8">{{ content.tagline }}</p>

      <!-- Job openings (careers) — fetched from the API, managed in the admin panel -->
      <div v-if="content.showJobOpenings" class="mb-8">
        <h2 class="font-semibold text-lg text-gray-800 mb-3">Current Openings</h2>
        <div v-if="jobs.length" class="space-y-3">
          <div
            v-for="job in jobs"
            :key="job.id"
            class="bg-white rounded-xl shadow p-6"
          >
            <div class="flex flex-wrap items-start justify-between gap-3 mb-2">
              <div>
                <h3 class="font-semibold text-gray-800">{{ job.title }}</h3>
                <p class="text-xs text-gray-500">{{ job.department }}</p>
              </div>
              <div class="flex gap-2">
                <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-orange-100 text-orange-700">
                  {{ job.employment_type }}
                </span>
                <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-600">
                  {{ job.location }}
                </span>
              </div>
            </div>
            <p class="text-sm text-gray-600 leading-relaxed mb-4">{{ job.description }}</p>
            <a
              :href="`mailto:careers@shophub.test?subject=${encodeURIComponent(`Application: ${job.title}`)}`"
              class="text-sm font-medium text-orange-500 hover:text-orange-600"
            >
              Apply via email →
            </a>
          </div>
        </div>
        <p v-else class="bg-white rounded-xl shadow p-6 text-sm text-gray-500">
          We don't have any open positions right now — check back soon, or send us your resume anyway.
        </p>
      </div>

      <!-- FAQ style content -->
      <div v-if="content.faqs" class="space-y-3">
        <div
          v-for="(faq, i) in content.faqs"
          :key="i"
          class="bg-white rounded-xl shadow overflow-hidden"
        >
          <button
            class="w-full flex items-center justify-between p-4 text-left font-medium text-gray-800"
            @click="openFaq = openFaq === i ? -1 : i"
          >
            {{ faq.q }}
            <svg
              class="w-5 h-5 text-gray-400 transition-transform shrink-0 ml-4"
              :class="{ 'rotate-180': openFaq === i }"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
          </button>
          <div v-if="openFaq === i" class="px-4 pb-4 text-sm text-gray-600 leading-relaxed">
            {{ faq.a }}
          </div>
        </div>
      </div>

      <!-- Section style content -->
      <div v-if="content.sections" class="space-y-6">
        <div v-for="(section, i) in content.sections" :key="i" class="bg-white rounded-xl shadow p-6">
          <h2 class="font-semibold text-lg text-gray-800 mb-2">{{ section.heading }}</h2>
          <p class="text-sm text-gray-600 leading-relaxed">{{ section.body }}</p>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch } from "vue";
import { infoPages } from "../data/infoPages";
import { getJobOpenings, type JobOpening } from "../services/careers";

const props = defineProps<{ slug: string }>();

const openFaq = ref(0);
const content = computed(() => infoPages[props.slug]);

const jobs = ref<JobOpening[]>([]);

// watch (not onMounted): the same component instance is reused when
// navigating between info pages.
watch(
  () => props.slug,
  async () => {
    if (!content.value?.showJobOpenings) return;
    try {
      jobs.value = await getJobOpenings();
    } catch {
      jobs.value = [];
    }
  },
  { immediate: true }
);
</script>
