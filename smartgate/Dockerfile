FROM php:8.2-cli

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    zip \
    unzip \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions needed by Laravel + maatwebsite/excel
RUN docker-php-ext-install pdo pdo_mysql mbstring exif pcntl bcmath gd zip

# Install Node.js 20
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs \
    && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Copy all project files
COPY . .

# Install PHP dependencies (ignore platform reqs to avoid Windows/Linux lock mismatch)
RUN composer install --no-dev --optimize-autoloader --no-interaction --ignore-platform-reqs

# Install Node dependencies and build frontend assets
RUN npm install
RUN npm run build

# Laravel cache optimizations
RUN php artisan config:clear
RUN php artisan view:cache

EXPOSE 8000

CMD php artisan migrate --force && php artisan serve --host=0.0.0.0 --port=${PORT:-8000}
