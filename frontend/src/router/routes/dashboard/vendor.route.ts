import type { RouteRecordRaw } from "vue-router";
import { UserRole } from "@/types/common.types";

const roles = { roles: [UserRole.Vendor] };

export const vendorRoutes: RouteRecordRaw[] = [
  {
    path: "vendor",
    name: "VendorDashboard",
    component: () => import("@/views/Dashboard/vendor/Index.vue"),
    meta: { ...roles, title: "Vendor Dashboard", breadcrumb: ["Dashboard"] },
  },
  {
    path: "vendor/profile",
    name: "VendorProfile",
    component: () => import("@/views/Dashboard/vendor/profile/Profile.vue"),
    meta: { ...roles, title: "Store Profile", breadcrumb: ["Profile"] },
  },

  // ---- Products ----------------------------------------------------------
  {
    path: "vendor/products",
    name: "VendorProducts",
    component: () => import("@/views/Dashboard/vendor/products/Index.vue"),
    meta: { ...roles, title: "My Products", breadcrumb: ["Products"] },
  },
  {
    path: "vendor/products/create",
    name: "VendorProductCreate",
    component: () => import("@/views/Dashboard/vendor/products/Create.vue"),
    meta: { ...roles, title: "Add Product", breadcrumb: ["Products", "New"] },
  },
  {
    path: "vendor/products/:id/edit",
    name: "VendorProductEdit",
    component: () => import("@/views/Dashboard/vendor/products/Edit.vue"),
    meta: { ...roles, title: "Edit Product", breadcrumb: ["Products", "Edit"] },
  },

  // ---- Orders ------------------------------------------------------------
  {
    path: "vendor/orders",
    name: "VendorOrders",
    component: () => import("@/views/Dashboard/vendor/orders/Index.vue"),
    meta: { ...roles, title: "Orders", breadcrumb: ["Orders"] },
  },
  {
    path: "vendor/orders/:id",
    name: "VendorOrderDetail",
    component: () => import("@/views/Dashboard/vendor/orders/Detail.vue"),
    meta: { ...roles, title: "Order Details", breadcrumb: ["Orders", "Details"] },
  },

  // ---- Payments & shipping ----------------------------------------------
  {
    path: "vendor/payments",
    name: "VendorPayments",
    component: () => import("@/views/Dashboard/vendor/payments/Index.vue"),
    meta: { ...roles, title: "Payments & Payouts", breadcrumb: ["Payments"] },
  },
  {
    path: "vendor/shipping",
    name: "VendorShipping",
    component: () => import("@/views/Dashboard/vendor/shipping/Index.vue"),
    meta: { ...roles, title: "Shipping Settings", breadcrumb: ["Shipping"] },
  },

  // ---- Coupons -----------------------------------------------------------
  {
    path: "vendor/coupons/create",
    name: "VendorCouponCreate",
    component: () => import("@/views/Dashboard/vendor/coupons/VendorCreateCoupon.vue"),
    meta: { ...roles, title: "Create Coupon", breadcrumb: ["Coupons", "New"] },
  },
  {
    path: "vendor/coupons/management",
    name: "VendorCouponManagement",
    component: () => import("@/views/Dashboard/vendor/coupons/VendorCouponManagement.vue"),
    meta: { ...roles, title: "Manage Coupons", breadcrumb: ["Coupons"] },
  },
  {
    path: "vendor/coupons/:id/edit",
    name: "VendorCouponEdit",
    component: () => import("@/views/Dashboard/vendor/coupons/VendorEditCoupon.vue"),
    meta: { ...roles, title: "Edit Coupon", breadcrumb: ["Coupons", "Edit"] },
  },
  {
    path: "vendor/coupons/:id/details",
    name: "VendorCouponDetails",
    component: () => import("@/views/Dashboard/vendor/coupons/VendorSingleCouponDetails.vue"),
    meta: { ...roles, title: "Coupon Details", breadcrumb: ["Coupons", "Details"] },
  },
];
