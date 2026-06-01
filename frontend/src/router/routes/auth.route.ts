import LoginRegister from "../../views/Auth/LoginRegister.vue";

export const authRoutes = [
  {
    path: "/login",
    name: "login",
    component: () => LoginRegister,
    meta: { guest: true },
  },
  {
    path: "/register",
    name: "register",
    component: () => LoginRegister,
    meta: { guest: true },
  },
];