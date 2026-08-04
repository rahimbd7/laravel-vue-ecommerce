<template>
  <div class="cloudinary-upload">
    <!-- Upload Area -->
    <div 
      class="upload-area"
      :class="{ 
        'dragover': isDragover, 
        'uploading': uploading,
        'has-error': error 
      }"
      @dragover.prevent="isDragover = true"
      @dragleave.prevent="isDragover = false"
      @drop.prevent="handleDrop"
    >
      <input
        ref="fileInput"
        type="file"
        accept="image/*"
        multiple
        @change="handleFileSelect"
        class="hidden"
      />
      
      <div v-if="!uploading && !uploadedFiles.length" class="upload-placeholder">
        <i class="pi pi-cloud-upload text-4xl text-gray-400"></i>
        <p class="text-gray-600 mt-2">Drag & drop images here or click to browse</p>
        <p class="text-gray-400 text-sm mt-1">Supports: JPEG, PNG, GIF, WEBP (Max 5MB)</p>
      </div>
      
      <!-- Upload Progress -->
      <div v-if="uploading" class="upload-progress">
        <div class="flex items-center gap-3">
          <i class="pi pi-spin pi-spinner text-2xl text-[#00685F]"></i>
          <span class="text-gray-600">Uploading... {{ progress }}%</span>
        </div>
        <div class="w-full bg-gray-200 rounded-full h-2 mt-2">
          <div 
            class="bg-[#00685F] h-2 rounded-full transition-all duration-300"
            :style="{ width: progress + '%' }"
          ></div>
        </div>
      </div>
      
      <!-- Uploaded Images Preview -->
      <div v-if="uploadedFiles.length > 0" class="uploaded-preview grid grid-cols-4 gap-2">
        <div 
          v-for="(file, index) in uploadedFiles" 
          :key="file.public_id || index"
          class="relative group"
        >
          <img 
            :src="file.secure_url || file.thumbnail || file.preview" 
            alt="Uploaded image"
            class="w-full h-24 object-cover rounded-lg"
          />
          <button
            @click="removeFile(index)"
            class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity"
          >
            <i class="pi pi-times text-xs"></i>
          </button>
          <div v-if="file.is_primary" class="absolute bottom-1 left-1">
            <span class="bg-[#00685F] text-white text-xs px-2 py-0.5 rounded">Primary</span>
          </div>
        </div>
        
        <!-- Add More Button -->
        <div v-if="!uploading && uploadedFiles.length < (maxFiles !== undefined ? maxFiles : 0)">
  <!-- Add More Button -->
  <div 
    @click="openFileDialog"
    class="border-2 border-dashed border-gray-300 rounded-lg flex items-center justify-center h-24 cursor-pointer hover:border-[#00685F] transition-colors"
  >
    <i class="pi pi-plus text-2xl text-gray-400"></i>
  </div>
</div>
    </div>
    
    <!-- Error Message -->
    <div v-if="error" class="mt-2 text-red-500 text-sm">
      <i class="pi pi-exclamation-circle mr-1"></i>
      {{ error }}
    </div>
    
    <!-- Image Count -->
   <div v-if="uploadedFiles.length > 0 && maxFiles !== undefined" class="mt-2 text-sm text-gray-500">
  {{ uploadedFiles.length }} image(s) uploaded
  <span v-if="maxFiles > 0">(Max: {{ maxFiles }})</span>
</div>
  </div>
  </div>
</template>

<script setup lang="ts">
import { ref, watch, onUnmounted } from 'vue'
import { useCloudinaryUpload } from '@/composables/useCloudinaryUpload'

interface UploadedFile {
  public_id?: string
  secure_url?: string
  thumbnail?: string
  medium?: string
  large?: string
  preview?: string
  is_primary: boolean
  file?: File
}

const props = defineProps<{
  modelValue: UploadedFile[]
  configType?: 'products' | 'categories' | 'profiles'
  maxFiles?: number
  multiple?: boolean
  autoUpload?: boolean
}>()

const emit = defineEmits<{
  (e: 'update:modelValue', value: UploadedFile[]): void
  (e: 'upload-success', file: UploadedFile): void
  (e: 'upload-error', error: string): void
}>()

const { uploadImage, uploading, progress, error, validateFile } = useCloudinaryUpload()

const fileInput = ref<HTMLInputElement>()
const isDragover = ref(false)
const uploadedFiles = ref<UploadedFile[]>([...props.modelValue])

