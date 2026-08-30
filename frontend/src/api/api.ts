import axios from "axios";
import { useAuthStore } from "@/stores/auth.store";
import { useCartStore } from "@/stores/cart.store";
import router from "@/router";

const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL || "http://localhost:8000/api",
  withCredentials: false, // token-based auth
  timeout: 20000,
  headers: {
    "Content-Type": "application/json",
    Accept: "application/json",
  },
});

const GUEST_TOLERATED = [/\/wishlist\//, /\/coupons\/applied/, /\/me$/];

const isGuestTolerated = (url = "") => GUEST_TOLERATED.some((re) => re.test(url));

// Request Interceptor - attach credentials
api.interceptors.request.use(
  (config) => {
    const auth = useAuthStore();
    const cart = useCartStore();

    // Never leak a stale Bearer token into a login/register attempt
    if (config.url?.includes("/login") || config.url?.includes("/register")) {
      return config;
    }

    if (auth.token) {
      config.headers.Authorization = `Bearer ${auth.token}`;
    }

    // Guest cart continuity
    if (!auth.token && cart.guestToken) {
      config.headers["X-Guest-Token"] = cart.guestToken;
    }

    return config;
  },
  (error) => Promise.reject(error)
);

/**
 * Response Interceptor - ONE handler.
 *
 * There used to be two `interceptors.response.use()` blocks registered on this
 * same instance, both reacting to 401. They fought each other:
 *   - the first called `auth.cleanState()` + `router.push('/login')` (SPA nav)
 *   - the second called `window.location.href = '/login'` (full page reload)
 * The reload always won, so every expired token wiped the SPA, discarded any
 * unsaved form (including a filled-in checkout) and re-downloaded the bundle.
 * It also double-logged every error to the console.
 */
api.interceptors.response.use(
  (response) => response,
  async (error) => {
    const auth = useAuthStore();
    const cart = useCartStore();
    const url: string = error.config?.url ?? "";
    const status = error.response?.status;

    if (import.meta.env.DEV) {
      console.warn("[api]", status ?? error.code, url, error.response?.data?.message ?? error.message);
    }

    if (status === 401 || status === 419) {
      // Guests are allowed to be unauthenticated on these endpoints.
      if (isGuestTolerated(url) && !auth.token) {
        return Promise.reject(error);
      }

      auth.cleanState();
      cart.clearGuestToken();

      const current = router.currentRoute.value;
      if (current.path !== "/login") {
        // `redirect` lets the login page return the user to what they were
        // doing instead of dumping them on the home page (UX + WCAG 3.2.x
        // predictability). SPA navigation preserves the loaded bundle.
        await router.push({ path: "/login", query: { redirect: current.fullPath } });
      }
    }

    // 403 / 404 / 422 / 5xx are surfaced to the caller, which shows an inline
    // message through useNotify().apiError(). Swallowing them here was why so
    // many failures used to appear as a spinner that never stopped.
    return Promise.reject(error);
  }
);

export default api;
