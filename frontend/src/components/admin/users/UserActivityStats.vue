<template>
  <div class="bg-white rounded-lg shadow-sm p-4 sm:p-6">
    <h4 class="font-semibold text-gray-900 mb-4">User Activity</h4>
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
      <div class="bg-gray-50 rounded-lg p-3 text-center">
        <p class="text-xs text-gray-500">Total Orders</p>
        <p class="text-lg font-bold text-gray-900">{{ stats.total_orders || 0 }}</p>
      </div>
      <div class="bg-gray-50 rounded-lg p-3 text-center">
        <p class="text-xs text-gray-500">Total Spent</p>
        <p class="text-lg font-bold text-[#00685F]">${{ stats.total_spent || 0 }}</p>
      </div>
      <div class="bg-gray-50 rounded-lg p-3 text-center">
        <p class="text-xs text-gray-500">Reviews</p>
        <p class="text-lg font-bold text-gray-900">{{ stats.total_reviews || 0 }}</p>
      </div>
      <div class="bg-gray-50 rounded-lg p-3 text-center">
        <p class="text-xs text-gray-500">Wishlist</p>
        <p class="text-lg font-bold text-gray-900">{{ stats.wishlist_count || 0 }}</p>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import api from '@/api/api'

const props = defineProps<{
  userId: string
}>()

const stats = ref({
  total_orders: 0,
  total_spent: 0,
  total_reviews: 0,
  wishlist_count: 0,
})

const fetchStats = async () => {
  try {
    const response = await api.get(`/admin/users/${props.userId}/stats`)
    stats.value = response.data.data
  } catch (error) {
    console.error('Failed to fetch user stats:', error)
  }
}

onMounted(() => {
  fetchStats()
})
</script>
