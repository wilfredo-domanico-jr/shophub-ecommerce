<template>
  <CategoriesBar />
  <MainBannerCarousel />
  <FlashSaleSection />
  <ShopByCategorySection />
  <TrendingSection />
</template>

<script lang="ts">
import { defineComponent } from "vue";

// PAGE SPECIFIC

import CategoriesBar from "../components/home/CategoriesBar.vue";
import MainBannerCarousel from "../components/home/MainBannerCarousel.vue";
import FlashSaleSection from "../components/home/FlashSaleSection.vue";
import ShopByCategorySection from "../components/home/ShopByCategorySection.vue";
import TrendingSection from "../components/home/TrendingSection.vue";

export default defineComponent({
  name: "Home",
  components: {
    CategoriesBar,
    MainBannerCarousel,
    FlashSaleSection,
    ShopByCategorySection,
    TrendingSection,
  },
});
</script>
