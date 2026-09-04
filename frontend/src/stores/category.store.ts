import { defineStore } from 'pinia';
import api from '@/api/api';
import type { Category } from '@/types';

interface CategoryState {
  categories: Category[];
  loading: boolean;
  error: string | null;
}

export const useCategoryStore = defineStore('category', {
  state: (): CategoryState => ({
    categories: [],
    loading: false,
    error: null
  }),
  
  getters: {
    parentCategories: (state) => state.categories.filter(cat => !cat.parent_uuid),
    hasCategories: (state) => state.categories.length > 0
  },
  
  actions: {
    async fetchCategories() {
      this.loading = true;
      this.error = null;
      
      try {
        const response = await api.get('/categories');
        this.categories = response.data.data;
      } catch (err: any) {
        this.error = err.response?.data?.message || 'Failed to load categories';
        console.error('Error fetching categories:', err);
      } finally {
        this.loading = false;
      }
    }
  }
});