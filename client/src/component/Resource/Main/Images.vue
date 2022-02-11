<template>
  <div
    :class="{
      'mb-3x': digitalImages.length > 3,
      'md:mb-3x': digitalImages.length > 6
    }"
  >
    <h3 class="text-lg font-bold mb-lg">
      <i class="far fa-image mr-xs"></i> Images
    </h3>

    <div
      class="-mx-xs"
      :class="{
        'px-xl': digitalImages.length > 3,
        'md:px-xl': digitalImages.length > 6
      }"
    >
      <VueSlickCarousel v-bind="slickCarouselSettings">
        <div
          v-for="(url, key) in digitalImages"
          :key="key"
        >
          <img
            :src="url" alt="digital image"
            class="border-base border-blue opacity-60 hover:opacity-100 transition-opacity duration-300"
            v-img:resource
          >
        </div>
      </VueSlickCarousel>
    </div>
  </div>
</template>

<script lang="ts">
import VueSlickCarousel from 'vue-slick-carousel';
import 'vue-slick-carousel/dist/vue-slick-carousel.css';
import 'vue-slick-carousel/dist/vue-slick-carousel-theme.css';

import { Component, Vue } from 'vue-property-decorator';
import { resourceModule } from "@/store/modules";

@Component({
  components: {
    VueSlickCarousel,
  },
})
export default class ResourceMainImages extends Vue {
  slickCarouselSettings: any = {
    arrows: true,
    dots: true,
    slidesPerRow: 6,
    adaptiveHeight: true,
    responsive: [
      {
        breakpoint: 768, // = tailwind's "md" breakpoint
        settings: {
          slidesPerRow: 3
        }
      }
    ]
  }

  get resource(): any {
    return resourceModule.getResource;
  }

  get digitalImages (): any[] {
    return resourceModule.getDigitalImages(this.resource);
  }
}
</script>
