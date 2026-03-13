# --- Etapa 1: PHP y Dependencias ---
FROM php:8.2-apache

# 1. Instalamos dependencias del sistema y extensiones de PHP para Laravel
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    zip \
    unzip \
    git \
    curl \
    gnupg

# Instalamos extensiones necesarias para MySQL y optimización
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip

# 2. Instalamos Node.js (necesario para Vite y UnoCSS)
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs

# 3. Traemos Composer desde la imagen oficial
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 4. Configuramos el DocumentRoot de Apache para Laravel (/public)
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf
RUN a2enmod rewrite

# 5. Copiamos los archivos del proyecto
WORKDIR /var/www/html
COPY . .

# 6. Instalamos dependencias de PHP y JS
RUN composer install --no-dev --optimize-autoloader
RUN npm install && npm run build

# 7. Ajustamos permisos para Laravel y los Módulos
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache /var/www/html/Modules

# Exponemos el puerto
EXPOSE 80

CMD ["apache2-foreground"]
