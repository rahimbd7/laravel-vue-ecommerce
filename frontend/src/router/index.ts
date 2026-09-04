import { createRouter, createWebHistory } from "vue-router";
import type { RouteRecordRaw } from "vue-router";
import { useAuthStore } from "@/stores/auth.store";

// Route modules
import { publicRoutes } from "./routes/public.route";
import { authRoutes } from "./routes/auth.route";
import { dashboardRoutes } from "./routes/dashboard";

declare module "vue-router" {
  interface RouteMeta {
    requiresAuth?: boolean;
    guest?: boolean;
    roles?: string[];
    title?: string;
    breadcrumb?: string[];
    layout?: "dashboard" | "bare" | "public";
    mode?: "login" | "register";
  }
}

const APP_NAME = "My Shop";

const ROLE_HOME: Record<string, string> = {
  admin: "/dashboard/admin",
  vendor: "/dashboard/vendor",
  customer: "/dashboard/customer",
};

const routes: RouteRecordRaw[] = [
  ...publicRoutes,
  ...authRoutes,
  ...dashboardRoutes,
  {
    path: "/:pathMatch(.*)*",
    name: "not-found",
    component: () => import("@/views/NotFound.vue"),
    meta: { title: "Page Not Found" },
  },
];

const router = createRouter({
  history: createWebHistory(),
  routes,
  scrollBehavior(to, from, savedPosition) {
    if (savedPosition) return savedPosition;
    if (to.hash) return { el: to.hash, behavior: "smooth", top: 80 };
    if (to.path === from.path) return false;
    return { top: 0 };
  },
});

// Global navigation guard
router.beforeEach((to, _from) => {
  const authStore = useAuthStore();
  const isAuthenticated = authStore.isAuthenticated || !!localStorage.getItem("token");
  const role = authStore.user?.role as string | undefined;

  if (to.meta.requiresAuth && !isAuthenticated) {
    return { path: "/login", query: { redirect: to.fullPath } };
  }

  if (to.meta.guest && isAuthenticated) {
    return ROLE_HOME[role ?? ""] || "/";
  }

  if (to.path === "/dashboard" && isAuthenticated) {
    return ROLE_HOME[role ?? ""] || "/";
  }

  if (to.meta.roles?.length && isAuthenticated) {
    if (!role || !to.meta.roles.includes(role)) {
      return ROLE_HOME[role ?? ""] || "/";
    }
  }

  return true;
});

// Handle errors - catch chunk loading failures
router.onError((error, to) => {
  console.error("Router error:", error);
  
  // If it's a chunk loading error, try reloading the page
  if (error.message?.includes('Loading chunk') || 
      error.message?.includes('Failed to fetch dynamically imported module')) {
    window.location.href = to.fullPath;
  }
});

// Document title
router.afterEach((to) => {
  const title = to.meta.title;
  document.title = title ? `${title} | ${APP_NAME}` : APP_NAME;
});

export default router;
