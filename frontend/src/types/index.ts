// Common types
export type { ApiResponse, PaginatedResponse, Address, Metadata } from './common.types';

// User types
export type { 
  User, 
  UserProfile, 
  UserWithProfile,
  LoginRequest, 
  LoginResponse, 
  RegisterRequest 
} from './models/user.types';

// Cart types
export type {
  CartItem,
  Cart,
  CartSummary,
  AddToCartRequest,
  UpdateCartItemRequest,
  SyncCartRequest
} from './models/cart.types';

// Product types
export type {
  Product,
  ProductVariation,
  ProductImage
} from './models/product.types';

// src/types/index.ts
export interface Category {
  id: number;
  uuid?: string;
  name: string;
  slug: string;
  description: string | null;
  image: string | null;
  icon: string | null;
  parent_uuid: string | null;
  position: number;
  is_active: boolean;
  is_featured: boolean;
  children?: Category[];
}