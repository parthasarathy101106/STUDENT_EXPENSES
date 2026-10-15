FROM php:8.4-cli-alpine

RUN docker-php-ext-install mysqli pdo_mysql

WORKDIR /app

COPY . .

CMD ["sh", "-c", "php -S 0.0.0.0:${PORT:-8000} -t public"]
