FROM node:22-alpine

WORKDIR /app

# Копирование package.json и package-lock.json
COPY package*.json ./

# Установка зависимостей
RUN npm install

# Копирование остальных файлов
COPY . .

# Установка переменных окружения для Docker
ENV HOST=0.0.0.0
ENV PORT=3000
ENV NITRO_HOST=0.0.0.0
ENV NITRO_PORT=3000

# Экспонирование порта
EXPOSE 3000

# Запуск приложения в режиме разработки
CMD ["npm", "run", "dev"]