// Cloudinary configuration for different modules
export const CLOUDINARY_CONFIG = {
  // Product images
  products: {
    cloudName: import.meta.env.VITE_CLOUDINARY_CLOUD_NAME || 'your-cloud-name',
    uploadPreset: import.meta.env.VITE_CLOUDINARY_PRODUCT_PRESET || 'product_preset',
    folder: 'products',
    transformations: {
      thumbnail: { width: 150, height: 150, crop: 'fill' },
      medium: { width: 400, height: 400, crop: 'fill' },
      large: { width: 800, height: 800, crop: 'fill' },
    }
  },
  
  // Category images
  categories: {
    cloudName: import.meta.env.VITE_CLOUDINARY_CLOUD_NAME || 'your-cloud-name',
    uploadPreset: import.meta.env.VITE_CLOUDINARY_CATEGORY_PRESET || 'category_preset',
    folder: 'categories',
    transformations: {
      thumbnail: { width: 100, height: 100, crop: 'fill' },
      medium: { width: 300, height: 300, crop: 'fill' },
    }
  },
  
  // Profile pictures
  profiles: {
    cloudName: import.meta.env.VITE_CLOUDINARY_CLOUD_NAME || 'your-cloud-name',
    uploadPreset: import.meta.env.VITE_CLOUDINARY_PROFILE_PRESET || 'profile_preset',
    folder: 'profiles',
    transformations: {
      thumbnail: { width: 80, height: 80, crop: 'fill' },
      medium: { width: 200, height: 200, crop: 'fill' },
    }
  }
}

// Default cloud instance
export const DEFAULT_CLOUD_NAME = CLOUDINARY_CONFIG.products.cloudName