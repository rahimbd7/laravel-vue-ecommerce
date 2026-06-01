import { UserRole } from "@/types/common.types";
import Index from "@/views/Dashboard/vendor/index.vue";

// Vendor Dashboard Routes
export const vendorRoutes = [
  {
    path: "vendor",
    name: "VendorDashboard",
    component: () => Index,
    meta: { roles: [UserRole.Vendor] },
  },
//   {
//     path: "vendor/profile",
//     name: "VendorProfile",
//     component: () => import("@/views/dashboard/vendor/Profile.vue"),
//     meta: { roles: ["vendor"] },
//   },
//   {
//     path: "vendor/products",
//     name: "VendorProducts",
//     component: () => import("@/views/dashboard/vendor/products/Index.vue"),
//     meta: { roles: ["vendor"] },
//   },
//   {
//     path: "vendor/products/create",
//     name: "VendorProductCreate",
//     component: () => import("@/views/dashboard/vendor/products/Create.vue"),
//     meta: { roles: ["vendor"] },
//   },
//   {
//     path: "vendor/orders",
//     name: "VendorOrders",
//     component: () => import("@/views/dashboard/vendor/orders/Index.vue"),
//     meta: { roles: ["vendor"] },
//   },
//   {
//     path: "vendor/payments",
//     name: "VendorPayments",
//     component: () => import("@/views/dashboard/vendor/payments/Index.vue"),
//     meta: { roles: ["vendor"] },
//   },
//   {
//     path: "vendor/shipping",
//     name: "VendorShipping",
//     component: () => import("@/views/dashboard/vendor/shipping/Index.vue"),
//     meta: { roles: ["vendor"] },
//   },
];