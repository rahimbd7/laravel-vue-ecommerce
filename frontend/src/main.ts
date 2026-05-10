import "./assets/main.css";

import { createApp } from "vue";
import App from "./App.vue";
import router from "./router";
import pinia from "./stores";
import { defaultConfig, plugin } from "@formkit/vue";
import PrimeVue from "primevue/config";

const app = createApp(App);
app.use(plugin, defaultConfig);
app.use(PrimeVue, {unstyled: true});
app.use(router);
app.use(pinia);
app.mount("#app");
