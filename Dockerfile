FROM php:8.4-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    unzip \
    git \
    curl \
    gnupg \
    procps

# Install Node.js and npm
RUN curl -sL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs

# Install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_mysql gd zip

# Set working directory
WORKDIR /var/www

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Create a new Laravel project if composer.json doesn't exist
RUN if [ ! -f "composer.json" ]; then \
        mkdir -p /tmp/laravel && \
        cd /tmp/laravel && \
        composer create-project --prefer-dist laravel/laravel:^12.0 . --no-interaction && \
        cp -R /tmp/laravel/. /var/www/ && \
        rm -rf /tmp/laravel; \
    fi

# Copy application files (will be overridden by volume in development)
COPY . .

# Install dependencies
RUN composer install --no-interaction --prefer-dist --optimize-autoloader

# Create an entrypoint script to handle user creation
RUN echo '#!/bin/bash\n\
# Create user with same UID as host if provided\n\
if [ ! -z "$USER_ID" ] && [ ! -z "$GROUP_ID" ]; then\n\
    echo "Creating user with UID:$USER_ID and GID:$GROUP_ID"\n\
    groupadd -g $GROUP_ID appgroup\n\
    useradd -u $USER_ID -g $GROUP_ID -m -s /bin/bash appuser\n\
    chown -R $USER_ID:$GROUP_ID /var/www\n\
    # Run PHP-FPM as the new user\n\
    sed -i "s/user = www-data/user = appuser/g" /usr/local/etc/php-fpm.d/www.conf\n\
    sed -i "s/group = www-data/group = appgroup/g" /usr/local/etc/php-fpm.d/www.conf\n\
    # Configure Git to trust the mounted directory\n\
    git config --global --add safe.directory /var/www\n\
else\n\
    # Default to www-data for production\n\
    chown -R www-data:www-data /var/www\n\
    # Configure Git to trust the mounted directory\n\
    git config --global --add safe.directory /var/www\n\
fi\n\
\n\
# Make storage directory writable\n\
chmod -R 755 /var/www/storage\n\
\n\
# Start PHP-FPM\n\
exec "$@"\n' > /usr/local/bin/entrypoint.sh

RUN chmod +x /usr/local/bin/entrypoint.sh

# Expose port 9000
EXPOSE 9000

# Use the entrypoint script
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["php-fpm"]