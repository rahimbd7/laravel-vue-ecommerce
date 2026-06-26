import { UserRole } from "@/types/common.types";
import CustomerAddress from "@/views/Dashboard/customer/address/CustomerAddress.vue";
import Index from "@/views/Dashboard/customer/index.vue";
import CustomerOrder from "@/views/Dashboard/customer/orders/CustomerOrder.vue";
import CustomerOrderDetails from "@/views/Dashboard/customer/orders/CustomerOrderDetails.vue";
import CustomerProfile from "@/views/Dashboard/customer/profile/CustomerProfile.vue";
import CustomerWishList from "@/views/Dashboard/customer/wishlist/CustomerWishList.vue";

// Customer Dashboard Routes
export const customerRoutes = [
  {
    path: "customer",
    name: "CustomerDashboard",
    component: () => Index,
    meta: { roles: [UserRole.Customer] },
  },
  {
    path: "customer/profile",
    name: "CustomerProfile",
    component: () => CustomerProfile,
    meta: { roles: ["customer"] },
  },
  {
    path: "customer/address",
    name: "CustomerAddresses",
    component: () => CustomerAddress,
    meta: { roles: ["customer"] },
  },
  {
    path: "customer/orders",
    name: "CustomerOrders",
    component: () => CustomerOrder,
    meta: { roles: ["customer"] },
  },
  {
    path: "customer/order/:id",
    name: "CustomerOrderDetails",
    component: () => CustomerOrderDetails,
    meta: { roles: ["customer"] },
  },
  {
    path: "customer/wishlist",
    name: "CustomerWishlist",
    component: () => CustomerWishList,
    meta: { roles: ["customer"] },
  },
//   {
//     path: "customer/payments",
//     name: "CustomerPayments",
//     component: () => import("@/views/dashboard/customer/payments/Index.vue"),
//     meta: { roles: ["customer"] },
//   },
];