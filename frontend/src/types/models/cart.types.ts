import type { Address } from '../common.types';
import type { Product, ProductVariation } from './product.types';

// Cart Item
export interface CartItem {
  id: number;
  uuid: string;
  cart_id: number;
  product_id: number;
  product_name: string;
  product_sku: string;
  product_variation_id: number | null;
  product_variation_name: string | null;
  product_attributes: Record<string, any> | null;
  quantity: number;
  unit_price: number;
  subtotal: number;
  discount: number;
  tax: number;
  total: number;
  is_saved_for_later: boolean;
  created_at: string;
  updated_at: string;
  product?: Product;
  variation?: ProductVariation;
}

// Cart
export interface Cart {
  [x: string]: any;
  id: number;
  uuid: string;
  user_id: number | null;
  session_id: string | null;
  guest_token: string | null;
  item_count: number;
  subtotal: number;
  discount_total: number;
  tax_total: number;
  shipping_total: number;
  grand_total: number;
  coupon_code: string | null;
  coupon_discount: number;
  shipping_method: string | null;
  shipping_address: Address | null;
  status: 'active' | 'abandoned' | 'converted';
  last_activity_at: string;
  expires_at: string;
  created_at: string;
  updated_at: string;
  items: CartItem[];
}

// Cart Summary
export interface CartSummary {
  item_count: number;
  subtotal: number;
  tax_total: number;
  shipping_total: number;
  discount_total: number;
  grand_total: number;
}

// Add to Cart
export interface AddToCartRequest {
  product_id: number;
  product_variation_id?: number | null;
  quantity: number;
}

export interface UpdateCartItemRequest {
  quantity: number;
}

// Cart Sync
export interface SyncCartRequest {
  guest_token: string;
}
