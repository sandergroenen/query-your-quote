#!/bin/bash

echo "Starting Laravel Docker environment..."

# Build and start the containers
docker compose up -d --build

echo "Waiting for containers to start..."
sleep 5

# Check if .env file exists, if not copy from .env.example
if [ ! -f ".env" ] && [ -f ".env.example" ]; then
    echo "Creating .env file from .env.example..."
    cp .env.example .env
fi

# Install Laravel dependencies
echo "Installing dependencies..."
docker compose exec -T app composer install

# Generate application key
echo "Generating application key..."
docker compose exec -T app php artisan key:generate --no-interaction

# Clear all caches
echo "Clearing all caches..."
docker compose exec -T app php artisan optimize:clear

# Run migrations
echo "Running database migrations..."
docker compose exec -T app php artisan migrate --force

# echo "Seeding the database..."
docker compose exec -T app php artisan db:seed --force

# Start Vite development server in the background
echo "Starting development run..."
docker compose exec -d app bash -c "cd /var/www && npm run dev"

echo "Laravel application is now running at http://localhost"
