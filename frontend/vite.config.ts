import { fileURLToPath, URL } from 'node:url'

import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import vueDevTools from 'vite-plugin-vue-devtools'
import tailwindcss from '@tailwindcss/vite'

// https://vite.dev/config/
export default defineConfig({
  plugins: [
    vue(),
    vueDevTools(),
    tailwindcss()
  ],
  resolve: {
    alias: {
      '@': fileURLToPath(new URL('./src', import.meta.url))
    },
  },
  build: {
    /**
     * PERFORMANCE
     * -------------------------------------------------------------------------
     * Baseline build produced a SINGLE 2.05 MB entry chunk, because every route
     * eagerly `import`ed its view at the top of the route file. A first-time
     * visitor landing on the home page downloaded and parsed the entire admin
     * panel, the vendor panel, Chart.js and SweetAlert2 before seeing anything.
     *
     * Route-level `() => import()` (see src/router/routes/*) splits the views.
     * These manual chunks then stop the heavy shared vendors from being
     * duplicated into, or inlined alongside, those route chunks:
     *
     *  - `charts` (chart.js + vue-chartjs, ~200KB) is admin/vendor-only, so no
     *    customer ever pays for it.
     *  - `primevue` is large and shared by dashboards; a stable separate chunk
     *    means it stays in the browser cache across app deploys.
     *  - `vendor` holds the framework core that genuinely is needed everywhere.
     */
    rollupOptions: {
      output: {
        manualChunks(id) {
          if (!id.includes('node_modules')) return

          if (id.includes('chart.js') || id.includes('vue-chartjs')) return 'charts'
          if (id.includes('primevue')) return 'primevue'
          if (id.includes('@formkit')) return 'forms'
          if (
            id.includes('/vue/') ||
            id.includes('/vue-router/') ||
            id.includes('/pinia/') ||
            id.includes('/pinia-plugin-persistedstate/') ||
            id.includes('/axios/')
          ) return 'vendor'
        },
      },
    },
    // The old build silently exceeded Rollup's 500KB warning on every chunk,
    // so the warning had become noise. 350KB keeps it meaningful again.
    chunkSizeWarningLimit: 350,
    // Cheap win: strips ~15% off CSS vs the default esbuild minifier.
    cssMinify: 'lightningcss',
    sourcemap: false,
  },
})
