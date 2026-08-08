import type { RouteRecordRaw } from "vue-router";

/**
 * `/login` and `/register` shared one component that toggled an internal
 * `isRegisterMode` boolean, so visiting /register still rendered the LOGIN
 * panel until you clicked "Create New Account". `meta.mode` now tells the view
 * which panel to open, making both URLs deep-linkable and shareable.
 */
export const authRoutes: RouteRecordRaw[] = [
  {
    path: "/login",
    name: "login",
    component: () => import("@/views/Auth/LoginRegister.vue"),
    meta: { guest: true, title: "Sign In", mode: "login", layout: "bare" },
  },
  {
    path: "/register",
    name: "register",
    component: () => import("@/views/Auth/LoginRegister.vue"),
    meta: { guest: true, title: "Create Account", mode: "register", layout: "bare" },
  },
];
