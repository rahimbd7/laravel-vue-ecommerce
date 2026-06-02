<template>
  <section class="py-12 px-4 bg-white">
    <div class="max-w-7xl mx-auto">
      <!-- Section Header -->
      <div class="text-center mb-10">
        <h2 class="text-3xl md:text-4xl font-bold text-gray-800 mb-3">Shop by Category</h2>
        <p class="text-gray-500 max-w-2xl mx-auto">Browse through our wide range of categories</p>
      </div>

      <!-- Loading State -->
      <div v-if="categoryStore.loading" class="flex justify-center py-12">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
      </div>

      <!-- Error State -->
      <div v-else-if="categoryStore.error" class="text-center py-12">
        <p class="text-red-500">{{ categoryStore.error }}</p>
        <button 
          @click="categoryStore.fetchCategories()" 
          class="mt-4 bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition"
        >
          Try Again
        </button>
      </div>

      <!-- Categories Grid -->
      <div v-else class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-6">
        <CategoryCard 
          v-for="category in mainCategories" 
          :key="category.uuid"
          :category="category"
        />
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
import { computed, onMounted } from 'vue'
import { useCategoryStore } from '@/stores/category.store'
import CategoryCard from '@/components/common/CategoryCard.vue'
import type { Category } from '@/types'

const categoryStore = useCategoryStore()

// Only parent categories (where parent_uuid is null)
const mainCategories = computed(() => categoryStore.parentCategories)

onMounted(() => {
  if (!categoryStore.hasCategories) {
    categoryStore.fetchCategories()
  }
})
</script>