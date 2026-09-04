<template>
  <div class="space-y-4 sm:space-y-6">
    <!-- Page Header -->
    <div class="bg-white rounded-lg shadow-sm p-4 sm:p-6">
      <h1 class="text-xl sm:text-2xl font-bold text-gray-900">Shipping Settings</h1>
      <p class="text-sm sm:text-base text-gray-600">Configure your shipping options</p>
    </div>

    <!-- Loading State -->
    <div v-if="loading.shipping" class="flex justify-center items-center py-20">
      <div class="animate-spin rounded-full h-16 w-16 border-b-2 border-[#00685F]"></div>
    </div>

    <!-- Shipping Form -->
    <div v-else class="bg-white rounded-lg shadow-sm p-4 sm:p-6">
      <form @submit.prevent="saveShipping" class="space-y-4 sm:space-y-6">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">
          <!-- Free Shipping Threshold -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Free Shipping Threshold</label>
            <InputNumber 
              v-model="settings.free_shipping_threshold" 
              mode="currency" 
              currency="USD" 
              placeholder="0.00" 
              class="w-full"
            />
            <small class="text-gray-400 text-xs sm:text-sm">Orders above this amount get free shipping</small>
          </div>

          <!-- Default Shipping Rate -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Default Shipping Rate</label>
            <InputNumber 
              v-model="settings.default_rate" 
              mode="currency" 
              currency="USD" 
              placeholder="0.00" 
              class="w-full"
            />
          </div>

          <!-- Shipping Zones -->
          <div class="sm:col-span-2">
            <label class="block text-sm font-medium text-gray-700 mb-1">Shipping Zones</label>
            <div class="space-y-3">
              <div v-for="(zone, index) in settings.zones" :key="index" class="flex flex-col sm:flex-row gap-3 items-start">
                <div class="flex-1 grid grid-cols-1 sm:grid-cols-2 gap-3 w-full">
                  <InputText 
                    v-model="zone.name" 
                    placeholder="Zone name (e.g., Local, National, International)"
                    class="w-full"
                  />
                  <InputNumber 
                    v-model="zone.rate" 
                    mode="currency" 
                    currency="USD" 
                    placeholder="Rate"
                    class="w-full"
                  />
                </div>
                <Button 
                  icon="pi pi-times" 
                  severity="danger" 
                  text 
                  class="self-end sm:self-center"
                  @click="removeZone(index)"
                />
              </div>
            </div>
            <Button 
              label="Add Zone" 
              icon="pi pi-plus" 
              text 
              class="mt-2 text-sm sm:text-base"
              @click="addZone"
            />
          </div>

          <!-- Estimated Delivery Days -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Estimated Delivery Days (Min)</label>
            <InputNumber v-model="settings.delivery_min_days" placeholder="Min days" class="w-full" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Estimated Delivery Days (Max)</label>
            <InputNumber v-model="settings.delivery_max_days" placeholder="Max days" class="w-full" />
          </div>
        </div>

        <div class="flex flex-col sm:flex-row gap-3 sm:gap-4 pt-4 border-t border-gray-200">
          <Button 
            type="submit" 
            label="Save Settings" 
            icon="pi pi-save" 
            :loading="saving"
            class="w-full sm:w-auto bg-[#00685F] border-[#00685F] hover:bg-[#004F45] text-sm sm:text-base"
          />
          <Button 
            label="Reset" 
            severity="secondary" 
            class="w-full sm:w-auto text-sm sm:text-base"
            @click="resetSettings"
          />
        </div>

        <div v-if="successMessage" class="flex items-center gap-2 text-green-600 bg-green-50 p-3 rounded-lg">
          <i class="pi pi-check-circle"></i>
          <span class="text-sm sm:text-base">{{ successMessage }}</span>
        </div>

        <div v-if="error.shipping" class="flex items-center gap-2 text-red-600 bg-red-50 p-3 rounded-lg">
          <i class="pi pi-exclamation-circle"></i>
          <span class="text-sm sm:text-base">{{ error.shipping }}</span>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useToast } from 'primevue/usetoast'
import { useVendorProfileStore } from '@/stores/DashboardStore/Vendor/dashboard.vendor.profile.store'
import InputText from 'primevue/inputtext'
import InputNumber from 'primevue/inputnumber'
import Button from 'primevue/button'

const toast = useToast()
const vendorProfileStore = useVendorProfileStore()

const saving = ref(false)
const successMessage = ref('')

const loading = computed(() => vendorProfileStore.loading)
const error = computed(() => vendorProfileStore.error)

const settings = ref({
  free_shipping_threshold: null,
  default_rate: null,
  zones: [] as { name: string; rate: number | null }[],
  delivery_min_days: null,
  delivery_max_days: null
})

const addZone = () => {
  settings.value.zones.push({ name: '', rate: null })
}

const removeZone = (index: number) => {
  settings.value.zones.splice(index, 1)
}

const resetSettings = () => {
  fetchSettings()
  successMessage.value = ''
}

const fetchSettings = async () => {
  try {
    await vendorProfileStore.fetchShipping()
    const data = vendorProfileStore.shipping
    if (data) {
      settings.value = {
        free_shipping_threshold: data.free_shipping_threshold ?? null,
        default_rate: data.default_shipping_rate ?? null,
        zones: data.shipping_zones || [],
        delivery_min_days: data.delivery_min_days ?? null,
        delivery_max_days: data.delivery_max_days ?? null
      }
    }
  } catch (error) {
    console.log('No shipping settings found, using defaults')
  }
}

const saveShipping = async () => {
  saving.value = true
  successMessage.value = ''

  try {
    await vendorProfileStore.updateShipping({
      free_shipping_threshold: settings.value.free_shipping_threshold,
      default_shipping_rate: settings.value.default_rate,
      shipping_zones: settings.value.zones,
      delivery_min_days: settings.value.delivery_min_days,
      delivery_max_days: settings.value.delivery_max_days
    })

    successMessage.value = 'Shipping settings saved successfully!'
    toast.add({
      severity: 'success',
      summary: 'Success',
      detail: 'Shipping settings saved',
      life: 3000
    })
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to save shipping settings',
      life: 3000
    })
  } finally {
    saving.value = false
  }
}

onMounted(() => {
  fetchSettings()
})
</script>

<style scoped>
/* ✅ Responsive fixes */
@media (max-width: 640px) {
  :deep(.p-inputnumber .p-inputnumber-input) {
    font-size: 0.875rem !important;
    padding: 0.5rem !important;
  }
  
  :deep(.p-inputtext) {
    font-size: 0.875rem !important;
    padding: 0.5rem !important;
  }
  
  :deep(.p-button .p-button-label) {
    font-size: 0.875rem !important;
  }
}

@media (max-width: 480px) {
  :deep(.p-inputnumber .p-inputnumber-input) {
    font-size: 0.8rem !important;
    padding: 0.4rem !important;
  }
  
  :deep(.p-inputtext) {
    font-size: 0.8rem !important;
    padding: 0.4rem !important;
  }
}
</style>
