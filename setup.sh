#!/bin/bash

# Navigate to the project directory
# Defaulting to current directory or /root/goya if on server
PROJECT_DIR="$(pwd)"
if [ -d "/root/goya" ]; then
    PROJECT_DIR="/root/goya"
fi
cd "$PROJECT_DIR"

echo "Using project directory: $PROJECT_DIR"

# 1. Handle .env file
if [ ! -f .env ]; then
    if [ -f .env.example ]; then
        echo "Creating .env from .env.example..."
        cp .env.example .env
    else
        echo "Creating basic .env file..."
        cat <<EOT >> .env
APP_NAME=Goya
APP_ENV=production
APP_DEBUG=false
APP_URL=http://localhost

LOG_CHANNEL=stack
LOG_LEVEL=debug

DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=laravel
DB_USERNAME=laravel
DB_PASSWORD=secret

REDIS_HOST=redis
REDIS_PASSWORD=null
REDIS_PORT=6379
EOT
    fi
fi

# 2. Fix/Generate APP_KEY
# Check if key is missing or is the literal bash command string
if ! grep -q "APP_KEY=base64:" .env || grep -q "APP_KEY=base64:\$(openssl" .env; then
    echo "Generating new APP_KEY..."
    # Remove any existing APP_KEY lines
    sed -i '/APP_KEY=/d' .env
    # Generate and append a real key
    NEW_KEY="base64:$(openssl rand -base64 32)"
    echo "APP_KEY=$NEW_KEY" >> .env
    echo "New APP_KEY generated."
fi

# 3. Docker Deployment
echo "Restarting Docker containers..."
docker compose down
docker compose up -d

# 4. Post-deployment tasks
echo "Waiting for containers to start..."
sleep 10

echo "Ensuring database exists..."
docker exec goya-db mysql -u root -proot -e "CREATE DATABASE IF NOT EXISTS laravel; CREATE USER IF NOT EXISTS 'laravel'@'%' IDENTIFIED BY 'secret'; GRANT ALL PRIVILEGES ON laravel.* TO 'laravel'@'%'; FLUSH PRIVILEGES;"

echo "Running migrations..."
docker exec goya-app php artisan migrate --force

echo "Running seeders..."
docker exec goya-app php artisan db:seed --force

echo "Installing Passport..."
docker exec goya-app php artisan passport:install --force

echo "Setting permissions..."
docker exec goya-app chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache /var/www/config

echo "Deployment finished successfully!"
