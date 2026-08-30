import type { RouteRecordRaw } from "vue-router";

export const authRoutes: RouteRecordRaw[] = [
  {
    path: "/login",
    name: "login",
    component: () => import("@/views/Auth/LoginRegister.vue"),
    meta: { guest: true, title: "Sign In", mode: "login" },
  },
  {
    path: "/register",
    name: "register",
    component: () => import("@/views/Auth/LoginRegister.vue"),
    meta: { guest: true, title: "Create Account", mode: "register" },
  },
];
