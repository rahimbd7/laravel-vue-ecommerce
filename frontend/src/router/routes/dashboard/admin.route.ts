import { UserRole } from "@/types/common.types";
import CategoryIndex from "@/views/Dashboard/admin/category/CategoryIndex.vue";
import CreateCategory from "@/views/Dashboard/admin/category/CreateCategory.vue";
import EditCategory from "@/views/Dashboard/admin/category/EditCategory.vue";
import SingleCategory from "@/views/Dashboard/admin/category/SingleCategory.vue";
import AdminCouponEdit from "@/views/Dashboard/admin/coupon/AdminCouponEdit.vue";
import AdminCouponManagement from "@/views/Dashboard/admin/coupon/AdminCouponManagement.vue";
import AdminCreateCoupon from "@/views/Dashboard/admin/coupon/AdminCreateCoupon.vue";
import AdminSingleCoupon from "@/views/Dashboard/admin/coupon/AdminSingleCoupon.vue";
import Index from "@/views/Dashboard/admin/index.vue";
import OrderDetails from "@/views/Dashboard/admin/orders/OrderDetails.vue";
import OrderIndex from "@/views/Dashboard/admin/orders/OrderIndex.vue";
import SingleUserOrders from "@/views/Dashboard/admin/orders/SingleUserOrders.vue";
import AdminProductDetails from "@/views/Dashboard/admin/products/AdminProductDetails.vue";
import AdminProductEdit from "@/views/Dashboard/admin/products/AdminProductEdit.vue";
import AdminProductManagement from "@/views/Dashboard/admin/products/AdminProductManagement.vue";
import UserDetails from "@/views/Dashboard/admin/users/UserDetails.vue";
import UserIndex from "@/views/Dashboard/admin/users/UserIndex.vue";
import UserManagement from "@/views/Dashboard/admin/users/UserManagement.vue";

// Admin Dashboard Routes
export const adminRoutes = [
  {
    path: "admin",
    name: "AdminDashboard",
    component: () => Index,
    meta: { roles: [UserRole.Admin] },
  },
  {
    path: "admin/users",
    name: "AdminUsers",
    component: () => UserIndex,
    meta: { roles: ["admin"] },
  },
  {
    path: "admin/users/user-management",
    name: "AdminUsersManagement",
    component: () => UserManagement,
    meta: { roles: ["admin"] },
  },
  {
    path: "admin/users/:id",
    name: "AdminUsersDetail",
    component: () => UserDetails,
    meta: { roles: ["admin"] },
  },
  {
    path: "admin/users/:id/orders",
    name: "AdminUsersOrders",
    component: () => SingleUserOrders,
    meta: { roles: ["admin"] },
  },
  {
    path: "admin/orders",
    name: "AdminOrders",
    component: () => OrderIndex,
    meta: { roles: ["admin"] },
  },
  {
    path: "admin/orders/:id",
    name: "AdminOrderDetails",
    component: () => OrderDetails,
    meta: { roles: ["admin"] },
  },
  {
    path: "admin/products",
    name: "AdminProducts",
    component: () => AdminProductManagement,
    meta: { roles: ["admin"] },
  },
  {
    path: "admin/products/:id/details",
    name: "AdminProductDetails",
    component: () => AdminProductDetails,
    meta: { roles: ["admin"] },
  },
  {
    path: "admin/products/:id/edit",
    name: "AdminProductEdit",
    component: () => AdminProductEdit,
    meta: { roles: ["admin"] },
  },
  {
    path: "admin/category",
    name: "AdminCategoryView",
    component: () => CategoryIndex,
    meta: { roles: ["admin"] },
  },
  {
    path: "admin/categories/:id/edit",
    name: "AdminCategoryEdit",
    component: () => EditCategory,
    meta: { roles: ["admin"] },
  },
  {
    path: "admin/categories/create",
    name: "AdminCategoryCreate",
    component: () => CreateCategory,
    meta: { roles: ["admin"] },
  },
  {
    path: "admin/categories/:id",
    name: "ViewSingleCategory",
    component: () => SingleCategory,
    meta: { roles: ["admin"] },
  },
  {
    path: "admin/coupons",
    name: "AdminCoupons",
    component: () => AdminCreateCoupon,
    meta: { roles: ["admin"] },
  },
  {
    path: "admin/coupons/management",
    name: "AdminCouponsManagement",
    component: () => AdminCouponManagement,
    meta: { roles: ["admin"] },
  },
  {
    path: "admin/coupons/:id/edit",
    name: "AdminCouponsEdit",
    component: () => AdminCouponEdit,
    meta: { roles: ["admin"] },
  },
  {
    path: "admin/coupons/:id",
    name: "AdminCouponsDetail",
    component: () => AdminSingleCoupon,
    meta: { roles: ["admin"] },
  },
  // {
  //   path: "admin/users/vendors",
  //   name: "AdminVendors",
  //   component: () => import("@/views/dashboard/admin/users/Vendors.vue"),
  //   meta: { roles: ["admin"] },
  // },
  // {
  //   path: "admin/products",
  //   name: "AdminProducts",
  //   component: () => import("@/views/dashboard/admin/products/Index.vue"),
  //   meta: { roles: ["admin"] },
  // },
  // {
  //   path: "admin/products/create",
  //   name: "AdminProductCreate",
  //   component: () => import("@/views/dashboard/admin/products/Create.vue"),
  //   meta: { roles: ["admin"] },
  // },
  // {
  //   path: "admin/orders",
  //   name: "AdminOrders",
  //   component: () => import("@/views/dashboard/admin/orders/Index.vue"),
  //   meta: { roles: ["admin"] },
  // },
  // {
  //   path: "admin/reviews",
  //   name: "AdminReviews",
  //   component: () => import("@/views/dashboard/admin/reviews/Index.vue"),
  //   meta: { roles: ["admin"] },
  // },
  // {
  //   path: "admin/payments",
  //   name: "AdminPayments",
  //   component: () => import("@/views/dashboard/admin/payments/Index.vue"),
  //   meta: { roles: ["admin"] },
  // },
  // {
  //   path: "admin/settings",
  //   name: "AdminSettings",
  //   component: () => import("@/views/dashboard/admin/settings/Index.vue"),
  //   meta: { roles: ["admin"] },
  // },
];