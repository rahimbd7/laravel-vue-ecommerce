// types/models/category.types.ts

export interface Category {
  id: number
  uuid: string
  name: string
  slug: string
  description: string | null
  image: string | null
  image_url: string | null
  icon: string | null
  parent_id: number | null
  parent_uuid: string | null
  position: number
  is_active: boolean
  is_featured: boolean
  product_count: number
  meta_title: string | null
  meta_description: string | null
  meta_keywords: string[] | null
  children?: Category[]
  parent?: Category | null
  created_at?: string
  updated_at?: string
  deleted_at?: string | null
}

export interface CategoryFormData {
  name: string
  slug?: string
  description: string | null
  image: string | null | File
  icon: string | null
  parent_id: number | null
  parent_uuid: string | null
  position: number
  is_active: boolean
  is_featured: boolean
  meta_title: string | null
  meta_description: string | null
  meta_keywords: string[] | null
}

export interface CategoryApiResponse {
  status: string
  message: string
  data: Category | Category[]
}

export interface CategoryListResponse {
  status: string
  message: string
  data: Category[]
}