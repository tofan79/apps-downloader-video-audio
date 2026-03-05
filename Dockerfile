# appsDLP Dockerfile
FROM php:8.3-cli


# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    python3 \
    python3-venv \
    ffmpeg \
    sqlite3 \
    libsqlite3-dev \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install yt-dlp
RUN curl -L https://github.com/yt-dlp/yt-dlp/releases/latest/download/yt-dlp -o /usr/local/bin/yt-dlp \
    && chmod a+rx /usr/local/bin/yt-dlp

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd pdo_sqlite

# Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Install Node.js
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs

# Set Working Directory
WORKDIR /var/www

# Copy the entire app
COPY . /var/www

# Ensure database exists
RUN touch database/database.sqlite

# Install dependencies & build
RUN composer install --no-interaction --optimize-autoloader
RUN npm install
RUN npm run build

# Clear Laravel caches
# Skip running artisan cache/config commands here because real env is bound at runtime
# RUN php artisan cache:clear
# RUN php artisan config:clear
# RUN php artisan view:clear

# Give RW permissions for Laravel runtime folders
RUN chmod -R 777 storage bootstrap/cache database

EXPOSE 8000

# Default command for the Web Server container
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
