import { UserRole } from "@/types/common.types";
import Index from "@/views/Dashboard/customer/index.vue";

// Customer Dashboard Routes
export const customerRoutes = [
  {
    path: "customer",
    name: "CustomerDashboard",
    component: () => Index,
    meta: { roles: [UserRole.Customer] },
  },
//   {
//     path: "customer/profile",
//     name: "CustomerProfile",
//     component: () => import("@/views/dashboard/customer/Profile.vue"),
//     meta: { roles: ["customer"] },
//   },
//   {
//     path: "customer/addresses",
//     name: "CustomerAddresses",
//     component: () => import("@/views/dashboard/customer/Addresses.vue"),
//     meta: { roles: ["customer"] },
//   },
//   {
//     path: "customer/orders",
//     name: "CustomerOrders",
//     component: () => import("@/views/dashboard/customer/orders/Index.vue"),
//     meta: { roles: ["customer"] },
//   },
//   {
//     path: "customer/wishlist",
//     name: "CustomerWishlist",
//     component: () => import("@/views/dashboard/customer/Wishlist.vue"),
//     meta: { roles: ["customer"] },
//   },
//   {
//     path: "customer/payments",
//     name: "CustomerPayments",
//     component: () => import("@/views/dashboard/customer/payments/Index.vue"),
//     meta: { roles: ["customer"] },
//   },
];