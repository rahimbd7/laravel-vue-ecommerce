import Shop from "@/views/Products/Shop.vue";
import ProductDetail from "@/views/Products/ProductDetail.vue";
import Home from "@/views/Home/Home.vue";
import CartDetails from "@/views/Cart/CartDetails.vue";
import Checkout from "@/views/Checkout/Checkout.vue";
import type { RouteRecordRaw } from "vue-router";

export const publicRoutes: RouteRecordRaw[] = [
  {
    path: "/",
    name: "home",
    component: () => Home,
    meta: { title: "Home" },
  },
  {
    path: "/shop",
    name: "shop",
    component: () =>Shop ,
    meta: { title: "Shop All Products" },
  },
  {
    path: "/product/:slug",
    name: "product-detail",
    component: () => ProductDetail,
    meta: { title: "Product" },
  },
  {
    path: "/category/:slug",
    name: "category",
    component: () => Shop,
    meta: { title: "Category" },
  },
  {
    path: "/collections",
    name: "collections",
    component: () => Shop,
    meta: { title: "Collections" },
  },
  {
    path: "/new-arrivals",
    name: "new-arrivals",
    component: () => Shop,
    meta: { title: "New Arrivals" },
  },
  {
    path: "/sale",
    name: "sale",
    component: () => Shop,
    meta: { title: "Sale" },
  },
  {
    path: "/cart",
    name: "cart",
    component: () => CartDetails,
    meta: { title: "Shopping Cart" },
  },
  {
    path: "/checkout",
    name: "checkout",
    component: () => Checkout,
    meta: { requiresAuth: true, title: "Checkout" },
  },
];
