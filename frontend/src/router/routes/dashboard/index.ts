import { adminRoutes } from "./admin.route";
import { vendorRoutes } from "./vendor.route";
import { customerRoutes } from "./customer.route";
import type { RouteRecordRaw } from 'vue-router';
import DashBoard from "@/Layout/DashBoard.vue";

// Dashboard Layout
const DashboardLayout = () => DashBoard;

export const dashboardRoutes: RouteRecordRaw[] = [
  {
    path: "/dashboard",
    name: "dashboard", // Add a name here
    component: DashboardLayout,
    meta: { requiresAuth: true },
    children: [
      ...adminRoutes, 
      ...vendorRoutes, 
      ...customerRoutes
    ],
  },
];