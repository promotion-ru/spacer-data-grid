import Aura from '@primeuix/themes/aura';
import tailwindcss from "@tailwindcss/vite";

export default defineNuxtConfig({
    devtools: {enabled: true},

    modules: [
      '@pinia/nuxt',
      '@primevue/nuxt-module',
      '@vueuse/nuxt',
      '@nuxtjs/device'
    ],

    css: [
        '~/assets/css/main.css',
        'primeicons/primeicons.css'
    ],

    primevue: {
        options: {
            theme: {
                preset: Aura,
                options: {
                    darkModeSelector: '[data-theme="dark"]',
                }
            },
        }
    },

    tailwindcss: {
        config: {
            darkMode: ['selector', '[data-theme="dark"]'],
        }
    },

    runtimeConfig: {
        public: {
            apiBase: process.env.API_URL || 'http://localhost:8000',
            appUrl: process.env.APP_URL || 'http://localhost:3000'
        }
    },

    ssr: false,

    nitro: {
        storage: {
            redis: {
                driver: 'memory'
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
    },

    app: {
        head: {
            meta: [
                {
                    name: 'viewport',
                    content: 'width=device-width, initial-scale=1.0'
                }
            ]
        }
    }
})