// types/models/product.types.ts

// Product Price
export interface ProductPrice {
  original: string
  formatted: string
  compare: {
    value: string | null
    formatted: string | null
  } | null
  final: string
  discount: number
  is_on_sale: boolean
}

// Product Inventory
export interface ProductInventory {
  quantity: number
  status: 'in_stock' | 'out_of_stock' | 'low_stock'
  status_label: string
  is_in_stock: boolean
  total_stock: number
}

// Product Media
export interface ProductMedia {
  thumbnail: string
  image: string
}

// Product Stats
export interface ProductStats {
  sold_count: number
  average_rating: string
  review_count: number
}

// Product Flags
export interface ProductFlags {
  is_visible: boolean
  is_featured: boolean
  has_variations: boolean
  is_taxable: boolean
  tax_rate: string
  free_shipping: boolean
}

// Product Category
export interface ProductCategory {
  id: string
  name: string
  slug: string
}

// Product Variation
export interface ProductVariation {
  id: number
  product_id: number
  name: string
  sku: string
  attributes: Record<string, any> | null
  price: number
  compare_price: number | null
  stock_quantity: number
  is_default: boolean
  created_at: string
  updated_at: string
}

// Product Image
export interface ProductImage {
  id: number
  product_id: number
  image: string
  thumbnail: string
  alt_text: string | null
  is_primary: boolean
  position: number
  created_at: string
  updated_at: string
}

// Main Product Interface
export interface Product {
  id: number
  name: string
  slug: string
  description: string
  short_description: string
  sku: string
  price: ProductPrice
  inventory: ProductInventory
  media: ProductMedia
  stats: ProductStats
  flags: ProductFlags
  category: ProductCategory
  variations?: ProductVariation[]
  images?: ProductImage[]
  created_at: string
  updated_at: string
}