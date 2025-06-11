FROM node:22-alpine

# Включаем corepack для pnpm
RUN corepack enable && corepack prepare pnpm@latest --activate

WORKDIR /app

# Копирование файлов зависимостей
COPY package.json pnpm-lock.yaml ./

# Установка зависимостей через pnpm
RUN pnpm install --frozen-lockfile

# Копирование остальных файлов
COPY . .

# Экспонирование порта
EXPOSE 3000

# Запуск приложения в режиме разработки
CMD ["pnpm", "run", "dev"]