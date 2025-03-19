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

# Install and configure Xdebug
RUN pecl install xdebug && docker-php-ext-enable xdebug \
    && echo "xdebug.mode=debug,develop" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.client_host=host.docker.internal" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.client_port=9003" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.start_with_request=yes" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.log=/var/log/xdebug.log" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.log_level=7" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.discover_client_host=0" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.idekey=VSCODE" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

# Set working directory
WORKDIR /var/www

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy composer files first for better layer caching
COPY composer.json composer.lock* ./

# Install dependencies (but don't run scripts yet as code isn't copied)
RUN composer install --no-scripts --no-autoloader --no-interaction

# Copy the rest of the application code
COPY . .

# Generate optimized autoloader and run scripts now that code is available
RUN composer dump-autoload --optimize && composer run-script post-autoload-dump

# Install NPM dependencies and build assets if package.json exists
RUN if [ -f "package.json" ]; then \
        npm install && \
        npm run build; \
    fi

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

# Create directory for nginx config
RUN mkdir -p /var/www/docker-entrypoint.d/nginx-conf

# Copy nginx configuration
COPY nginx/default.conf /var/www/docker-entrypoint.d/nginx-conf/default.conf

# Set proper permissions for storage and cache
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache /var/www/vendor
RUN chmod -R 775 /var/www/storage /var/www/bootstrap/cache /var/www/vendor

# Expose port 9000
EXPOSE 9000

# Use the entrypoint script
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["php-fpm"]