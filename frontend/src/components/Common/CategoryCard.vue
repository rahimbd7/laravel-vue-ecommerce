<template>
  <div 
    class="group cursor-pointer"
    @click="navigateToCategory"
  >
    <!-- Circle with Icon/Image -->
    <div class="w-25 h-25 md:w-25 md:h-25 sm:w-20 sm:h-20 mx-auto mb-3 rounded-full bg-linear-to-br from-gray-100 to-gray-200 flex items-center justify-center transition-all duration-300 group-hover:-translate-y-1.25 group-hover:from-[#00685F] group-hover:to-[#004F45] group-hover:shadow-lg group-hover:shadow-[#00685F]/30">
      <i :class="category.icon" class="text-2xl text-[#00685F] transition-colors duration-300 group-hover:text-white"></i>
    </div>
    
    <!-- Category Name -->
    <h3 class="text-sm md:text-sm font-medium text-[#00685F] text-center transition-colors duration-300 group-hover:text-[#00685F]">
      {{ category.name }}
    </h3>
  </div>
</template>

<script setup lang="ts">
import { useRouter } from 'vue-router'
import type { Category } from '@/types'

const router = useRouter()

const props = defineProps<{
  category: Category
}>()

defineEmits<{
  (e: 'click', category: Category): void
}>()

const navigateToCategory = () => {
   console.log('Navigating to category:', props.category.id)
  router.push({
    path: '/shop',
    query: {
      category_id: props.category.id
    }
  })
}</script>
