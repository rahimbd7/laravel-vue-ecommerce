import type { Product } from '@/types/models/product.types'

const API_URL = import.meta.env.VITE_API_URL || 'http://localhost:8000/api'

export interface PaginatedResponse<T> {
  data: T[]
  pagination: {
    total: number
    per_page: number
    current_page: number
    total_pages: number
  }
}

export const productApi = {
  /**
   * Get all products - simple endpoint without parameters
   */
  getAll: async (page: number = 1, perPage: number = 15): Promise<PaginatedResponse<Product>> => {
    const url = page === 1 && perPage === 15 
      ? `${API_URL}/v1/products/`
      : `${API_URL}/v1/products/?page=${page}&per_page=${perPage}`
    
    const response = await fetch(url)

    if (!response.ok) {
      throw new Error(`Failed to fetch products: ${response.statusText}`)
    }

    const result = await response.json()
    
    return {
      data: result.data.data || result.data,
      pagination: {
        total: result.data.pagination.total,
        per_page: result.data.pagination.per_page,
        current_page: result.data.pagination.current_page,
        total_pages: result.data.pagination.total_pages
      }
    }
  },

  /**
   * Search/Filter products with filters
   */
  search: async (filters: any = {}, perPage: number = 15): Promise<PaginatedResponse<Product>> => {
    const params = new URLSearchParams()

    if (filters.search) params.append('search', filters.search)
    if (filters.min_price) params.append('min_price', filters.min_price)
    if (filters.max_price) params.append('max_price', filters.max_price)
    if (filters.in_stock) params.append('in_stock', '1')
    if (filters.featured) params.append('featured', '1')
    if (filters.category_id) params.append('category_id', filters.category_id) 
    if (filters.vendor_id) params.append('vendor_id', filters.vendor_id)
    if (filters.sort_by) params.append('sort_by', filters.sort_by)
    if (filters.sort_order) params.append('sort_order', filters.sort_order)
    if (filters.page) params.append('page', filters.page)
    if (filters.has_variations) params.append('has_variations', '1')

    params.append('per_page', perPage.toString())

    const url = `${API_URL}/v1/products?${params.toString()}`
    console.log('Searching products with URL:', url)
    const response = await fetch(url)

    if (!response.ok) {
      throw new Error(`Failed to search products: ${response.statusText}`)
    }

    const result = await response.json()
    
    return {
      data: result.data.data || result.data,
      pagination: {
         total: result.data.pagination.total,
        per_page: result.data.pagination.per_page,
        current_page: result.data.pagination.current_page,
        total_pages: result.data.pagination.total_pages
      }
    }
  },

  /**
   * Get single product by slug
   */
  getBySlug: async (slug: string): Promise<Product> => {
    const response = await fetch(`${API_URL}/v1/products/slug/${slug}`)

    if (!response.ok) {
      throw new Error(`Failed to fetch product: ${response.statusText}`)
    }

    const result = await response.json()
    return result.data
  },

  /**
   * Get related products
   */
  getRelated: async (productId: number, limit: number = 10): Promise<Product[]> => {
    const response = await fetch(`${API_URL}/v1/products/${productId}/related?limit=${limit}`)

    if (!response.ok) {
      throw new Error(`Failed to fetch related products: ${response.statusText}`)
    }

    const result = await response.json()
    return result.data
  },

  /**
   * Get featured products
   */
  getFeatured: async (limit: number = 12): Promise<Product[]> => {
    const response = await fetch(`${API_URL}/v1/products?featured=1&per_page=${limit}`)

    if (!response.ok) {
      throw new Error(`Failed to fetch featured products: ${response.statusText}`)
    }

    const result = await response.json()
    return result.data.data || result.data
  },
}