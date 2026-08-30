import type { RouteRecordRaw } from "vue-router";
import { adminRoutes } from "./admin.route";
import { vendorRoutes } from "./vendor.route";
import { customerRoutes } from "./customer.route";

export const dashboardRoutes: RouteRecordRaw[] = [
  {
    path: "/dashboard",
    name: "dashboard",
    redirect: "/dashboard/customer",
    component: () => import("@/Layout/DashBoard.vue"),
    // `layout: 'dashboard'` lets App.vue suppress the storefront Navbar/Footer,
    // which used to render *in addition to* the dashboard chrome - two navbars
    // stacked on top of each other and a marketing footer under the admin
    // tables.
    meta: { requiresAuth: true, layout: "dashboard" },
    children: [...adminRoutes, ...vendorRoutes, ...customerRoutes],
  },
];
