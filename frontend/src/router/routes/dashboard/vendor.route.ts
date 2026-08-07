import { UserRole } from "@/types/common.types";
import VendorCouponManagement from "@/views/Dashboard/vendor/coupons/VendorCouponManagement.vue";
import VendorCreateCoupon from "@/views/Dashboard/vendor/coupons/VendorCreateCoupon.vue";
import VendorEditCoupon from "@/views/Dashboard/vendor/coupons/VendorEditCoupon.vue";
import VendorSingleCouponDetails from "@/views/Dashboard/vendor/coupons/VendorSingleCouponDetails.vue";
import Detail from "@/views/Dashboard/vendor/orders/Detail.vue";
import type { RouteRecordRaw } from 'vue-router';

export const vendorRoutes: RouteRecordRaw[] = [
  {
    path: "vendor",
    name: "VendorDashboard",
    component: () => import("@/views/Dashboard/vendor/Index.vue"),
    meta: { roles: [UserRole.Vendor] },
  },
  {
    path: "vendor/profile",
    name: "VendorProfile",
    component: () => import("@/views/Dashboard/vendor/profile/Profile.vue"),
    meta: { roles: [UserRole.Vendor] },
  },
  {
    path: "vendor/products",
    name: "VendorProducts",
    component: () => import("@/views/Dashboard/vendor/products/Index.vue"),
    meta: { roles: [UserRole.Vendor] },
  },
  {
    path: "vendor/products/create",
    name: "VendorProductCreate",
    component: () => import("@/views/Dashboard/vendor/products/Create.vue"),
    meta: { roles: [UserRole.Vendor] },
  },
  {
  path: "vendor/products/:id/edit",
  name: "VendorProductEdit",
  component: () => import("@/views/Dashboard/vendor/products/Edit.vue"),
  meta: { roles: [UserRole.Vendor] },
},
  {
    path: "vendor/orders",
    name: "VendorOrders",
    component: () => import("@/views/Dashboard/vendor/orders/Index.vue"),
    meta: { roles: [UserRole.Vendor] },
  },
  {
    path: "vendor/orders/:id",
    name: "VendorOrderDetail",
    component: () => Detail,
    meta: { roles: [UserRole.Vendor] },
  },
  {
    path: "vendor/payments",
    name: "VendorPayments",
    component: () => import("@/views/Dashboard/vendor/payments/Index.vue"),
    meta: { roles: [UserRole.Vendor] },
  },
  {
    path: "vendor/shipping",
    name: "VendorShipping",
    component: () => import("@/views/Dashboard/vendor/shipping/Index.vue"),
    meta: { roles: [UserRole.Vendor] },
  },
  {
    path: "vendor/coupons/create",
    name: "VendorCouponCreate",
    component: () => VendorCreateCoupon,
    meta: { roles: [UserRole.Vendor] },
  },
  {
    path: "vendor/coupons/management",
    name: "VendorCouponManagement",
    component: () => VendorCouponManagement,
    meta: { roles: [UserRole.Vendor] },
  },
  {
    path: "vendor/coupons/:id/edit",
    name: "VendorCouponEdit",
    component: () => VendorEditCoupon,
    meta: { roles: [UserRole.Vendor] },
  },
  {
    path: "vendor/coupons/:id/details",
    name: "VendorCouponDetails",
    component: () => VendorSingleCouponDetails,
    meta: { roles: [UserRole.Vendor] },
  },
];