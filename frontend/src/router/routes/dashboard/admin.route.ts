import type { RouteRecordRaw } from "vue-router";
import { UserRole } from "@/types/common.types";

/**
 * ADMIN ROUTES
 * -----------------------------------------------------------------------------
 * Every view here was statically imported at the top of this file, so the whole
 * admin panel (~700KB of tables, charts and forms) shipped inside the entry
 * bundle to *every* visitor, including signed-out shoppers who can never reach
 * these pages. Now each is a real dynamic import.
 *
 * `meta.title` + `meta.breadcrumb` drive the document title and the shared
 * breadcrumb trail, so deep pages such as "edit coupon" finally tell the user
 * where they are (WCAG 2.4.2 / 2.4.8).
 */
const roles = { roles: [UserRole.Admin] };

export const adminRoutes: RouteRecordRaw[] = [
  {
    path: "admin",
    name: "AdminDashboard",
    component: () => import("@/views/Dashboard/admin/index.vue"),
    meta: { ...roles, title: "Admin Dashboard", breadcrumb: ["Dashboard"] },
  },

  // ---- Users -------------------------------------------------------------
  {
    path: "admin/users",
    name: "AdminUsers",
    component: () => import("@/views/Dashboard/admin/users/UserIndex.vue"),
    meta: { ...roles, title: "Users", breadcrumb: ["Users"] },
  },
  {
    path: "admin/users/user-management",
    name: "AdminUsersManagement",
    component: () => import("@/views/Dashboard/admin/users/UserManagement.vue"),
    meta: { ...roles, title: "Manage Users", breadcrumb: ["Users", "Manage"] },
  },
  {
    path: "admin/users/:id",
    name: "AdminUsersDetail",
    component: () => import("@/views/Dashboard/admin/users/UserDetails.vue"),
    meta: { ...roles, title: "User Details", breadcrumb: ["Users", "Details"] },
  },
  {
    path: "admin/users/:id/orders",
    name: "AdminUsersOrders",
    component: () => import("@/views/Dashboard/admin/orders/SingleUserOrders.vue"),
    meta: { ...roles, title: "User Orders", breadcrumb: ["Users", "Orders"] },
  },

  // ---- Orders ------------------------------------------------------------
  {
    path: "admin/orders",
    name: "AdminOrders",
    component: () => import("@/views/Dashboard/admin/orders/OrderIndex.vue"),
    meta: { ...roles, title: "Orders", breadcrumb: ["Orders"] },
  },
  {
    path: "admin/orders/:id",
    name: "AdminOrderDetails",
    component: () => import("@/views/Dashboard/admin/orders/OrderDetails.vue"),
    meta: { ...roles, title: "Order Details", breadcrumb: ["Orders", "Details"] },
  },

  // ---- Products ----------------------------------------------------------
  {
    path: "admin/products",
    name: "AdminProducts",
    component: () => import("@/views/Dashboard/admin/products/AdminProductManagement.vue"),
    meta: { ...roles, title: "Products", breadcrumb: ["Products"] },
  },
  {
    path: "admin/products/:id/details",
    name: "AdminProductDetails",
    component: () => import("@/views/Dashboard/admin/products/AdminProductDetails.vue"),
    meta: { ...roles, title: "Product Details", breadcrumb: ["Products", "Details"] },
  },
  {
    path: "admin/products/:id/edit",
    name: "AdminProductEdit",
    component: () => import("@/views/Dashboard/admin/products/AdminProductEdit.vue"),
    meta: { ...roles, title: "Edit Product", breadcrumb: ["Products", "Edit"] },
  },

  // ---- Categories --------------------------------------------------------
  {
    path: "admin/category",
    name: "AdminCategoryView",
    component: () => import("@/views/Dashboard/admin/category/CategoryIndex.vue"),
    meta: { ...roles, title: "Categories", breadcrumb: ["Categories"] },
  },
  {
    path: "admin/categories/create",
    name: "AdminCategoryCreate",
    component: () => import("@/views/Dashboard/admin/category/CreateCategory.vue"),
    meta: { ...roles, title: "New Category", breadcrumb: ["Categories", "New"] },
  },
  {
    path: "admin/categories/:id/edit",
    name: "AdminCategoryEdit",
    component: () => import("@/views/Dashboard/admin/category/EditCategory.vue"),
    meta: { ...roles, title: "Edit Category", breadcrumb: ["Categories", "Edit"] },
  },
  {
    path: "admin/categories/:id",
    name: "ViewSingleCategory",
    component: () => import("@/views/Dashboard/admin/category/SingleCategory.vue"),
    meta: { ...roles, title: "Category", breadcrumb: ["Categories", "Details"] },
  },

  // ---- Coupons -----------------------------------------------------------
  {
    path: "admin/coupons",
    name: "AdminCoupons",
    component: () => import("@/views/Dashboard/admin/coupon/AdminCreateCoupon.vue"),
    meta: { ...roles, title: "Create Coupon", breadcrumb: ["Coupons", "New"] },
  },
  {
    path: "admin/coupons/management",
    name: "AdminCouponsManagement",
    component: () => import("@/views/Dashboard/admin/coupon/AdminCouponManagement.vue"),
    meta: { ...roles, title: "Manage Coupons", breadcrumb: ["Coupons"] },
  },
  {
    path: "admin/coupons/:id/edit",
    name: "AdminCouponsEdit",
    component: () => import("@/views/Dashboard/admin/coupon/AdminCouponEdit.vue"),
    meta: { ...roles, title: "Edit Coupon", breadcrumb: ["Coupons", "Edit"] },
  },
  {
    path: "admin/coupons/:id",
    name: "AdminCouponsDetail",
    component: () => import("@/views/Dashboard/admin/coupon/AdminSingleCoupon.vue"),
    meta: { ...roles, title: "Coupon Details", breadcrumb: ["Coupons", "Details"] },
  },
];
