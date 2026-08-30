import type { RouteRecordRaw } from "vue-router";
import { UserRole } from "@/types/common.types";

const roles = { roles: [UserRole.Customer] };

export const customerRoutes: RouteRecordRaw[] = [
  {
    path: "customer",
    name: "CustomerDashboard",
    component: () => import("@/views/Dashboard/customer/index.vue"),
    meta: { ...roles, title: "My Account", breadcrumb: ["Overview"] },
  },
  {
    path: "customer/profile",
    name: "CustomerProfile",
    component: () => import("@/views/Dashboard/customer/profile/CustomerProfile.vue"),
    meta: { ...roles, title: "My Profile", breadcrumb: ["Profile"] },
  },
  {
    path: "customer/address",
    name: "CustomerAddresses",
    component: () => import("@/views/Dashboard/customer/address/CustomerAddress.vue"),
    meta: { ...roles, title: "Addresses", breadcrumb: ["Profile", "Addresses"] },
  },
  {
    path: "customer/orders",
    name: "CustomerOrders",
    component: () => import("@/views/Dashboard/customer/orders/CustomerOrder.vue"),
    meta: { ...roles, title: "My Orders", breadcrumb: ["Orders"] },
  },
  {
    path: "customer/order/:id",
    name: "CustomerOrderDetails",
    component: () => import("@/views/Dashboard/customer/orders/CustomerOrderDetails.vue"),
    meta: { ...roles, title: "Order Details", breadcrumb: ["Orders", "Details"] },
  },
  {
    path: "customer/wishlist",
    name: "CustomerWishlist",
    component: () => import("@/views/Dashboard/customer/wishlist/CustomerWishList.vue"),
    meta: { ...roles, title: "My Wishlist", breadcrumb: ["Wishlist"] },
  },
  {
    // Was commented out while the sidebar still linked here -> dead link.
    path: "customer/payments",
    name: "CustomerPayments",
    component: () => import("@/views/Dashboard/customer/payments/CustomerPayment.vue"),
    meta: { ...roles, title: "Payment History", breadcrumb: ["Payments"] },
  },
];
