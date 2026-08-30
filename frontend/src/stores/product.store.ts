import { defineStore } from "pinia";
import api from "@/api/api";
import type { Product } from "@/types/models/product.types";

interface ProductState {
  products: Product[];
  featuredProducts: Product[];
  currentProduct: Product | null;
  loading: boolean;
  pagination: {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
  };
}

export const useProductStore = defineStore("product", {
  state: (): ProductState => ({
    products: [],
    featuredProducts: [],
    currentProduct: null,
    loading: false,
    pagination: {
      current_page: 1,
      last_page: 1,
      per_page: 12,
      total: 0,
    },
  }),

  getters: {
    // Get featured products
    featuredList: (state): Product[] => state.featuredProducts,

    // Get products on sale - use price.compare.value
    saleProducts: (state): Product[] =>
      state.products.filter(
        (p) =>
          p.price.compare !== null &&
          Number(p.price.compare.value) > Number(p.price.original),
      ),

    // Get new arrivals (last 30 days from created_at)
    newArrivals: (state): Product[] =>
      state.products.filter((p) => {
        const daysSinceCreated =
          (Date.now() - new Date(p.created_at).getTime()) /
          (1000 * 60 * 60 * 24);
        return daysSinceCreated <= 30;
      }),

    // Get best sellers - use stats.sold_count
    bestSellers: (state): Product[] =>
      [...state.products]
        .sort((a, b) => b.stats.sold_count - a.stats.sold_count)
        .slice(0, 8),

    // Get in-stock products - use inventory.status
    inStockProducts: (state): Product[] =>
      state.products.filter(
        (p) =>
          p.inventory.status === "in_stock" ||
          p.inventory.status === "low_stock",
      ),

    isLoading: (state): boolean => state.loading,

    // Lookup helpers
    getProductBySlug:
      (state) =>
      (slug: string): Product | undefined =>
        state.products.find((p) => p.slug === slug),

    getProductById:
      (state) =>
      (id: number): Product | undefined =>
        state.products.find((p) => p.id === id),

    getProductsByCategory:
      (state) =>
      (categoryId: string): Product[] =>
        state.products.filter((p) => p.category.id === categoryId),
  },

  actions: {
    // Fetch featured products for home page
    async fetchFeaturedProducts(limit: number = 8): Promise<Product[]> {
      this.loading = true;
      try {
        const response = await api.get("/v1/products", {
          params: { featured: true, limit, is_visible: true },
        });
        const data = response.data.data;
        this.featuredProducts = (data.data || data).slice(0, limit);
        return this.featuredProducts;
      } catch (error) {
        console.error("Error fetching featured products:", error);
        throw error;
      } finally {
        this.loading = false;
      }
    },

    // Fetch all products with pagination
    async fetchProducts(
      params: {
        page?: number;
        limit?: number;
        category?: string;
        search?: string;
        sort?: string;
      } = {},
    ): Promise<{ products: Product[]; pagination: any }> {
      this.loading = true;
      try {
        const response = await api.get("/v1/products", { params });

        const responseData = response.data.data;
        const paginationData = responseData;

        this.products = responseData.data || responseData;
        this.pagination = {
          current_page: paginationData.current_page || 1,
          last_page: paginationData.last_page || 1,
          per_page: paginationData.per_page || 15,
          total: paginationData.total || 0,
        };
        return { products: this.products, pagination: this.pagination };
      } catch (error) {
        console.error("Error fetching products:", error);
        throw error;
      } finally {
        this.loading = false;
      }
    },

    // Fetch single product by slug
    async fetchProductBySlug(slug: string): Promise<Product | null> {
      this.loading = true;
      try {
        const response = await api.get(`/v1/products/${slug}`);
        this.currentProduct = response.data.data;
        return this.currentProduct;
      } catch (error) {
        console.error("Error fetching product:", error);
        throw error;
      } finally {
        this.loading = false;
      }
    },

    // Fetch single product by ID
    async fetchProductById(id: number): Promise<Product | null> {
      this.loading = true;
      try {
        const response = await api.get(`/v1/products/${id}`);
        this.currentProduct = response.data.data;
        return this.currentProduct;
      } catch (error) {
        console.error("Error fetching product:", error);
        throw error;
      } finally {
        this.loading = false;
      }
    },

    // Clear current product
    clearCurrentProduct(): void {
      this.currentProduct = null;
    },

    // Reset products state
    resetProducts(): void {
      this.products = [];
      this.featuredProducts = [];
      this.currentProduct = null;
      this.pagination = {
        current_page: 1,
        last_page: 1,
        per_page: 12,
        total: 0,
      };
    },
  },
});
