import type { RouteRecordRaw } from "vue-router";

/**
 * PERFORMANCE: these were static top-of-file imports wrapped in an arrow
 * (`component: () => Home`). That arrow looks lazy but is not - the `import`
 * statement above it had already pulled the component into the entry bundle.
 * Real dynamic `import()` gives each view its own chunk.
 *
 * `meta.title` feeds the document.title updater in router/index.ts (WCAG 2.4.2
 * Page Titled - the whole SPA previously reported "Vite App" on every route,
 * making browser history and screen-reader page announcements useless).
 */

export const publicRoutes: RouteRecordRaw[] = [
  {
    path: "/",
    name: "home",
    component: () => import("@/views/Home/Home.vue"),
    meta: { title: "Home" },
  },
  {
    path: "/shop",
    name: "shop",
    component: () => {
      console.log ('shop');
      return import("@/views/Products/Shop.vue");
    },
    meta: { title: "Shop All Products" },
  },
  {
    path: "/product/:slug",
    name: "product-detail",
    component: () => import("@/views/Products/ProductDetail.vue"),
    meta: { title: "Product" },
  },
  {
    path: "/category/:slug",
    name: "category",
    component: () => import("@/views/Products/Shop.vue"),
    meta: { title: "Category" },
  },
  {
    path: "/collections",
    name: "collections",
    component: () => import("@/views/Products/Shop.vue"),
    meta: { title: "Collections" },
  },
  {
    path: "/new-arrivals",
    name: "new-arrivals",
    component: () => import("@/views/Products/Shop.vue"),
    meta: { title: "New Arrivals" },
  },
  {
    path: "/sale",
    name: "sale",
    component: () => import("@/views/Products/Shop.vue"),
    meta: { title: "Sale" },
  },
  {
    path: "/cart",
    name: "cart",
    component: () => import("@/views/Cart/CartDetails.vue"),
    meta: { title: "Shopping Cart" },
  },
  {
    path: "/checkout",
    name: "checkout",
    component: () => import("@/views/Checkout/Checkout.vue"),
    meta: { requiresAuth: true, title: "Checkout" },
  },
];