// Track progress for each file
const uploadProgress = ref<Record<string, number>>({})

const openFileDialog = () => {
  fileInput.value?.click()
}

const handleFileSelect = async (event: Event) => {
  const input = event.target as HTMLInputElement
  if (!input.files?.length) return
  
  const files = Array.from(input.files)
  await processFiles(files)
  input.value = ''
}

const handleDrop = async (event: DragEvent) => {
  isDragover.value = false
  const files = event.dataTransfer?.files
  if (!files?.length) return
  
  await processFiles(Array.from(files))
}

const processFiles = async (files: File[]) => {
  // Check max files limit
  if (props.maxFiles && uploadedFiles.value.length + files.length > props.maxFiles) {
    error.value = `You can upload maximum ${props.maxFiles} images`
    return
  }
  
  // Validate each file
  for (const file of files) {
    const validation = validateFile(file)
    if (!validation.valid) {
      error.value = validation.message as string
      return
    }
  }
  
  if (props.autoUpload) {
    // Auto upload each file
    for (const file of files) {
      await uploadFile(file)
    }
  } else {
    // Just preview without uploading
    const newFiles: UploadedFile[] = files.map((file, index) => ({
      preview: URL.createObjectURL(file),
      is_primary: uploadedFiles.value.length === 0 && index === 0,
      file: file,
    }))
    
    uploadedFiles.value = [...uploadedFiles.value, ...newFiles]
    emit('update:modelValue', uploadedFiles.value)
  }
}

const uploadFile = async (file: File): Promise<UploadedFile | null> => {
  try {
    const result = await uploadImage(file, props.configType || 'products')
    
    const uploadedFile: UploadedFile = {
      public_id: result.public_id,
      secure_url: result.secure_url,
      thumbnail: result.secure_url,
      medium: result.secure_url,
      large: result.secure_url,
      is_primary: uploadedFiles.value.length === 0,
    }
    
    uploadedFiles.value = [...uploadedFiles.value, uploadedFile]
    emit('update:modelValue', uploadedFiles.value)
    emit('upload-success', uploadedFile)
    
    return uploadedFile
  } catch (err: any) {
    error.value = err.message
    emit('upload-error', err.message)
    return null
  }
}

const removeFile = (index: number) => {
  const file = uploadedFiles.value[index]
  
  // Revoke object URL for preview
  if (file && file.preview) {
    URL.revokeObjectURL(file.preview)
  }
  
  uploadedFiles.value.splice(index, 1)
  
  // If primary image was removed, set first image as primary
  const firstFile = uploadedFiles.value[0] as UploadedFile
  if (file && file.is_primary && uploadedFiles.value.length > 0) {
    firstFile.is_primary = true as boolean
  }
  
  emit('update:modelValue', uploadedFiles.value)
}

const setPrimary = (index: number) => {
  uploadedFiles.value.forEach((file, i) => {
    file.is_primary = i === index
  })
  emit('update:modelValue', uploadedFiles.value)
}

// Watch for external changes to modelValue
watch(() => props.modelValue, (newVal) => {
  if (JSON.stringify(newVal) !== JSON.stringify(uploadedFiles.value)) {
    uploadedFiles.value = [...newVal]
  }
}, { deep: true })

// Expose methods to parent
defineExpose({
  uploadedFiles,
  setPrimary,
  removeFile,
  uploadFile,
  openFileDialog,
})

// Cleanup object URLs on component unmount
onUnmounted(() => {
  uploadedFiles.value.forEach(file => {
    if (file.preview) {
      URL.revokeObjectURL(file.preview)
    }
  })
})
</script>

<style scoped>
.upload-area {
  border: 2px dashed #d1d5db;
  border-radius: 0.5rem;
  padding: 2rem;
  transition: all 0.3s ease;
  cursor: pointer;
  min-height: 150px;
}

.upload-area:hover {
  border-color: #00685F;
}

.upload-area.dragover {
  border-color: #00685F;
  background: #f0fdf4;
}

.upload-area.uploading {
  opacity: 0.7;
  cursor: default;
}

.upload-area.has-error {
  border-color: #ef4444;
}

.upload-placeholder {
  text-align: center;
}

.uploaded-preview {
  width: 100%;
}

.uploaded-preview .relative:hover img {
  filter: brightness(0.9);
}
</style>