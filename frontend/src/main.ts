import "./assets/main.css";

import { createApp } from "vue";
import App from "./App.vue";
import router from "./router";
import pinia from "./stores";
import { defaultConfig, plugin } from "@formkit/vue";
import PrimeVue from "primevue/config";
import ToastService from "primevue/toastservice";
import ConfirmationService from "primevue/confirmationservice";
import { definePreset } from "@primeuix/themes";
import Aura from "@primevue/themes/aura";

import "primeicons/primeicons.css";
import "@fortawesome/fontawesome-free/css/all.min.css";

import { registerToastService } from "./composables/useNotify";

const BrandPreset = definePreset(Aura, {
  semantic: {
    primary: {
      50: "{teal.50}",
      100: "#d3ede8",
      200: "#a8dbd3",
      300: "#6cbcb2",
      400: "#3d9c92",
      500: "#168076",
      600: "#00685f",
      700: "#004f45",
      800: "#003d36",
      900: "#002b26",
      950: "#001a17",
    },
    colorScheme: {
      light: {
        primary: {
          color: "#00685f",
          contrastColor: "#ffffff",
          hoverColor: "#004f45",
          activeColor: "#003d36",
        },
        // 2px offset ring matching the design-system :focus-visible style
        focusRing: {
          width: "2px",
          style: "solid",
          color: "#00685f",
          offset: "2px",
        },
      },
    },
  },
});

const app = createApp(App);

app.use(plugin, defaultConfig);

app.use(PrimeVue, {
  theme: {
    preset: BrandPreset,
    options: {
      darkModeSelector: false,
      /**
       * Tailwind utilities and PrimeVue's own CSS were fighting for specificity,
       * which is why the codebase is littered with `!px-2` overrides. Declaring
       * PrimeVue inside a lower cascade layer lets plain Tailwind classes win
       * without `!important`.
       */
      cssLayer: {
        name: "primevue",
        order: "theme, base, primevue, components, utilities",
      },
    },
  },
  ripple: true,
});

app.use(router);
app.use(pinia);
app.use(ToastService);
app.use(ConfirmationService);

/**
 * Hand the app-wide ToastService to useNotify() so any module - including
 * plain .ts files such as stores and API helpers, which cannot call a
 * composable - can surface user feedback through one funnel.
 */
registerToastService(app.config.globalProperties.$toast);

app.mount("#app");
