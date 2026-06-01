import "./assets/main.css";

import { createApp } from "vue";
import App from "./App.vue";
import router from "./router";
import pinia from "./stores";
import { defaultConfig, plugin } from "@formkit/vue";
import PrimeVue from "primevue/config";
import ToastService from 'primevue/toastservice';
import ConfirmationService from 'primevue/confirmationservice';

// Import PrimeVue styles
import 'primeicons/primeicons.css';
// Import the theme from @primevue/themes
import Aura from '@primevue/themes/aura';

// Import fontawesome css
import "@fortawesome/fontawesome-free/css/all.min.css";
import 'vue-toast-notification/dist/theme-sugar.css';

const app = createApp(App);

app.use(plugin, defaultConfig);

// PrimeVue v4 with theme
app.use(PrimeVue, {
    theme: {
        preset: Aura,
        options: {
            darkModeSelector: false,
        }
    },
    ripple: true,
});

app.use(router);
app.use(pinia);
app.use(ToastService);
app.use(ConfirmationService);

app.mount("#app");