import Aura from '@primeuix/themes/aura';
import tailwindcss from "@tailwindcss/vite";

export default defineNuxtConfig({
    devtools: {enabled: true},

    modules: ['@pinia/nuxt', '@primevue/nuxt-module', '@vueuse/nuxt', '@nuxtjs/device', 'dayjs-nuxt'],

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
            locale: {
                firstDayOfWeek: 1,
                dayNames: ['Воскресенье', 'Понедельник', 'Вторник', 'Среда', 'Четверг', 'Пятница', 'Суббота'],
                dayNamesShort: ['Вс', 'Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб'],
                dayNamesMin: ['Вс', 'Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб'],
                monthNames: [
                    'Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь',
                    'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь'
                ],
                monthNamesShort: [
                    'Янв', 'Фев', 'Мар', 'Апр', 'Май', 'Июн',
                    'Июл', 'Авг', 'Сен', 'Окт', 'Ноя', 'Дек'
                ],
                today: 'Сегодня',
                clear: 'Очистить',
                weekHeader: 'Нед',
                weak: 'Слабый',
                medium: 'Средний',
                strong: 'Сильный',
                passwordPrompt: 'Введите пароль'
            }
        }
    },

    tailwindcss: {
        config: {
            darkMode: ['selector', '[data-theme="dark"]'],
        }
    },

    dayjs: {
        locales: ['ru'],
        plugins: ['relativeTime', 'customParseFormat'],
        defaultLocale: 'ru',
    },

    runtimeConfig: {
        public: {
            apiBase: process.env.NUXT_PUBLIC_APP_API_URL || 'http://localhost:8000',
            appUrl: process.env.NUXT_PUBLIC_APP_URL || 'http://localhost:3000',
        }
    },

    ssr: false,

    nitro: {
        preset: 'node-server',
        host: '0.0.0.0',
        port: 3000,
        minify: true,
        sourceMap: false,
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
        },
        build: {
            target: 'es2015',
            minify: 'terser',

            terserOptions: {
                compress: {
                    drop_console: true,
                    drop_debugger: true,
                    pure_funcs: ['console.log', 'console.info', 'console.debug']
                }
            },
            rollupOptions: {
                treeshake: true,
                output: {
                    manualChunks: {
                        'vendor': ['vue', 'pinia'],
                        'primevue': ['primevue'],
                        'utils': ['dayjs', '@vueuse/core']
                    },
                    // Настройка имен файлов для кеширования
                    chunkFileNames: 'chunks/[name]-[hash].js',
                    entryFileNames: 'entry/[name]-[hash].js',
                    assetFileNames: 'assets/[name]-[hash].[ext]'
                }
            },
            // Размер чанков
            chunkSizeWarningLimit: 1000,
            // Включаем source maps только для stage окружения
            sourcemap: process.env.NODE_ENV === 'development'
        },
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