import { ref } from 'vue'
import { CLOUDINARY_CONFIG } from '@/config/cloudinary.config'

export interface CloudinaryUploadResult {
  public_id: string
  secure_url: string
  format: string
  width: number
  height: number
  bytes: number
  created_at: string
}

export interface UploadOptions {
  folder?: string
  publicId?: string
  tags?: string[]
  context?: Record<string, any>
}

export function useCloudinaryUpload() {
  const uploading = ref(false)
  const progress = ref(0)
  const error = ref<string | null>(null)

  /**
   * Upload image to Cloudinary using Upload Preset
   */
  const uploadImage = async (
    file: File,
    configType: keyof typeof CLOUDINARY_CONFIG = 'products',
    options: UploadOptions = {}
  ): Promise<CloudinaryUploadResult> => {
    uploading.value = true
    progress.value = 0
    error.value = null

    const config = CLOUDINARY_CONFIG[configType]
    const formData = new FormData()
    
    formData.append('file', file)
    formData.append('upload_preset', config.uploadPreset)
    formData.append('cloud_name', config.cloudName)
    
    if (options.folder) {
      formData.append('folder', options.folder)
    } else {
      formData.append('folder', config.folder)
    }
    
    if (options.publicId) {
      formData.append('public_id', options.publicId)
    }
    
    if (options.tags) {
      formData.append('tags', options.tags.join(','))
    }
    
    if (options.context) {
      formData.append('context', JSON.stringify(options.context))
    }

    // Use Cloudinary's unsigned upload URL
    const url = `https://api.cloudinary.com/v1_1/${config.cloudName}/image/upload`

    try {
      const response = await fetch(url, {
        method: 'POST',
        body: formData,
      })

      if (!response.ok) {
        const errorData = await response.json()
        throw new Error(errorData.error?.message || 'Upload failed')
      }

      const result = await response.json()
      progress.value = 100
      
      return {
        public_id: result.public_id,
        secure_url: result.secure_url,
        format: result.format,
        width: result.width,
        height: result.height,
        bytes: result.bytes,
        created_at: result.created_at,
      }
    } catch (err: any) {
      error.value = err.message || 'Upload failed'
      throw err
    } finally {
      uploading.value = false
    }
  }

  /**
   * Generate Cloudinary URL with transformations
   */
  const getCloudinaryUrl = (
    publicId: string,
    configType: keyof typeof CLOUDINARY_CONFIG = 'products',
    size: 'thumbnail' | 'medium' | 'large' = 'medium'
  ): string => {
    const config = CLOUDINARY_CONFIG[configType]
    const transform = config.transformations[size as keyof typeof config.transformations]
    
    if (!transform) {
      return `https://res.cloudinary.com/${config.cloudName}/image/upload/${publicId}`
    }
    
    const { width, height, crop } = transform
    return `https://res.cloudinary.com/${config.cloudName}/image/upload/c_${crop},w_${width},h_${height}/${publicId}`
  }

  /**
   * Generate multiple image sizes for product
   */
  const getProductImageUrls = (publicId: string): {
    original: string
    thumbnail: string
    medium: string
    large: string
  } => {
    const config = CLOUDINARY_CONFIG.products
    const baseUrl = `https://res.cloudinary.com/${config.cloudName}/image/upload`
    
    return {
      original: `${baseUrl}/${publicId}`,
      thumbnail: `${baseUrl}/c_fill,w_150,h_150/${publicId}`,
      medium: `${baseUrl}/c_fill,w_400,h_400/${publicId}`,
      large: `${baseUrl}/c_fill,w_800,h_800/${publicId}`,
    }
  }

  /**
   * Validate file before upload
   */
  const validateFile = (file: File): { valid: boolean; message?: string } => {
    const maxSize = 5 * 1024 * 1024 // 5MB
    const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml']
    
    if (!allowedTypes.includes(file.type)) {
      return { 
        valid: false, 
        message: 'Please upload a valid image (JPEG, PNG, GIF, WEBP, or SVG)' 
      }
    }
    
    if (file.size > maxSize) {
      return { 
        valid: false, 
        message: 'Image size should be less than 5MB' 
      }
    }
    
    return { valid: true }
  }

  return {
    uploading,
    progress,
    error,
    uploadImage,
    getCloudinaryUrl,
    getProductImageUrls,
    validateFile,
  }
}
