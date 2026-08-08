import type { RouteRecordRaw } from "vue-router";
import { adminRoutes } from "./admin.route";
import { vendorRoutes } from "./vendor.route";
import { customerRoutes } from "./customer.route";

/**
 * The dashboard shell itself is now lazy too. `component: () => DashBoard`
 * with a static import above it pulled PrimeVue's Menubar, PanelMenu,
 * SplitButton, ConfirmDialog and Toast into the entry chunk for every guest.
 */
export const dashboardRoutes: RouteRecordRaw[] = [
  {
    path: "/dashboard",
    name: "dashboard",
    component: () => import("@/Layout/DashBoard.vue"),
    // `layout: 'dashboard'` lets App.vue suppress the storefront Navbar/Footer,
    // which used to render *in addition to* the dashboard chrome - two navbars
    // stacked on top of each other and a marketing footer under the admin
    // tables.
    meta: { requiresAuth: true, layout: "dashboard" },
    children: [...adminRoutes, ...vendorRoutes, ...customerRoutes],
  },
];
