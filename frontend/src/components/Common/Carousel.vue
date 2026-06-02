<template>
  <Carousel 
    :autoplay="4000" 
    :items-to-show="1" 
    :wrap-around="true"
    class="hero-carousel"
  >
    <Slide v-for="slide in slides" :key="slide.id">
      <div class="relative">
        <img :src="slide.image" class="w-full h-125 object-cover" />
        <div class="absolute inset-0 bg-black/40 flex items-center justify-center">
          <div class="text-center text-white">
            <h2 class="text-4xl font-bold mb-4">{{ slide.title }}</h2>
            <p class="text-xl mb-6">{{ slide.subtitle }}</p>
            <router-link to="/shop" class="inline-block bg-[#00685F] px-6 py-3 rounded-lg hover:bg-[#004F45] transition font-medium">
              Shop Now
            </router-link>
          </div>
        </div>
      </div>
    </Slide>
  </Carousel>
</template>

<script setup lang="ts">
import { Carousel, Slide } from 'vue3-carousel';
import 'vue3-carousel/dist/carousel.css';

interface SlideItem {
  id: number;
  image: string;
  title: string;
  subtitle: string;
}

const slides: SlideItem[] = [
  { 
    id: 1, 
    image: 'https://picsum.photos/id/10/1200/500', 
    title: 'Summer Sale 2024', 
    subtitle: 'Up to 50% off on selected items' 
  },
  { 
    id: 2, 
    image: 'https://picsum.photos/id/20/1200/500', 
    title: 'New Arrivals', 
    subtitle: 'Discover the latest collection' 
  },
  { 
    id: 3, 
    image: 'https://picsum.photos/id/26/1200/500', 
    title: 'Winter Collection', 
    subtitle: 'Stay warm in style' 
  },
];
</script>

<style scoped>
.hero-carousel {
  margin-bottom: 2rem;
}

/* Style navigation buttons - use :deep() for scoped styles */
:deep(.carousel__prev),
:deep(.carousel__next) {
  background-color: white !important;
  border-radius: 50% !important;
  width: 40px !important;
  height: 40px !important;
  box-shadow: 0 2px 8px rgba(0,0,0,0.2) !important;
}

:deep(.carousel__prev:hover),
:deep(.carousel__next:hover) {
  background-color: #f0f0f0 !important;
}

:deep(.carousel__viewport) {
  border-radius: 12px;
}

:deep(.carousel__slide) {
  position: relative;
}
</style>