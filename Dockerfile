FROM composer:latest

COPY . .

RUN apk add --no-cache postgresql-dev && \
    docker-php-ext-install pdo pdo_pgsql pgsql
    
RUN apk add nodejs && \
    apk add npm

RUN composer install && \
    npm install && \
    npm run build && \
    php artisan storage:link

RUN php artisan key:generate

# --no-reload: tanpa ini "artisan serve" membuang env dari compose (env_file) lalu membaca ulang .env yang ikut
# ter-copy saat build, sehingga web server bisa memakai konfigurasi (mis. database) berbeda dari "docker exec ... artisan"
CMD [ "php", "artisan", "serve", "--host=0.0.0.0", "--port=80", "--no-reload" ]
