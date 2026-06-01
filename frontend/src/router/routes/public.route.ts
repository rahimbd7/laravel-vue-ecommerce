import Home from "../../views/Home/Home.vue";
import Shop from "../../views/Products/Shop.vue";
import ProductDetail from "../../views/Products/ProductDetail.vue";
import CartDetails from "../../views/Cart/CartDetails.vue";
import Checkout from "../../views/Checkout/Checkout.vue";

export const publicRoutes = [
  {
    path: "/",
    name: "home",
    component: () => Home,
  },
  {
    path: "/shop",
    name: "shop",
    component: () => Shop,
  },
  {
    path: "/product/:slug",
    name: "product-detail",
    component: () => ProductDetail,
  },
  {
    path: "/category/:slug",
    name: "category",
    component: () => Shop,
  },
  {
    path: "/collections",
    name: "collections",
    component: () => Shop,
  },
  {
    path: "/new-arrivals",
    name: "new-arrivals",
    component: () => Shop,
  },
  {
    path: "/sale",
    name: "sale",
    component: () => Shop,
  },
  {
    path: "/cart",
    name: "cart",
    component: () => CartDetails,
  },
  {
    path: "/checkout",
    name: "checkout",
    component: () => Checkout,
    meta: { requiresAuth: true },
  },
];