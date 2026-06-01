import OrderList from "../../views/Orders/OrderList.vue";
import OrderDetails from "../../views/Orders/OrderDetails.vue";

export const orderRoutes = [
  {
    path: "/orders",
    name: "OrderList",
    component: () => OrderList,
    meta: { requiresAuth: true },
  },
  {
    path: "/order/:id",
    name: "OrderDetail",
    component: () => OrderDetails,
    meta: { requiresAuth: true },
  },
];