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

# Install PHP extensions
RUN docker-php-ext-install pdo pdo_mysql mbstring exif pcntl bcmath gd zip

# Install Node.js 20
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs \
    && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Copy all files
COPY . .

# Delete Windows-generated lock file so Composer resolves fresh on Linux
RUN rm -f composer.lock

# Install PHP deps cleanly for Linux environment
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts

# Install Node deps and build frontend
RUN npm install
RUN npm run build

# Fix storage permissions
RUN chmod -R 775 storage bootstrap/cache

EXPOSE 8000

# Run artisan commands at startup (APP_KEY is available here as env var)
CMD php artisan config:clear && \
    php artisan migrate --force && \
    php artisan serve --host=0.0.0.0 --port=${PORT:-8000}
