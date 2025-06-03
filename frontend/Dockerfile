FROM node:22-alpine

WORKDIR /app

# Копирование package.json и package-lock.json
COPY package*.json ./

# Установка зависимостей
RUN npm install

# Копирование остальных файлов
COPY . .

# Экспонирование порта
EXPOSE 3000

# Запуск приложения в режиме разработки
CMD ["npm", "run", "dev"]