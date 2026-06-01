// src/stores/cart.store.ts
import { defineStore } from 'pinia';
import api from '@/api/api';
import { useAuthStore } from './auth.store';
import type { 
  Cart, 
  CartItem, 
  CartSummary, 
  AddToCartRequest 
} from '../types';

interface CartState {
  id: number | null;
  uuid: string | null;
  items: CartItem[];
  itemCount: number;
  subtotal: number;
  taxTotal: number;
  shippingTotal: number;
  discountTotal: number;
  grandTotal: number;
  guestToken: string | null;
  loading: boolean;
  syncing: boolean;
}

export const useCartStore = defineStore('cart', {
  state: (): CartState => ({
    id: null,
    uuid: null,
    items: [],
    itemCount: 0,
    subtotal: 0,      
    taxTotal: 0,      
    shippingTotal: 0, 
    discountTotal: 0, 
    grandTotal: 0,    
    guestToken: localStorage.getItem('guest_token'),
    loading: false,
    syncing: false
  }),
  
  getters: {
     safeSubtotal: (state) => Number(state.subtotal) || 0,
    safeGrandTotal: (state) => Number(state.grandTotal) || 0,
    safeDiscountTotal: (state) => Number(state.discountTotal) || 0,
    safeTaxTotal: (state) => Number(state.taxTotal) || 0,
    safeItemCount: (state) => Number(state.itemCount) || 0,
    cartItems: (state): CartItem[] => state.items,
    activeItems: (state): CartItem[] => state.items.filter(item => !item.is_saved_for_later),
    savedForLaterItems: (state): CartItem[] => state.items.filter(item => item.is_saved_for_later),
    totalItems: (state): number => state.itemCount,
    totalPrice: (state): number => state.grandTotal,
    isEmpty: (state): boolean => state.itemCount === 0
  },
  
  actions: {
    async fetchCart(): Promise<Cart> {
      this.loading = true;
      try {
        const headers: Record<string, string> = {};
        if (this.guestToken) {
          headers['X-Guest-Token'] = this.guestToken;
        }
        
        const response = await api.get<Cart>('/cart', { headers });
        const cart = response.data.data;
        
        this.id = cart.id;
        this.uuid = cart.uuid;
        this.items = cart.items || [];
        // ✅ Ensure numbers, not strings
        this.itemCount = Number(cart.item_count) || 0;
        this.subtotal = Number(cart.subtotal) || 0;
        this.taxTotal = Number(cart.tax_total) || 0;
        this.shippingTotal = Number(cart.shipping_total) || 0;
        this.discountTotal = Number(cart.discount_total) || 0;
        this.grandTotal = Number(cart.grand_total) || 0;
        
        return cart;
      } finally {
        this.loading = false;
      }
    },
    
    async addItem(productId: number, quantity: number, variationId: number | null = null): Promise<{ success: boolean; error?: string }> {
      this.loading = true;
      try {
        const payload: AddToCartRequest = { 
          product_id: productId, 
          quantity, 
          product_variation_id: variationId 
        };
        
        await api.post('/cart/add', payload);
        await this.fetchCart();
        return { success: true };
      } catch (error: any) {
        return { success: false, error: error.response?.data?.message };
      } finally {
        this.loading = false;
      }
    },

    async updateItem(cartItemId: number, quantity: number): Promise<{ success: boolean; error?: string }> {
      this.loading = true;
      try {
        const payload = { quantity };
        
        await api.put(`/cart/items/${cartItemId}`, payload);
        await this.fetchCart();
        return { success: true };
      } catch (error: any) {
        return { success: false, error: error.response?.data?.message };
      } finally {
        this.loading = false;
      }
    },

    async removeItem(cartItemId: number): Promise<{ success: boolean; error?: string }> {
      this.loading = true;
      try {
        await api.delete(`/cart/items/${cartItemId}`);
        await this.fetchCart();
        return { success: true };
      } catch (error: any) {
        return { success: false, error: error.response?.data?.message };
      } finally {
        this.loading = false;
      }
    },

    async clear(): Promise<{ success: boolean; error?: string }> {
      this.loading = true;
      try {
        await api.post('/cart/clear');
        await this.fetchCart();
        return { success: true };
      } catch (error: any) {
        return { success: false, error: error.response?.data?.message };
      } finally {
        this.loading = false;
      }
    },

    async clearGuestToken() {
      this.guestToken = null;
      localStorage.removeItem('guest_token');
    }
  }
});