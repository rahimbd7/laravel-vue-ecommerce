import { UserRole } from "@/types/common.types";
import Index from "@/views/Dashboard/admin/index.vue";
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