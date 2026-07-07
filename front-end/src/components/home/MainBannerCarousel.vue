<template>
  <div class="container mx-auto px-4 py-6">
    <div class="relative rounded-2xl overflow-hidden shadow-xl">
      <div id="bannerCarousel" class="relative h-64 md:h-96">
        <div
          v-for="(slide, index) in slides"
          :key="index"
          class="banner-slide absolute inset-0 transition-opacity duration-500"
          :class="{
            'opacity-100 z-10': currentBanner === index,
            'opacity-0 z-0': currentBanner !== index,
          }"
        >
          <div
            :class="[
              'w-full h-full flex items-center justify-center',
              slide.gradientClass,
            ]"
          >
            <div class="text-center px-4" :class="slide.textColor">
              <h2
                class="font-display text-3xl md:text-5xl font-bold mb-4 animate-fadeIn"
              >
                {{ slide.title }}
              </h2>
              <p
                class="text-lg md:text-xl mb-6 animate-fadeIn"
                style="animation-delay: 0.2s"
              >
                {{ slide.subtitle }}
              </p>
              <router-link
                :to="slide.link"
                :class="[
                  'inline-block px-8 py-3 rounded-full font-semibold transition animate-scaleIn',
                  slide.btnClass,
                ]"
                style="animation-delay: 0.4s"
              >
                {{ slide.buttonText }}
              </router-link>
            </div>
          </div>
        </div>
      </div>

      <button
        @click="goPrev"
        class="absolute left-4 top-1/2 -translate-y-1/2 bg-white/80 hover:bg-white p-2 rounded-full transition z-20"
      >
        <svg
          class="w-6 h-6"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M15 19l-7-7 7-7"
          />
        </svg>
      </button>

      <button
        @click="goNext"
        class="absolute right-4 top-1/2 -translate-y-1/2 bg-white/80 hover:bg-white p-2 rounded-full transition z-20"
      >
        <svg
          class="w-6 h-6"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M9 5l7 7-7 7"
          />
        </svg>
      </button>

      <div class="absolute bottom-4 left-1/2 -translate-x-1/2 flex gap-2 z-20">
        <button
          v-for="(_, index) in slides"
          :key="index"
          @click="setBanner(index)"
          class="transition-all duration-300 rounded-full h-2"
          :class="[
            currentBanner === index ? 'bg-white w-6' : 'bg-white/50 w-2',
          ]"
        ></button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted } from "vue";

interface Slide {
  title: string;
  subtitle: string;
  buttonText: string;
  gradientClass: string;
  textColor: string;
  btnClass: string;
  link: string;
}

// Slide Data
const slides: Slide[] = [
  {
    title: "Mega Sale Season",
    subtitle: "Up to 80% OFF on Electronics",
    buttonText: "Shop Now",
    gradientClass: "gradient-primary",
    textColor: "text-white",
    btnClass: "bg-white text-orange-500 hover:bg-gray-100",
    link: "/products?category=electronics",
  },
  {
    title: "Fashion Forward",
    subtitle: "New Arrivals Every Week",
    buttonText: "Explore Collection",
    gradientClass: "gradient-secondary",
    textColor: "text-white",
    btnClass: "bg-white text-purple-600 hover:bg-gray-100",
    link: "/products?category=fashion",
  },
  {
    title: "Free Shipping",
    subtitle: "On Orders Over ₱500",
    buttonText: "Start Shopping",
    gradientClass: "gradient-accent",
    textColor: "text-gray-900",
    btnClass: "bg-gray-900 text-white hover:bg-gray-800",
    link: "/products",
  },
];

const currentBanner = ref(0);
let timer: ReturnType<typeof setInterval> | null = null;

// Logic functions
const nextBanner = () => {
  currentBanner.value = (currentBanner.value + 1) % slides.length;
};

const prevBanner = () => {
  currentBanner.value =
    (currentBanner.value - 1 + slides.length) % slides.length;
};

// Manual navigation should restart the auto-rotate countdown so it doesn't
// immediately fight the user's click with another auto-advance.
const goNext = () => {
  nextBanner();
  resetTimer();
};

const goPrev = () => {
  prevBanner();
  resetTimer();
};

const setBanner = (index: number) => {
  currentBanner.value = index;
  resetTimer();
};

// Lifecycle management
const startTimer = () => {
  timer = setInterval(nextBanner, 5000);
};

const resetTimer = () => {
  if (timer) clearInterval(timer);
  startTimer();
};

onMounted(() => {
  startTimer();
});

onUnmounted(() => {
  if (timer) clearInterval(timer);
});
</script>
