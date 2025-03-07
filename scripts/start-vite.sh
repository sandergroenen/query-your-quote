#!/bin/bash

# Script to start the Vite development server in the app container
echo "Starting Vite development server in the background..."
docker compose exec -d app bash -c "cd /var/www && npm run vite &"
echo "Vite development server is running at http://localhost:5173"
