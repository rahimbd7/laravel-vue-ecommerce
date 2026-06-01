// src/api/api.ts
import axios from "axios";
import { useAuthStore } from "@/stores/auth.store";
import { useCartStore } from "@/stores/cart.store";
import router from "@/router";

const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL || "http://localhost:8000/api",
  withCredentials: false,  // ✅ Important: false for token-based auth
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  }
});

// Request Interceptor - Add token
api.interceptors.request.use(
  (config) => {
    const auth = useAuthStore();
    const cart = useCartStore();

    if(config.url?.includes('/login') || config.url?.includes('/register')) {
      // Don't add token for auth routes
      return config;
    }
    
    // ✅ Add Bearer token for authenticated requests
    if (auth.token) {
      config.headers.Authorization = `Bearer ${auth.token}`;
    }
    
    // ✅ Add guest token for cart (only when not logged in)
    if (!auth.token && cart.guestToken) {
      config.headers['X-Guest-Token'] = cart.guestToken;
    }
    
    return config;
  },
  (error) => Promise.reject(error)
);

// Response Interceptor
api.interceptors.response.use(
  (response) => response,
  async (error) => {
    const auth = useAuthStore();
    const cart = useCartStore();

    if (error.response) {
      const status = error.response.status;

      switch (status) {
        case 401:
        case 419:
          auth.cleanState();
          cart.clearGuestToken();
          if (router.currentRoute.value.path !== "/login") {
            await router.push("/login");
          }
          break;
        case 403:
          console.error("Permission denied");
          break;
        case 404:
          console.error("Resource not found");
          break;
        case 422:
          // Validation error - let component handle
          break;
        case 500:
          console.error("Server error");
          break;
      }
    } else if (error.code === "ERR_NETWORK") {
      console.error("Network error - server might be down");
    }
    
    return Promise.reject(error);
  }
);

export default api;