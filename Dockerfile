FROM composer:latest

COPY . .

RUN apk add --no-cache postgresql-dev && \
    docker-php-ext-install pdo pdo_pgsql pgsql

# gd + exif: foto unggahan admin dikecilkan & diubah ke WebP sebelum disimpan (App\Support\ImageOptimizer)
RUN apk add --no-cache libpng-dev libjpeg-turbo-dev libwebp-dev freetype-dev && \
    docker-php-ext-configure gd --with-jpeg --with-webp --with-freetype && \
    docker-php-ext-install gd exif
    
RUN apk add nodejs && \
    apk add npm

RUN composer install && \
    npm install && \
    npm run build && \
    php artisan storage:link

# APP_KEY dan konfigurasi lain datang dari env_file saat runtime (.env tidak ikut ke image, lihat .dockerignore).
# Migrasi dijalankan saat container start karena database belum ada saat build. Seeding tetap manual.
# --no-reload: tanpa ini "artisan serve" membuang env dari compose (env_file) lalu membaca ulang .env,
# sehingga web server bisa memakai konfigurasi (mis. database) berbeda dari "docker exec ... artisan"
CMD [ "sh", "-c", "php artisan migrate --force && exec php artisan serve --host=0.0.0.0 --port=80 --no-reload" ]
