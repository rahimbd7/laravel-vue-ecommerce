// import { createRouter, createWebHistory } from "vue-router";
// import Home from "../views/Home/Home.vue";
// import Shop from "../views/Products/Shop.vue";
// import ProductDetail from "../views/Products/ProductDetail.vue";
// import CartDetails from "../views/Cart/CartDetails.vue";
// import Checkout from "../views/Checkout/Checkout.vue";
// import LoginRegister from "../views/Auth/LoginRegister.vue";
// import OrderList from "../views/Orders/OrderList.vue";
// import OrderDetails from "../views/Orders/OrderDetails.vue";

// const router = createRouter({
//   history: createWebHistory(),
//   routes: [
//     {
//       path: "/",
//       name: "home",
//       component: () => Home,
//     },
//     {
//       path: "/shop",
//       name: "shop",
//       component: () => Shop,
//     },
//     {
//       path: "/product/:slug",
//       name: "product-detail",
//       component: () => ProductDetail,
//     },
//     {
//       path: "/category/:slug",
//       name: "category",
//       component: () => Shop,
//     },
//     {
//       path: "/collections",
//       name: "collections",
//       component: () => Shop,
//     },
//     {
//       path: "/new-arrivals",
//       name: "new-arrivals",
//       component: () => Shop,
//     },
//     {
//       path: "/sale",
//       name: "sale",
//       component: () => Shop,
//     },
//     {
//       path: "/cart",
//       name: "cart",
//       component: () => CartDetails,
//     },
//     {
//       path: "/checkout",
//       name: "checkout",
//       component: () => Checkout,
//     },
//     {
//       path: "/orders",
//       name: "OrderList",
//       component: () => OrderList,
//       meta: { requiresAuth: true },
//     },
//     {
//       path: "/order/:id",
//       name: "OrderDetail",
//       component: () => OrderDetails,
//       meta: { requiresAuth: true },
//     },
//     {
//       path: "/login",
//       name: "login",
//       component: () => LoginRegister,
//     },
//   ],
// });

// export default router;


import { createRouter, createWebHistory } from "vue-router";
import type { RouteRecordRaw } from "vue-router";
import { useAuthStore } from "@/stores/auth.store";

// Import route modules
import { publicRoutes } from "./routes/public.route";
import { authRoutes } from "./routes/auth.route";
import { orderRoutes } from "./routes/order.route";
import { dashboardRoutes } from "./routes/dashboard";

declare module 'vue-router' {
  interface RouteMeta {
    requiresAuth?: boolean;
    guest?: boolean;
    roles?: string[];
  }
}

const routes: RouteRecordRaw[] = [
  ...publicRoutes, 
  ...authRoutes, 
  ...orderRoutes, 
  ...dashboardRoutes
];

const router = createRouter({
  history: createWebHistory(),
  routes,
});

// Navigation Guards
router.beforeEach((to, from, next) => {
  const authStore = useAuthStore();
  const token = localStorage.getItem("token");
  const isAuthenticated = authStore.isAuthenticated || !!token;

  // Check if route requires authentication
  if (to.meta.requiresAuth && !isAuthenticated) {
    next("/login");
    return;
  }

  // Redirect authenticated users from guest pages (login/register)
  if (to.meta.guest && isAuthenticated) {
    const role = authStore.user?.role as string;
    const roleMap: Record<string, string> = {
      admin: "/dashboard/admin",
      vendor: "/dashboard/vendor",
      customer: "/dashboard/customer",
    };
    next(roleMap[role] || "/");
    return;
  }

  // Redirect /dashboard to role-specific dashboard
  if (to.path === '/dashboard' && isAuthenticated) {
    const role = authStore.user?.role as string;
    const roleMap: Record<string, string> = {
      admin: "/dashboard/admin",
      vendor: "/dashboard/vendor",
      customer: "/dashboard/customer",
    };
    next(roleMap[role] || "/");
    return;
  }

  // Check role-based access for dashboard routes
  if (to.meta.roles && Array.isArray(to.meta.roles) && isAuthenticated) {
    const userRole = authStore.user?.role;
    if (!userRole || !to.meta.roles.includes(userRole)) {
      const roleMap: Record<string, string> = {
        admin: "/dashboard/admin",
        vendor: "/dashboard/vendor",
        customer: "/dashboard/customer",
      };
      next(roleMap[userRole as string] || "/");
      return;
    }
  }

  next();
});

export default router;