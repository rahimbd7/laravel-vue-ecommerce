import { defineStore } from 'pinia';
import api from '@/api/api';
import { useAuthStore } from './auth.store';
import type { 
  Cart, 
  CartItem, 
  CartSummary, 
  AddToCartRequest 
} from '../types';

interface AppliedCoupon {
  code: string;
  id?: number;
  discount: number;
  applied_at: string;
}

interface CartState {
  id: number | null;
  uuid: string | null;
  items: CartItem[];
  itemCount: number;
  couponCode: string | null;
  couponDiscount: number;
  appliedCoupons: AppliedCoupon[];
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
    couponCode: null,
    couponDiscount: 0,
    appliedCoupons: [],
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
    isEmpty: (state): boolean => state.itemCount === 0,
    
    totalDiscount: (state): number => {
      if (state.appliedCoupons && state.appliedCoupons.length > 0) {
        return state.appliedCoupons.reduce((sum, c) => sum + (c.discount || 0), 0);
      }
      return state.couponDiscount || 0;
    },
    
    couponCount: (state): number => state.appliedCoupons?.length || 0,
    
    isCouponLimitReached: (state): boolean => (state.appliedCoupons?.length || 0) >= 3,
    
    lastCouponCode: (state): string | null => {
      if (state.appliedCoupons && state.appliedCoupons.length > 0) {
        const last = state.appliedCoupons[state.appliedCoupons.length - 1];
        return last?.code || null;
      }
      return state.couponCode || null;
    }
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
        this.itemCount = Number(cart.item_count) || 0;
        this.subtotal = Number(cart.subtotal) || 0;
        this.taxTotal = Number(cart.tax_total) || 0;
        this.shippingTotal = Number(cart.shipping_total) || 0;
        this.discountTotal = Number(cart.discount_total) || 0;
        this.grandTotal = Number(cart.grand_total) || 0;
        
        if (cart.applied_coupons && cart.applied_coupons.length > 0) {
          this.appliedCoupons = cart.applied_coupons;
          const total = this.appliedCoupons.reduce((sum, c) => sum + (c.discount || 0), 0);
          this.couponDiscount = total;
          this.discountTotal = total;
          
          const last = this.appliedCoupons[this.appliedCoupons.length - 1];
          this.couponCode = last?.code || null;
        } else if (cart.coupon_code) {
          // Fallback: single coupon
          this.couponCode = cart.coupon_code;
          this.couponDiscount = Number(cart.coupon_discount) || 0;
          this.appliedCoupons = [{
            code: cart.coupon_code,
            discount: Number(cart.coupon_discount) || 0,
            applied_at: new Date().toISOString()
          }];
        } else {
          // No coupons
          this.appliedCoupons = [];
          this.couponCode = null;
          this.couponDiscount = 0;
        }
        
        return cart;
      } finally {
        this.loading = false;
      }
    },
    
    async fetchAppliedCoupons(): Promise<void> {
      try {
        const response = await api.get('/coupons/applied');
        if (response.data.status === 'success') {
          this.appliedCoupons = response.data.data || [];
          
          // Update coupon discount
          const total = this.appliedCoupons.reduce((sum, c) => sum + (c.discount || 0), 0);
          this.couponDiscount = total;
          this.discountTotal = total;
          
          if (this.appliedCoupons.length > 0) {
            const last = this.appliedCoupons[this.appliedCoupons.length - 1];
            this.couponCode = last?.code || null;
          } else {
            this.couponCode = null;
          }
        }
      } catch (error) {
        console.error('Failed to fetch applied coupons:', error);
      }
    },
    
    async applyCoupon(code: string): Promise<{ success: boolean; error?: string; data?: any }> {
      this.loading = true;
      try {
        const response = await api.post('/coupons/apply', { code });
        
        if (response.data.status === 'success') {
          const data = response.data.data;
          
          // Update applied coupons
          this.appliedCoupons = data.applied_coupons || [];
          
          const total = this.appliedCoupons.reduce((sum, c) => sum + (c.discount || 0), 0);
          this.couponDiscount = total;
          this.discountTotal = total;
          
          if (this.appliedCoupons.length > 0) {
            const last = this.appliedCoupons[this.appliedCoupons.length - 1];
            this.couponCode = last?.code || null;
          } else {
            this.couponCode = null;
          }
          
          // Update grand total
          this.grandTotal = this.subtotal - total;
          
          return { success: true, data: data };
        }
        return { success: false, error: response.data.message };
      } catch (error: any) {
        return { success: false, error: error.response?.data?.message };
      } finally {
        this.loading = false;
      }
    },
    
    async removeCoupon(code: string): Promise<{ success: boolean; error?: string }> {
      this.loading = true;
      try {
        const response = await api.delete('/coupons/remove', { 
          data: { code: code }
        });
        
        if (response.data.status === 'success') {
          // Refetch applied coupons
          await this.fetchAppliedCoupons();
          return { success: true };
        }
        return { success: false, error: response.data.message };
      } catch (error: any) {
        return { success: false, error: error.response?.data?.message };
      } finally {
        this.loading = false;
      }
    },
    
    async clearCoupons(): Promise<{ success: boolean; error?: string }> {
      this.loading = true;
      try {
        const response = await api.delete('/coupons/clear');
        
        if (response.data.status === 'success') {
          this.appliedCoupons = [];
          this.couponCode = null;
          this.couponDiscount = 0;
          this.discountTotal = 0;
          this.grandTotal = this.subtotal;
          return { success: true };
        }
        return { success: false, error: response.data.message };
      } catch (error: any) {
        return { success: false, error: error.response?.data?.message };
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