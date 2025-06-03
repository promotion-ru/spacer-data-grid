import Aura from '@primeuix/themes/aura';
import tailwindcss from "@tailwindcss/vite";

export default defineNuxtConfig({
    devtools: {enabled: true},

    modules: [
        '@pinia/nuxt',
        '@primevue/nuxt-module'
    ],

    css: [
        '~/assets/css/main.css',
        'primeicons/primeicons.css'
    ],

    primevue: {
        options: {
            theme: {
                preset: Aura
            }
        }
    },

    runtimeConfig: {
        public: {
            apiBase: process.env.API_URL || 'http://localhost:8000',
        }
    },

    nitro: {
        devProxy: {
            '/api': {
                target: 'http://backend:8000/api',
                changeOrigin: true,
            }
        }
    },

    vite: {
        plugins: [
            tailwindcss(),
        ],
        server: {
            host: '0.0.0.0',
            port: 3000,
            hmr: {
                protocol: 'ws',
                host: 'localhost',
            }
        }
    }
})